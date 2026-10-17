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

    public function test_checkout_returns_paypal_approval_url_tied_to_the_workspace(): void
    {
        $workspace = $this->workspace();

        $this->actingAs($this->owner($workspace))
            ->postJson('/app/billing/checkout', ['plan' => 'pro', 'interval' => 'month'])
            ->assertOk()
            ->assertJson(['provider' => 'paypal', 'redirect' => 'https://www.sandbox.paypal.com/webapps/billing/subscriptions?ba_token=BA-1']);

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

    public function test_setup_command_creates_plans_and_prints_env_lines(): void
    {
        $this->artisan('billing:paypal-setup')
            ->expectsOutputToContain('Connected to PayPal (sandbox)')
            ->expectsOutputToContain('PAYPAL_PLAN_PRO_MONTHLY=P-')
            ->expectsOutputToContain('PAYPAL_PLAN_AGENCY_YEARLY=P-')
            ->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request->url() === self::API.'/v1/billing/plans'
            && data_get($request->data(), 'billing_cycles.0.pricing_scheme.fixed_price.value') === '24.00');
    }
}
