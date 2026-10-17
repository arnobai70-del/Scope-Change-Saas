<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * PayPal Subscriptions for the SaaS plan. The customer approves the
 * subscription on PayPal; access is granted only from state read back from
 * the PayPal API (on return and on every verified webhook), never from the
 * browser redirect itself.
 */
class PayPalBilling
{
    /** PayPal subscription status => local status. Only ACTIVE grants access. */
    private const STATUS_MAP = [
        'APPROVAL_PENDING' => 'incomplete',
        'APPROVED' => 'incomplete',
        'ACTIVE' => 'active',
        'SUSPENDED' => 'paused',
        'CANCELLED' => 'canceled',
        'EXPIRED' => 'canceled',
    ];

    public function __construct(
        private readonly PayPalClient $paypal,
        private readonly AuditTrailService $audit,
        private readonly AnalyticsService $analytics,
    ) {}

    public function isConfigured(): bool
    {
        return $this->paypal->hasCredentials();
    }

    /**
     * Public client id for the PayPal JS SDK (safe to send to the browser).
     */
    public function clientId(): ?string
    {
        $id = config('billing.paypal.client_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function planId(string $plan, string $interval): ?string
    {
        $id = config("billing.paypal.plans.{$plan}.{$interval}");

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * @return array{plan: string, interval: string}|null
     */
    public function planFor(string $planId): ?array
    {
        /** @var array<string, array<string, string|null>> $plans */
        $plans = config('billing.paypal.plans', []);

        foreach ($plans as $plan => $intervals) {
            foreach ($intervals as $interval => $id) {
                if ($id !== null && $id !== '' && hash_equals($id, $planId)) {
                    return ['plan' => $plan, 'interval' => $interval];
                }
            }
        }

        return null;
    }

    /**
     * Create a pending subscription on PayPal. The card form on the billing
     * page approves it by id; the approval URL is the fallback page.
     *
     * @return array{id: string, approve_url: string}
     */
    public function createSubscription(Workspace $workspace, User $user, string $plan, string $interval): array
    {
        $planId = $this->planId($plan, $interval);

        if (! $this->isConfigured() || $planId === null) {
            throw ValidationException::withMessages(['plan' => 'PayPal checkout is not configured yet. Please contact support.']);
        }

        $response = $this->paypal->post('/v1/billing/subscriptions', [
            'plan_id' => $planId,
            'custom_id' => (string) $workspace->id,
            'subscriber' => ['email_address' => $user->email],
            'application_context' => [
                'brand_name' => mb_substr((string) config('app.name'), 0, 127),
                'user_action' => 'SUBSCRIBE_NOW',
                'shipping_preference' => 'NO_SHIPPING',
                'return_url' => route('billing.paypal.return'),
                'cancel_url' => route('billing.show', ['checkout' => 'canceled']),
            ],
        ]);

        /** @var list<array{rel?: string, href?: string}> $links */
        $links = $response->json('links', []);
        $approve = collect($links)->firstWhere('rel', 'approve');
        $url = is_array($approve) ? ($approve['href'] ?? null) : null;
        $id = $response->json('id');

        if ($response->failed() || ! is_string($id) || ! is_string($url) || ! str_starts_with($url, 'https://')) {
            throw ValidationException::withMessages(['plan' => 'PayPal checkout could not be started. Please try again.']);
        }

        return ['id' => $id, 'approve_url' => $url];
    }

    /**
     * Read a subscription from PayPal and apply it. When $expected is given
     * the subscription must belong to that workspace.
     */
    public function syncFromApi(string $subscriptionId, ?Workspace $expected = null): ?Subscription
    {
        $data = $this->paypal->subscription($subscriptionId);

        if ($data === null) {
            return null;
        }

        if ($expected !== null && (string) Arr::get($data, 'custom_id') !== (string) $expected->id) {
            return null;
        }

        return $this->apply($data);
    }

    /**
     * Apply a PayPal subscription resource to local state. Idempotent, and
     * ignores data older than what is stored.
     *
     * @param  array<string, mixed>  $data
     */
    public function apply(array $data): ?Subscription
    {
        $providerId = (string) Arr::get($data, 'id');
        $planMatch = $this->planFor((string) Arr::get($data, 'plan_id'));
        $workspace = $this->workspaceFor($data, $providerId);

        if ($providerId === '' || $workspace === null) {
            return null;
        }

        $updatedAt = $this->time(Arr::get($data, 'status_update_time')) ?? $this->time(Arr::get($data, 'update_time'));
        $paypalStatus = strtoupper((string) Arr::get($data, 'status'));
        $status = self::STATUS_MAP[$paypalStatus] ?? 'incomplete';

        return DB::transaction(function () use ($data, $providerId, $planMatch, $workspace, $updatedAt, $status) {
            /** @var Subscription|null $subscription */
            $subscription = Subscription::query()->where('provider_subscription_id', $providerId)->lockForUpdate()->first();

            if ($subscription !== null && $updatedAt !== null && $subscription->provider_updated_at !== null && $updatedAt->lt($subscription->provider_updated_at)) {
                return $subscription; // stale data
            }

            $wasActive = $subscription?->isActive() ?? false;
            $plan = $planMatch['plan'] ?? $subscription->plan ?? 'free';
            $interval = $planMatch['interval'] ?? $subscription->billing_interval ?? 'month';
            $nextBilling = $this->time(Arr::get($data, 'billing_info.next_billing_time'));
            $periodEnd = $nextBilling ?? $subscription?->current_period_ends_at;

            // A cancelled PayPal subscription keeps access until the paid period ends.
            $cancelsAt = $status === 'canceled' && $periodEnd !== null && $periodEnd->isFuture() ? $periodEnd : null;

            $subscription ??= new Subscription(['provider_subscription_id' => $providerId]);
            $subscription->forceFill([
                'workspace_id' => $workspace->id,
                'provider' => 'paypal',
                'provider_customer_id' => Arr::get($data, 'subscriber.payer_id'),
                'plan' => $plan,
                'billing_interval' => $interval,
                'status' => $status,
                'quantity' => 1,
                'trial_ends_at' => null,
                'current_period_ends_at' => $periodEnd,
                'cancels_at' => $status === 'canceled' ? $cancelsAt : $subscription->cancels_at,
                'canceled_at' => $status === 'canceled' ? ($updatedAt ?? Carbon::now()) : null,
                'provider_updated_at' => $updatedAt,
            ])->save();

            $active = $subscription->isActive();
            $workspace->forceFill(['plan' => $active ? $plan : 'free'])->save();

            if ($subscription->wasRecentlyCreated || $subscription->wasChanged('status')) {
                $this->audit->record($workspace, 'billing.paypal.subscription_'.$status, ActorType::Provider, 'paypal', ['plan' => $plan, 'status' => $status]);
            }

            if (! $wasActive && $active) {
                $this->analytics->track('subscription_activated', $workspace->id, null, ['plan' => $plan, 'interval' => $interval]);
            } elseif ($wasActive && ! $active) {
                $this->analytics->track('subscription_canceled', $workspace->id, null, ['plan' => $plan]);
            }

            return $subscription;
        });
    }

    public function cancel(Subscription $subscription, User $user): void
    {
        $response = $this->paypal->post("/v1/billing/subscriptions/{$subscription->provider_subscription_id}/cancel", [
            'reason' => 'Cancelled by the customer from the billing page.',
        ]);

        // 422 means it is already cancelled on PayPal.
        if ($response->failed() && $response->status() !== 422) {
            throw ValidationException::withMessages(['billing' => 'We could not cancel the subscription right now. Please try again or contact support.']);
        }

        $subscription->forceFill(['cancels_at' => $subscription->current_period_ends_at ?? Carbon::now()])->save();
        $this->audit->record($subscription->workspace, 'billing.cancel_requested', ActorType::User, $user);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function workspaceFor(array $data, string $providerId): ?Workspace
    {
        $workspaceId = Arr::get($data, 'custom_id');

        if (is_numeric($workspaceId)) {
            $workspace = Workspace::query()->find((int) $workspaceId);
            if ($workspace !== null) {
                return $workspace;
            }
        }

        return $providerId === '' ? null : Subscription::query()->where('provider_subscription_id', $providerId)->first()?->workspace;
    }

    private function time(mixed $value): ?Carbon
    {
        return is_string($value) && $value !== '' ? Carbon::parse($value)->utc() : null;
    }
}
