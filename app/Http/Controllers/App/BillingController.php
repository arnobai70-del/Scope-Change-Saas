<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\AnalyticsService;
use App\Services\BillingService;
use App\Services\PlanService;
use App\Services\UsageLimitService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class BillingController extends Controller
{
    public function __construct(
        private readonly BillingService $billing,
        private readonly PlanService $plans,
    ) {}

    public function show(Request $request, UsageLimitService $usage, AnalyticsService $analytics): Response
    {
        $workspace = $this->workspace();
        $subscription = $workspace->activeSubscription();
        $analytics->track('upgrade_viewed', $workspace->id, $this->user()->id);

        return Inertia::render('billing/Index', [
            'plan' => $this->plans->effectivePlan($workspace),
            'plans' => collect($this->plans->all())->map(fn ($plan, $key) => [
                'key' => $key,
                'name' => $plan['name'],
                'description' => $plan['description'],
                'monthly' => new Money((int) $plan['monthly_minor'], 'USD'),
                'yearly' => new Money((int) $plan['yearly_minor'], 'USD'),
                'highlights' => $plan['highlights'],
            ])->values(),
            'trialEndsAt' => $workspace->onTrial() ? $workspace->trial_ends_at?->toFormattedDateString() : null,
            'subscription' => $subscription ? [
                'plan' => $subscription->plan,
                'status' => $subscription->status,
                'interval' => $subscription->billing_interval,
                'renews_at' => $subscription->current_period_ends_at?->toFormattedDateString(),
                'cancels_at' => $subscription->cancels_at?->toFormattedDateString(),
            ] : null,
            'usage' => $usage->summary($workspace),
            'invoices' => Transaction::query()->where('workspace_id', $workspace->id)->latest('billed_at')->limit(24)->get()->map(fn (Transaction $t) => [
                'id' => $t->id,
                'number' => $t->invoice_number,
                'status' => $t->status,
                'total' => $t->total(),
                'billed_at' => $t->billed_at?->toFormattedDateString(),
            ]),
            'checkoutConfigured' => $this->billing->isConfigured(),
            'isOwner' => $workspace->owner_user_id === $this->user()->id,
            'checkoutStatus' => $request->query('checkout'),
        ]);
    }

    /**
     * Returns Paddle.js options; plan access is granted later by webhook.
     */
    public function checkout(Request $request): JsonResponse
    {
        $workspace = $this->workspace();
        $this->authorize('billing', $workspace);

        $data = $request->validate([
            'plan' => ['required', 'string'],
            'interval' => ['required', 'in:month,year'],
        ]);

        return response()->json($this->billing->checkoutOptions($workspace, $this->user(), $data['plan'], $data['interval']));
    }

    public function cancel(): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->authorize('billing', $workspace);
        $subscription = $workspace->activeSubscription() ?? abort(404);

        $this->billing->cancel($subscription, $this->user());
        $this->toast('Your subscription will end at the close of the current billing period.');

        return back();
    }

    public function resume(): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->authorize('billing', $workspace);
        $subscription = $workspace->activeSubscription() ?? abort(404);

        $this->billing->resume($subscription, $this->user());
        $this->toast('Subscription resumed.');

        return back();
    }

    public function portal(): SymfonyResponse
    {
        $workspace = $this->workspace();
        $this->authorize('billing', $workspace);
        $subscription = $workspace->activeSubscription() ?? abort(404);

        return Inertia::location($this->billing->portalUrl($subscription));
    }
}
