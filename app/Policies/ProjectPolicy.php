<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy extends TenantPolicy
{
    public function view(User $user, Project $model): Response
    {
        return $this->inCurrentWorkspace($user, $model->workspace_id);
    }

    public function update(User $user, Project $model): Response
    {
        return $this->inCurrentWorkspace($user, $model->workspace_id);
    }

    public function delete(User $user, Project $model): Response
    {
        return $this->inCurrentWorkspace($user, $model->workspace_id);
    }
}
