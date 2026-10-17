<?php

namespace Tests\Feature\Billing;

use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\WebhookEvent;
use App\Models\Workspace;
use App\Services\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class PayPalBillingTest extends TestCase
{
    use BuildsWorkspaces;
    use RefreshDatabase;

    private const API = 'https://api-m.sandbox.paypal.com';

    /** @var array<string, array<string, mixed>> */
    private array $remote = [];

    private string $verification = 'SUCCESS';

    /** @var list<array<string, mixed>> */
    private array $listedPlans = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.provider' => 'paypal',
            'billing.paypal.mode' => 'sandbox',
            'billing.paypal.client_id' => 'test-client',
            'billing.paypal.client_secret' => 'test-secret',
            'billing.paypal.webhook_id' => 'WH-TEST',
            'billing.paypal.plans.pro.month' => 'P-PROMONTH',
        ]);

        Http::fake(function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            return match (true) {
                $path === '/v1/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
                $path === '/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => $this->verification]),
                $path === '/v1/billing/subscriptions' && $request->method() === 'POST' => Http::response([
                    'id' => 'I-NEWSUB123',
                    'status' => 'APPROVAL_PENDING',
                    'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/webapps/billing/subscriptions?ba_token=BA-1']],
                ], 201),
                str_ends_with($path, '/cancel') => Http::response(null, 204),
                $path === '/v1/catalogs/products' => Http::response(['id' => 'PROD-1'], 201),
                $path === '/v1/billing/plans' && $request->method() === 'GET' => Http::response(['plans' => $this->listedPlans]),
                $path === '/v1/billing/plans' => Http::response(['id' => 'P-'.strtoupper(substr(md5((string) json_encode($request->data())), 0, 10))], 201),
                str_starts_with($path, '/v1/billing/subscriptions/') => isset($this->remote[basename($path)])
                    ? Http::response($this->remote[basename($path)])
                    : Http::response(['name' => 'RESOURCE_NOT_FOUND'], 404),
                default => Http::response(['message' => 'unexpected '.$path], 500),
            };
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function remoteSubscription(Workspace $workspace, string $status = 'ACTIVE', string $updated = '2026-10-10T10:00:00Z', ?string $nextBilling = '2026-11-10T10:00:00Z'): array
    {
        return $this->remote['I-SUB123456'] = [
            'id' => 'I-SUB123456',
            'plan_id' => 'P-PROMONTH',
            'status' => $status,
            'custom_id' => (string) $workspace->id,
            'status_update_time' => $updated,
            'subscriber' => ['payer_id' => 'PAYER1', 'email_address' => 'owner@example.com'],
            'billing_info' => $nextBilling === null ? [] : ['next_billing_time' => $nextBilling],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function deliver(array $payload): TestResponse
    {
        return $this->call('POST', '/webhooks/paypal', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYPAL_AUTH_ALGO' => 'SHA256withRSA',
            'HTTP_PAYPAL_CERT_URL' => 'https://api.sandbox.paypal.com/v1/notifications/certs/CERT-1',
            'HTTP_PAYPAL_TRANSMISSION_ID' => 'tx-1',
            'HTTP_PAYPAL_TRANSMISSION_SIG' => 'sig',
            'HTTP_PAYPAL_TRANSMISSION_TIME' => '2026-10-10T10:00:00Z',
        ], (string) json_encode($payload));
    }

    /**
     * @return array<string, mixed>
     */
    private function event(string $id, string $type, string $resourceId = 'I-SUB123456'): array
    {
        return ['id' => $id, 'event_type' => $type, 'create_time' => '2026-10-10T10:00:00Z', 'resource' => ['id' => $resourceId]];
    }

    public function test_billing_page_shares_the_public_paypal_client_id_only(): void
    {
        $workspace = $this->workspace();

        $this->actingAs($this->owner($workspace))->get('/app/billing')
            ->assertInertia(fn ($page) => $page->where('provider', 'paypal')->where('paypalClientId', 'test-client'))
            ->assertDontSee('test-secret');
    }

    public function test_checkout_returns_paypal_approval_url_tied_to_the_workspace(): void
    {
        $workspace = $this->workspace();

        $this->actingAs($this->owner($workspace))
            ->postJson('/app/billing/checkout', ['plan' => 'pro', 'interval' => 'month'])
            ->assertOk()
            ->assertJson(['provider' => 'paypal', 'subscription_id' => 'I-NEWSUB123', 'redirect' => 'https://www.sandbox.paypal.com/webapps/billing/subscriptions?ba_token=BA-1']);

        Http::assertSent(fn (Request $request) => $request->url() === self::API.'/v1/billing/subscriptions'
            && $request['plan_id'] === 'P-PROMONTH'
            && $request['custom_id'] === (string) $workspace->id);
    }

    public function test_checkout_without_a_plan_id_is_rejected(): void
    {
        $workspace = $this->workspace();

        $this->actingAs($this->owner($workspace))
            ->postJson('/app/billing/checkout', ['plan' => 'solo', 'interval' => 'month'])
            ->assertStatus(422);
    }

    public function test_return_activates_the_plan_from_the_paypal_api(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $workspace = $this->workspace();
        $this->remoteSubscription($workspace);

        $this->actingAs($this->owner($workspace))
            ->get('/app/billing/paypal/return?subscription_id=I-SUB123456')
            ->assertRedirect(route('billing.show', ['checkout' => 'success']));

        $subscription = Subscription::query()->firstOrFail();
        $this->assertSame('paypal', $subscription->provider);
        $this->assertSame('active', $subscription->status);
        $this->assertSame('pro', app(PlanService::class)->effectivePlan($workspace->refresh()));
    }

    public function test_card_form_confirmation_activates_only_the_owners_subscription(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $workspace = $this->workspace();
        $this->remoteSubscription($workspace);

        $this->actingAs($this->owner($workspace))
            ->postJson('/app/billing/paypal/confirm', ['subscription_id' => 'I-SUB123456'])
            ->assertOk()
            ->assertJson(['active' => true]);

        $this->assertSame('pro', app(PlanService::class)->effectivePlan($workspace->refresh()));

        $other = $this->workspace();
        $this->actingAs($this->owner($other))
            ->postJson('/app/billing/paypal/confirm', ['subscription_id' => 'I-SUB123456'])
            ->assertOk()
            ->assertJson(['active' => false]);

        $this->assertNull($other->refresh()->activeSubscription());
    }

    public function test_return_ignores_a_subscription_from_another_workspace(): void
    {
        $workspace = $this->workspace();
        $this->remoteSubscription($this->workspace());

        $this->actingAs($this->owner($workspace))
            ->get('/app/billing/paypal/return?subscription_id=I-SUB123456')
            ->assertRedirect(route('billing.show', ['checkout' => 'pending']));

        $this->assertSame(0, Subscription::query()->count());
        $this->assertNull($workspace->refresh()->activeSubscription());
    }

    public function test_webhooks_that_paypal_does_not_verify_are_rejected(): void
    {
        $this->verification = 'FAILURE';

        $this->deliver($this->event('WH-1', 'BILLING.SUBSCRIPTION.ACTIVATED'))->assertStatus(401);

        $this->assertSame(0, WebhookEvent::query()->count());
    }

    public function test_webhooks_are_idempotent_and_sync_from_the_api(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $workspace = $this->workspace();
        $this->remoteSubscription($workspace);

        $this->deliver($this->event('WH-1', 'BILLING.SUBSCRIPTION.ACTIVATED'))->assertOk()->assertJson(['status' => 'accepted']);
        $this->deliver($this->event('WH-1', 'BILLING.SUBSCRIPTION.ACTIVATED'))->assertOk()->assertJson(['status' => 'duplicate']);

        $this->assertSame(1, WebhookEvent::query()->count());
        $this->assertSame('processed', WebhookEvent::query()->firstOrFail()->status);
        $this->assertSame('pro', app(PlanService::class)->effectivePlan($workspace->refresh()));
    }

    public function test_cancelled_subscription_keeps_access_until_the_paid_period_ends(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $workspace = $this->workspace();
        $this->remoteSubscription($workspace);
        $this->deliver($this->event('WH-1', 'BILLING.SUBSCRIPTION.ACTIVATED'))->assertOk();

        $this->remoteSubscription($workspace, 'CANCELLED', '2026-10-12T09:00:00Z', null);
        $this->deliver($this->event('WH-2', 'BILLING.SUBSCRIPTION.CANCELLED'))->assertOk();

        $subscription = Subscription::query()->firstOrFail();
        $this->assertSame('canceled', $subscription->status);
        $this->assertSame('2026-11-10', $subscription->cancels_at?->toDateString());
        $this->assertSame('pro', app(PlanService::class)->effectivePlan($workspace->refresh()));

        Carbon::setTestNow('2026-11-11 10:00:00');
        $this->assertSame('free', app(PlanService::class)->effectivePlan($workspace->refresh()));
    }

    public function test_recurring_payment_is_recorded_as_a_transaction(): void
    {
        $workspace = $this->workspace();
        $this->remoteSubscription($workspace);

        $this->deliver([
            'id' => 'WH-SALE',
            'event_type' => 'PAYMENT.SALE.COMPLETED',
            'create_time' => '2026-10-10T10:00:00Z',
            'resource' => [
                'id' => 'SALE-1',
                'state' => 'completed',
                'billing_agreement_id' => 'I-SUB123456',
                'amount' => ['total' => '24.00', 'currency' => 'USD'],
                'create_time' => '2026-10-10T10:00:00Z',
            ],
        ])->assertOk();

        $transaction = Transaction::query()->firstOrFail();
        $this->assertSame($workspace->id, $transaction->workspace_id);
        $this->assertSame(2400, $transaction->total_minor);
        $this->assertSame('paypal', $transaction->provider);
    }

    public function test_owner_can_cancel_but_not_resume_a_paypal_subscription(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $workspace = $this->workspace();
        $this->remoteSubscription($workspace);
        $this->deliver($this->event('WH-1', 'BILLING.SUBSCRIPTION.ACTIVATED'))->assertOk();
        $owner = $this->owner($workspace);

        $this->actingAs($owner)->post('/app/billing/cancel')->assertRedirect();

        Http::assertSent(fn (Request $request) => $request->url() === self::API.'/v1/billing/subscriptions/I-SUB123456/cancel');
        $this->assertSame('2026-11-10', Subscription::query()->firstOrFail()->cancels_at?->toDateString());

        $this->actingAs($owner)->post('/app/billing/resume')->assertSessionHasErrors('billing');
    }

    public function test_setup_command_creates_plans_and_saves_them_to_env(): void
    {
        $dir = $this->tempEnvDirectory("APP_NAME=Test\nPAYPAL_PLAN_PRO_MONTHLY=\n");

        $this->artisan('billing:paypal-setup')
            ->expectsOutputToContain('Connected to PayPal (sandbox)')
            ->expectsOutputToContain('Saved to .env')
            ->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request->url() === self::API.'/v1/billing/plans'
            && data_get($request->data(), 'billing_cycles.0.pricing_scheme.fixed_price.value') === '24.00');

        $env = (string) file_get_contents($dir.'/.env');
        $this->assertStringContainsString('APP_NAME=Test', $env);
        $this->assertStringContainsString('BILLING_PROVIDER=paypal', $env);
        $this->assertMatchesRegularExpression('/^PAYPAL_PLAN_PRO_MONTHLY=P-[A-Z0-9]+$/m', $env);
        $this->assertSame(1, substr_count($env, 'PAYPAL_PLAN_PRO_MONTHLY='));
    }

    public function test_env_command_writes_existing_plan_ids(): void
    {
        $dir = $this->tempEnvDirectory("APP_NAME=Test\nPAYPAL_MODE=sandbox\n");
        $this->listedPlans = [];

        foreach (['Solo', 'Pro', 'Agency'] as $name) {
            foreach (['monthly', 'yearly'] as $interval) {
                $this->listedPlans[] = ['id' => 'P-'.strtoupper($name.$interval), 'name' => "{$name} ({$interval})", 'status' => 'ACTIVE', 'create_time' => '2026-10-17T00:23:00Z'];
            }
        }
        $this->listedPlans[] = ['id' => 'P-OLDPRO', 'name' => 'Pro (monthly)', 'status' => 'ACTIVE', 'create_time' => '2026-10-01T00:00:00Z'];

        $this->artisan('billing:paypal-env')->expectsOutputToContain('Saved to .env')->assertSuccessful();

        $env = (string) file_get_contents($dir.'/.env');
        $this->assertStringContainsString('PAYPAL_PLAN_PRO_MONTHLY=P-PROMONTHLY', $env);
        $this->assertStringContainsString('PAYPAL_PLAN_AGENCY_YEARLY=P-AGENCYYEARLY', $env);
        $this->assertStringContainsString('BILLING_PROVIDER=paypal', $env);
        $this->assertSame(1, substr_count($env, 'PAYPAL_MODE='));
    }

    private function tempEnvDirectory(string $contents): string
    {
        $dir = sys_get_temp_dir().'/paypal-env-'.bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir.'/.env', $contents);
        $this->app->useEnvironmentPath($dir);

        return $dir;
    }
}
