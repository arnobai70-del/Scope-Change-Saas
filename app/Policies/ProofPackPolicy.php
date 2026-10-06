<?php

namespace App\Policies;

use App\Models\ProofPack;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProofPackPolicy extends TenantPolicy
{
    public function view(User $user, ProofPack $model): Response
    {
        return $this->inCurrentWorkspace($user, $model->workspace_id);
    }

    public function update(User $user, ProofPack $model): Response
    {
        return $this->inCurrentWorkspace($user, $model->workspace_id);
    }

    public function delete(User $user, ProofPack $model): Response
    {
        return $this->inCurrentWorkspace($user, $model->workspace_id);
    }
}
