<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\WorkspaceService;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant for authenticated app routes. Users without a
 * workspace (e.g. removed from their only team) get a fresh one.
 */
class EnsureWorkspace
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly WorkspaceService $workspaces,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isSuspended()) {
            abort(403, 'This account is suspended. Contact support for help.');
        }

        $workspace = $this->tenant->resolveFor($user) ?? $this->workspaces->createFor($user);

        $this->tenant->set($workspace);

        if ($workspace->isSuspended()) {
            return Inertia::render('errors/Suspended', ['reason' => $workspace->suspension_reason])
                ->toResponse($request)
                ->setStatusCode(403);
        }

        if ($workspace->onboarded_at === null && ! $request->routeIs('onboarding.*', 'workspaces.switch')) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
