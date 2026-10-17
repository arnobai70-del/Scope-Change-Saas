<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\WebhookEvent;
use App\Models\Workspace;
use App\Notifications\SubscriptionPaymentFailed;
use App\Support\Money;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

/**
 * Applies verified PayPal webhook events. Subscription events re-read the
 * subscription from the PayPal API, so the stored state is always PayPal's
 * current state regardless of delivery order.
 */
class PayPalWebhookHandler
{
    public function __construct(
        private readonly PayPalBilling $billing,
        private readonly AuditTrailService $audit,
        private readonly AnalyticsService $analytics,
    ) {}

    public function handle(WebhookEvent $event): void
    {
        /** @var array<string, mixed> $resource */
        $resource = Arr::get($event->payload, 'resource', []);

        match (true) {
            $event->event_type === 'BILLING.SUBSCRIPTION.PAYMENT.FAILED' => $this->paymentFailed($resource),
            str_starts_with($event->event_type, 'BILLING.SUBSCRIPTION.') => $this->syncSubscription($resource),
            $event->event_type === 'PAYMENT.SALE.COMPLETED' => $this->recordSale($resource),
            in_array($event->event_type, ['PAYMENT.SALE.REFUNDED', 'PAYMENT.SALE.REVERSED'], true) => $this->recordRefund($event->event_type, $resource),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function syncSubscription(array $resource): void
    {
        $id = (string) Arr::get($resource, 'id');

        if ($id !== '') {
            $this->billing->syncFromApi($id);
        }
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function paymentFailed(array $resource): void
    {
        $this->syncSubscription($resource);

        $subscription = Subscription::query()->where('provider_subscription_id', (string) Arr::get($resource, 'id'))->first();

        if ($subscription !== null) {
            $this->analytics->track('subscription_payment_failed', $subscription->workspace_id);
            Notification::send($subscription->workspace->owner()->get(), new SubscriptionPaymentFailed($subscription->workspace));
        }
    }

    /**
     * A recurring payment was collected.
     *
     * @param  array<string, mixed>  $resource
     */
    private function recordSale(array $resource): void
    {
        $saleId = (string) Arr::get($resource, 'id');
        $subscriptionId = Arr::get($resource, 'billing_agreement_id');

        if ($saleId === '') {
            return;
        }

        $subscription = is_string($subscriptionId) ? Subscription::query()->where('provider_subscription_id', $subscriptionId)->first() : null;

        if ($subscription === null && is_string($subscriptionId)) {
            $subscription = $this->billing->syncFromApi($subscriptionId);
        }

        $currency = strtoupper((string) Arr::get($resource, 'amount.currency', 'USD'));

        try {
            $total = Money::fromDecimal((string) Arr::get($resource, 'amount.total', '0'), $currency)->amount;
        } catch (InvalidArgumentException) {
            $total = 0;
        }

        Transaction::query()->updateOrCreate(
            ['provider_transaction_id' => $saleId],
            [
                'workspace_id' => $subscription?->workspace_id,
                'provider' => 'paypal',
                'provider_subscription_id' => is_string($subscriptionId) ? $subscriptionId : null,
                'status' => (string) Arr::get($resource, 'state', 'completed'),
                'total_minor' => $total,
                'currency' => substr($currency, 0, 3),
                'invoice_number' => Arr::get($resource, 'invoice_number'),
                'billed_at' => $this->time(Arr::get($resource, 'create_time')),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function recordRefund(string $type, array $resource): void
    {
        $saleId = (string) Arr::get($resource, 'sale_id');
        $transaction = $saleId !== '' ? Transaction::query()->where('provider_transaction_id', $saleId)->first() : null;

        if ($transaction === null) {
            return;
        }

        $transaction->forceFill(['status' => $type === 'PAYMENT.SALE.REFUNDED' ? 'refunded' : 'reversed'])->save();

        $workspace = $transaction->workspace_id !== null ? Workspace::query()->find($transaction->workspace_id) : null;

        if ($workspace !== null) {
            $this->audit->record($workspace, 'billing.adjustment', ActorType::Provider, 'paypal', [
                'action' => $type === 'PAYMENT.SALE.REFUNDED' ? 'refund' : 'chargeback',
                'transaction_id' => $saleId,
            ]);
        }
    }

    private function time(mixed $value): ?Carbon
    {
        return is_string($value) && $value !== '' ? Carbon::parse($value)->utc() : null;
    }
}
