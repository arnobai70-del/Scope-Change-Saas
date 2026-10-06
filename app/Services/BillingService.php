<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Paddle Billing integration for the SaaS subscription. Checkout happens in
 * Paddle's hosted overlay; plan access only changes when a signed webhook
 * arrives, never from the frontend success redirect.
 */
class BillingService
{
    public function __construct(
        private readonly AuditTrailService $audit,
        private readonly AnalyticsService $analytics,
        private readonly PlanService $plans,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('billing.paddle.client_side_token')) && filled(config('billing.paddle.webhook_secret'));
    }

    public function priceId(string $plan, string $interval): ?string
    {
        $price = config("billing.paddle.prices.{$plan}.{$interval}");

        return is_string($price) && $price !== '' ? $price : null;
    }

    /**
     * Find the plan + interval a Paddle price belongs to.
     *
     * @return array{plan: string, interval: string}|null
     */
    public function planForPrice(string $priceId): ?array
    {
        /** @var array<string, array<string, string|null>> $prices */
        $prices = config('billing.paddle.prices', []);

        foreach ($prices as $plan => $intervals) {
            if ($plan === 'seat') {
                continue;
            }

            foreach ($intervals as $interval => $id) {
                if ($id !== null && $id !== '' && hash_equals($id, $priceId)) {
                    return ['plan' => $plan, 'interval' => $interval];
                }
            }
        }

        return null;
    }

    /**
     * Options for Paddle.js Checkout.open().
     *
     * @return array<string, mixed>
     */
    public function checkoutOptions(Workspace $workspace, User $user, string $plan, string $interval): array
    {
        if (! $this->plans->exists($plan) || $plan === 'free' || ! in_array($interval, ['month', 'year'], true)) {
            throw ValidationException::withMessages(['plan' => 'Choose a valid paid plan.']);
        }

        $priceId = $this->priceId($plan, $interval);

        if (! $this->isConfigured() || $priceId === null) {
            throw ValidationException::withMessages(['plan' => 'Online checkout is not configured yet. Please contact support.']);
        }

        if ($workspace->activeSubscription() !== null) {
            throw ValidationException::withMessages(['plan' => 'This workspace already has a subscription. Use Manage billing to change plans.']);
        }

        $this->analytics->track('checkout_started', $workspace->id, $user->id, ['plan' => $plan, 'interval' => $interval]);

        return [
            'environment' => config('billing.paddle.sandbox') ? 'sandbox' : 'production',
            'token' => config('billing.paddle.client_side_token'),
            'items' => [['priceId' => $priceId, 'quantity' => 1]],
            'customer' => ['email' => $user->email],
            'customData' => ['workspace_id' => (string) $workspace->id],
            'settings' => [
                'successUrl' => route('billing.show', ['checkout' => 'success']),
                'displayMode' => 'overlay',
            ],
        ];
    }

    public function cancel(Subscription $subscription, User $user): void
    {
        $response = $this->api()->post("/subscriptions/{$subscription->provider_subscription_id}/cancel", [
            'effective_from' => 'next_billing_period',
        ]);

        if ($response->failed()) {
            throw ValidationException::withMessages(['billing' => 'We could not cancel the subscription right now. Please try again or contact support.']);
        }

        $subscription->forceFill(['cancels_at' => $subscription->current_period_ends_at ?? Carbon::now()])->save();
        $this->audit->record($subscription->workspace, 'billing.cancel_requested', ActorType::User, $user);
    }

    public function resume(Subscription $subscription, User $user): void
    {
        $response = $this->api()->patch("/subscriptions/{$subscription->provider_subscription_id}", [
            'scheduled_change' => null,
        ]);

        if ($response->failed()) {
            throw ValidationException::withMessages(['billing' => 'We could not resume the subscription right now. Please try again.']);
        }

        $subscription->forceFill(['cancels_at' => null])->save();
        $this->audit->record($subscription->workspace, 'billing.resumed', ActorType::User, $user);
    }

    /**
     * Provider-hosted customer portal for invoices, payment methods and
     * plan changes.
     */
    public function portalUrl(Subscription $subscription): string
    {
        if ($subscription->provider_customer_id === null) {
            throw ValidationException::withMessages(['billing' => 'No billing customer is linked to this workspace yet.']);
        }

        $response = $this->api()->post("/customers/{$subscription->provider_customer_id}/portal-sessions", [
            'subscription_ids' => [$subscription->provider_subscription_id],
        ]);

        $url = $response->json('data.urls.general.overview');

        if ($response->failed() || ! is_string($url)) {
            throw ValidationException::withMessages(['billing' => 'The billing portal is unavailable right now. Please try again.']);
        }

        return $url;
    }

    private function api(): PendingRequest
    {
        $key = config('billing.paddle.api_key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('PADDLE_API_KEY is not configured.');
        }

        $base = config('billing.paddle.sandbox') ? 'https://sandbox-api.paddle.com' : 'https://api.paddle.com';

        return Http::baseUrl($base)->withToken($key)->acceptJson()->timeout(15)->retry(2, 500, throw: false);
    }
}
