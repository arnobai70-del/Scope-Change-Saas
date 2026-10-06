<?php

namespace Tests\Feature\Billing;

use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Services\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class PaddleWebhookTest extends TestCase
{
    use BuildsWorkspaces;
    use RefreshDatabase;

    private const SECRET = 'pdl_ntfset_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.paddle.webhook_secret' => self::SECRET,
            'billing.paddle.prices.pro.month' => 'pri_pro_month',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function deliver(array $payload, ?string $secret = self::SECRET, ?int $timestamp = null): TestResponse
    {
        $body = (string) json_encode($payload);
        $ts = (string) ($timestamp ?? time());
        $signature = 'ts='.$ts.';h1='.hash_hmac('sha256', $ts.':'.$body, (string) $secret);

        return $this->call('POST', '/webhooks/paddle', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $signature,
        ], $body);
    }

    /**
     * @return array<string, mixed>
     */
    private function subscriptionEvent(int $workspaceId, string $eventId = 'evt_1', string $status = 'active', string $updatedAt = '2026-10-01T10:00:00Z'): array
    {
        return [
            'event_id' => $eventId,
            'event_type' => 'subscription.created',
            'occurred_at' => $updatedAt,
            'data' => [
                'id' => 'sub_123',
                'status' => $status,
                'customer_id' => 'ctm_1',
                'custom_data' => ['workspace_id' => (string) $workspaceId],
                'items' => [['price' => ['id' => 'pri_pro_month', 'product_id' => 'pro_1'], 'quantity' => 1, 'status' => 'active']],
                'billing_cycle' => ['interval' => 'month', 'frequency' => 1],
                'current_billing_period' => ['starts_at' => '2026-10-01T10:00:00Z', 'ends_at' => '2026-11-01T10:00:00Z'],
                'updated_at' => $updatedAt,
            ],
        ];
    }

    public function test_rejects_invalid_signatures(): void
    {
        $workspace = $this->workspace();

        $this->deliver($this->subscriptionEvent($workspace->id), 'wrong-secret')->assertStatus(401);
        $this->deliver($this->subscriptionEvent($workspace->id), self::SECRET, time() - 3600)->assertStatus(401);

        $this->assertSame(0, WebhookEvent::query()->count());
    }

    public function test_activates_subscription_and_is_idempotent(): void
    {
        $workspace = $this->workspace(['trial_ends_at' => null]);

        $this->deliver($this->subscriptionEvent($workspace->id))->assertOk()->assertJson(['status' => 'accepted']);
        $this->deliver($this->subscriptionEvent($workspace->id))->assertOk()->assertJson(['status' => 'duplicate']);

        $this->assertSame(1, WebhookEvent::query()->count());
        $this->assertSame(1, Subscription::query()->count());
        $this->assertSame('processed', WebhookEvent::query()->firstOrFail()->status);
        $this->assertSame('pro', app(PlanService::class)->effectivePlan($workspace->refresh()));
    }

    public function test_out_of_order_events_do_not_overwrite_newer_state(): void
    {
        $workspace = $this->workspace(['trial_ends_at' => null]);

        $this->deliver($this->subscriptionEvent($workspace->id, 'evt_new', 'canceled', '2026-10-05T10:00:00Z'))->assertOk();
        $this->deliver($this->subscriptionEvent($workspace->id, 'evt_old', 'active', '2026-10-01T10:00:00Z'))->assertOk();

        $this->assertSame('canceled', Subscription::query()->firstOrFail()->status);
        $this->assertSame('free', app(PlanService::class)->effectivePlan($workspace->refresh()));
    }

    public function test_rejects_malformed_payload(): void
    {
        $this->deliver(['hello' => 'world'])->assertStatus(422);
    }
}
