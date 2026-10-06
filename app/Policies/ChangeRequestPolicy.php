<?php

namespace App\Policies;

use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChangeRequestPolicy extends TenantPolicy
{
    public function view(User $user, ChangeRequest $model): Response
    {
        return $this->inCurrentWorkspace($user, $model->workspace_id);
    }

    public function update(User $user, ChangeRequest $model): Response
    {
        return $this->inCurrentWorkspace($user, $model->workspace_id);
    }

    public function delete(User $user, ChangeRequest $model): Response
    {
        return $this->inCurrentWorkspace($user, $model->workspace_id);
    }
}
