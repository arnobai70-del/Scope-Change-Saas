<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Support\TenantContext;
use Illuminate\Auth\Access\Response;

/**
 * Shared tenant isolation rules. A record is only reachable when it belongs
 * to the workspace the user is currently acting in and the user is a member
 * of it. Failures return 404 so private resources do not leak existence.
 */
abstract class TenantPolicy
{
    public function __construct(protected readonly TenantContext $tenant) {}

    protected function inCurrentWorkspace(User $user, ?int $workspaceId): Response
    {
        if ($workspaceId === null || ! $this->tenant->has()) {
            return Response::denyAsNotFound();
        }

        $workspace = $this->tenant->workspace();

        if ($workspace->id !== $workspaceId || ! $workspace->hasMember($user)) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    protected function canManage(User $user, Workspace $workspace): Response
    {
        $role = $workspace->roleOf($user);

        if ($role === null) {
            return Response::denyAsNotFound();
        }

        return $role->canManageWorkspace()
            ? Response::allow()
            : Response::deny('Only workspace owners and admins can do this.');
    }

    protected function role(User $user, Workspace $workspace): ?WorkspaceRole
    {
        return $workspace->roleOf($user);
    }
}
