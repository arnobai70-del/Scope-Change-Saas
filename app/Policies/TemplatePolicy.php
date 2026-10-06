<?php

namespace App\Policies;

use App\Models\Template;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TemplatePolicy extends TenantPolicy
{
    public function view(User $user, Template $template): Response
    {
        return $template->isSystem() ? Response::allow() : $this->inCurrentWorkspace($user, $template->workspace_id);
    }

    /** System templates are read-only. */
    public function update(User $user, Template $template): Response
    {
        return $template->isSystem() ? Response::deny('System templates cannot be edited.') : $this->inCurrentWorkspace($user, $template->workspace_id);
    }

    public function delete(User $user, Template $template): Response
    {
        return $this->update($user, $template);
    }
}
