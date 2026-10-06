<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\WebhookEvent;
use App\Models\Workspace;
use App\Notifications\SubscriptionPaymentFailed;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Applies verified Paddle Billing events to local subscription state.
 * Handlers are idempotent and ignore events older than the stored state,
 * so duplicate or out-of-order deliveries cannot corrupt a workspace.
 */
class PaddleWebhookHandler
{
    public function __construct(
        private readonly BillingService $billing,
        private readonly AuditTrailService $audit,
        private readonly AnalyticsService $analytics,
    ) {}

    public function handle(WebhookEvent $event): void
    {
        /** @var array<string, mixed> $data */
        $data = Arr::get($event->payload, 'data', []);

        match (true) {
            str_starts_with($event->event_type, 'subscription.') => $this->syncSubscription($event->event_type, $data),
            $event->event_type === 'transaction.completed',
            $event->event_type === 'transaction.paid',
            $event->event_type === 'transaction.payment_failed',
            $event->event_type === 'transaction.past_due' => $this->syncTransaction($event->event_type, $data),
            $event->event_type === 'adjustment.created',
            $event->event_type === 'adjustment.updated' => $this->recordAdjustment($data),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncSubscription(string $type, array $data): void
    {
        $providerId = (string) Arr::get($data, 'id');
        $workspace = $this->resolveWorkspace($data, $providerId);

        if ($workspace === null || $providerId === '') {
            return;
        }

        $updatedAt = $this->time(Arr::get($data, 'updated_at'));

        DB::transaction(function () use ($type, $data, $providerId, $workspace, $updatedAt) {
            /** @var Subscription|null $subscription */
            $subscription = Subscription::query()->where('provider_subscription_id', $providerId)->lockForUpdate()->first();

            if ($subscription !== null && $updatedAt !== null && $subscription->provider_updated_at !== null && $updatedAt->lt($subscription->provider_updated_at)) {
                return; // stale, out-of-order delivery
            }

            /** @var list<array<string, mixed>> $items */
            $items = Arr::get($data, 'items', []);
            $plan = null;
            $interval = (string) Arr::get($data, 'billing_cycle.interval', 'month');

            foreach ($items as $item) {
                $match = $this->billing->planForPrice((string) Arr::get($item, 'price.id'));
                if ($match !== null) {
                    $plan = $match['plan'];
                    $interval = $match['interval'];
                }
            }

            $plan ??= $subscription->plan ?? 'free';
            $status = (string) Arr::get($data, 'status', 'active');
            $scheduled = Arr::get($data, 'scheduled_change');
            $wasActive = $subscription?->isActive() ?? false;

            $subscription ??= new Subscription(['provider_subscription_id' => $providerId]);
            $subscription->forceFill([
                'workspace_id' => $workspace->id,
                'provider' => 'paddle',
                'provider_customer_id' => Arr::get($data, 'customer_id'),
                'plan' => $plan,
                'billing_interval' => $interval,
                'status' => $status,
                'quantity' => 1,
                'trial_ends_at' => $status === 'trialing' ? $this->time(Arr::get($data, 'current_billing_period.ends_at')) : null,
                'current_period_ends_at' => $this->time(Arr::get($data, 'current_billing_period.ends_at')),
                'cancels_at' => is_array($scheduled) && ($scheduled['action'] ?? null) === 'cancel' ? $this->time($scheduled['effective_at'] ?? null) : null,
                'canceled_at' => $this->time(Arr::get($data, 'canceled_at')),
                'provider_updated_at' => $updatedAt,
            ])->save();

            foreach ($items as $item) {
                $subscription->items()->updateOrCreate(
                    ['provider_price_id' => (string) Arr::get($item, 'price.id')],
                    [
                        'provider_product_id' => Arr::get($item, 'price.product_id'),
                        'quantity' => (int) Arr::get($item, 'quantity', 1),
                        'status' => Arr::get($item, 'status'),
                    ],
                );
            }

            $workspace->forceFill(['plan' => $subscription->isActive() ? $plan : 'free'])->save();

            $this->audit->record($workspace, 'billing.'.$type, ActorType::Provider, 'paddle', ['plan' => $plan, 'status' => $status]);

            if (! $wasActive && $subscription->isActive()) {
                $this->analytics->track('subscription_activated', $workspace->id, null, ['plan' => $plan, 'interval' => $interval]);
            } elseif ($wasActive && $status === 'canceled') {
                $this->analytics->track('subscription_canceled', $workspace->id, null, ['plan' => $plan]);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncTransaction(string $type, array $data): void
    {
        $providerId = (string) Arr::get($data, 'id');
        $subscriptionId = Arr::get($data, 'subscription_id');
        $workspace = $this->resolveWorkspace($data, is_string($subscriptionId) ? $subscriptionId : null);

        if ($providerId === '') {
            return;
        }

        $currency = strtoupper((string) Arr::get($data, 'currency_code', 'USD'));

        Transaction::query()->updateOrCreate(
            ['provider_transaction_id' => $providerId],
            [
                'workspace_id' => $workspace?->id,
                'provider' => 'paddle',
                'provider_subscription_id' => $subscriptionId,
                'status' => (string) Arr::get($data, 'status', $type),
                'total_minor' => (int) Arr::get($data, 'details.totals.grand_total', 0),
                'currency' => substr($currency, 0, 3),
                'invoice_number' => Arr::get($data, 'invoice_number'),
                'billed_at' => $this->time(Arr::get($data, 'billed_at')),
            ],
        );

        if ($workspace !== null && in_array($type, ['transaction.payment_failed', 'transaction.past_due'], true)) {
            $this->analytics->track('subscription_payment_failed', $workspace->id);
            Notification::send($workspace->owner()->get(), new SubscriptionPaymentFailed($workspace));
        }
    }

    /**
     * Refunds and chargebacks are recorded for the audit trail; access
     * changes arrive through the matching subscription events.
     *
     * @param  array<string, mixed>  $data
     */
    private function recordAdjustment(array $data): void
    {
        $subscriptionId = Arr::get($data, 'subscription_id');
        $workspace = $this->resolveWorkspace($data, is_string($subscriptionId) ? $subscriptionId : null);

        if ($workspace !== null) {
            $this->audit->record($workspace, 'billing.adjustment', ActorType::Provider, 'paddle', [
                'action' => Arr::get($data, 'action'),
                'status' => Arr::get($data, 'status'),
                'transaction_id' => Arr::get($data, 'transaction_id'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveWorkspace(array $data, ?string $subscriptionId): ?Workspace
    {
        $workspaceId = Arr::get($data, 'custom_data.workspace_id');

        if (is_numeric($workspaceId)) {
            $workspace = Workspace::query()->find((int) $workspaceId);
            if ($workspace !== null) {
                return $workspace;
            }
        }

        if ($subscriptionId !== null && $subscriptionId !== '') {
            return Subscription::query()->where('provider_subscription_id', $subscriptionId)->first()?->workspace;
        }

        return null;
    }

    private function time(mixed $value): ?Carbon
    {
        return is_string($value) && $value !== '' ? Carbon::parse($value)->utc() : null;
    }
}
