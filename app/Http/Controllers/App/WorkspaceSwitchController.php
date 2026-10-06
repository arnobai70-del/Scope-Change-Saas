<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;

class WorkspaceSwitchController extends Controller
{
    public function __invoke(Workspace $workspace): RedirectResponse
    {
        $this->authorize('view', $workspace);

        $this->user()->forceFill(['current_workspace_id' => $workspace->id])->save();

        return to_route('dashboard');
    }
}
