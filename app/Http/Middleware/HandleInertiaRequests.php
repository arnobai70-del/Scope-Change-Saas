<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\PlanService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $tenant = app(TenantContext::class);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
                    'is_admin' => $user->is_admin,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ] : null,
            ],
            'workspace' => function () use ($user, $tenant) {
                if ($user === null) {
                    return null;
                }

                // Account pages (/settings/*) run outside the tenant middleware.
                if (! $tenant->has() && ($workspace = $tenant->resolveFor($user)) !== null) {
                    $tenant->set($workspace);
                }

                return $tenant->has() ? $this->workspaceProps($user, $tenant, app(PlanService::class)) : null;
            },
            'unreadNotifications' => fn () => $user ? $user->unreadNotifications()->count() : 0,
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'upgrade' => fn () => $request->session()->get('upgrade'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function workspaceProps(User $user, TenantContext $tenant, PlanService $plans): array
    {
        $workspace = $tenant->workspace();
        $role = $workspace->roleOf($user);

        return [
            'id' => $workspace->id,
            'name' => $workspace->name,
            'currency' => $workspace->currency,
            'timezone' => $workspace->timezone,
            'brand_color' => $workspace->brand_color,
            'plan' => $plans->effectivePlan($workspace),
            'on_trial' => $workspace->onTrial() && $workspace->activeSubscription() === null,
            'trial_ends_at' => $workspace->trial_ends_at?->toFormattedDateString(),
            'role' => $role?->value,
            'can_manage' => $role?->canManageWorkspace() ?? false,
            'is_owner' => $workspace->owner_user_id === $user->id,
            'all' => $user->workspaces()->orderBy('name')->get(['workspaces.id', 'workspaces.name'])
                ->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])->values(),
        ];
    }
}
