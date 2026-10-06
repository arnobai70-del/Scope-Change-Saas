<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy extends TenantPolicy
{
    public function view(User $user, Workspace $workspace): Response
    {
        return $workspace->hasMember($user) ? Response::allow() : Response::denyAsNotFound();
    }

    /** Settings, branding, team and data management. */
    public function manage(User $user, Workspace $workspace): Response
    {
        return $this->canManage($user, $workspace);
    }

    /** Billing is owner-only: it controls money and the provider account. */
    public function billing(User $user, Workspace $workspace): Response
    {
        if (! $workspace->hasMember($user)) {
            return Response::denyAsNotFound();
        }

        return $workspace->owner_user_id === $user->id
            ? Response::allow()
            : Response::deny('Only the workspace owner can manage billing.');
    }
}
