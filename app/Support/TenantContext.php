<?php

namespace App\Support;

use App\Models\User;
use App\Models\Workspace;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Resolves the workspace the authenticated user is acting in. Bound as a
 * scoped singleton so one request always uses one workspace.
 */
class TenantContext
{
    private ?Workspace $workspace = null;

    public function set(Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    public function has(): bool
    {
        return $this->workspace !== null;
    }

    public function workspace(): Workspace
    {
        if ($this->workspace === null) {
            throw new HttpException(409, 'No workspace selected.');
        }

        return $this->workspace;
    }

    /**
     * Pick the user's current workspace, falling back to the first one they
     * belong to. Never returns a workspace the user is not a member of.
     */
    public function resolveFor(User $user): ?Workspace
    {
        $workspace = null;

        if ($user->current_workspace_id !== null) {
            $workspace = $user->workspaces()->whereKey($user->current_workspace_id)->first();
        }

        if ($workspace === null) {
            $workspace = $user->workspaces()->orderBy('workspaces.id')->first();

            if ($workspace !== null && $user->current_workspace_id !== $workspace->id) {
                $user->forceFill(['current_workspace_id' => $workspace->id])->save();
            }
        }

        return $workspace;
    }
}
