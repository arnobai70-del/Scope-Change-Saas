<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\WorkspaceService;
use Illuminate\Http\RedirectResponse;

class InvitationController extends Controller
{
    public function __invoke(string $token, WorkspaceService $workspaces): RedirectResponse
    {
        $workspace = $workspaces->acceptInvitation($token, $this->user());
        $this->toast("You joined {$workspace->name}.");

        return to_route('dashboard');
    }
}
