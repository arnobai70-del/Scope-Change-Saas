<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClientPolicy extends TenantPolicy
{
    public function view(User $user, Client $model): Response
    {
        return $this->inCurrentWorkspace($user, $model->workspace_id);
    }

    public function update(User $user, Client $model): Response
    {
        return $this->inCurrentWorkspace($user, $model->workspace_id);
    }

    public function delete(User $user, Client $model): Response
    {
        return $this->inCurrentWorkspace($user, $model->workspace_id);
    }
}
