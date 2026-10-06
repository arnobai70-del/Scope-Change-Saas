<?php

namespace App\Services;

use App\Models\Workspace;

/**
 * Resolves the effective plan for a workspace: an active paid subscription
 * wins, then an unexpired trial, then the free plan.
 */
class PlanService
{
    public function effectivePlan(Workspace $workspace): string
    {
        $subscription = $workspace->activeSubscription();

        if ($subscription !== null && $this->exists($subscription->plan)) {
            return $subscription->plan;
        }

        if ($workspace->onTrial()) {
            return (string) config('plans.trial_plan', 'pro');
        }

        return 'free';
    }

    public function exists(string $plan): bool
    {
        return array_key_exists($plan, $this->all());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        /** @var array<string, array<string, mixed>> $plans */
        $plans = config('plans.plans', []);

        return $plans;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(string $plan): array
    {
        return $this->all()[$plan] ?? $this->all()['free'];
    }

    public function limit(Workspace $workspace, string $limit): ?int
    {
        /** @var array<string, int|null> $limits */
        $limits = $this->definition($this->effectivePlan($workspace))['limits'] ?? [];

        $value = $limits[$limit] ?? null;

        if ($limit === 'seats' && $value !== null) {
            $value += $this->extraSeats($workspace);
        }

        return $value;
    }

    public function hasFeature(Workspace $workspace, string $feature): bool
    {
        /** @var array<string, bool> $features */
        $features = $this->definition($this->effectivePlan($workspace))['features'] ?? [];

        return (bool) ($features[$feature] ?? false);
    }

    private function extraSeats(Workspace $workspace): int
    {
        $subscription = $workspace->activeSubscription();

        if ($subscription === null || $subscription->plan !== 'agency') {
            return 0;
        }

        $seatPrice = config('billing.paddle.prices.seat.'.$subscription->billing_interval);

        if (! is_string($seatPrice) || $seatPrice === '') {
            return 0;
        }

        return (int) $subscription->items()->where('provider_price_id', $seatPrice)->sum('quantity');
    }
}
