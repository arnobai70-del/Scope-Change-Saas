<?php

namespace App\Http\Controllers\App;

use App\Enums\WorkspaceRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Services\PlanService;
use App\Services\UsageLimitService;
use App\Services\WorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(private readonly WorkspaceService $workspaces) {}

    public function index(PlanService $plans, UsageLimitService $usage): Response
    {
        $workspace = $this->workspace();
        $role = $workspace->roleOf($this->user());

        return Inertia::render('team/Index', [
            'members' => $workspace->members()->orderBy('name')->get()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->getRelationValue('pivot')->role,
                'is_owner' => $u->id === $workspace->owner_user_id,
            ]),
            'invitations' => $workspace->invitations()->whereNull('accepted_at')->where('expires_at', '>', now())->get()
                ->map(fn (WorkspaceInvitation $i) => ['id' => $i->id, 'email' => $i->email, 'role' => $i->role, 'expires_at' => $i->expires_at->toFormattedDateString()]),
            'canManage' => $role?->canManageWorkspace() ?? false,
            'teamEnabled' => $plans->hasFeature($workspace, 'team'),
            'seats' => $usage->summary($workspace)['seats'],
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->authorize('manage', $workspace);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in([WorkspaceRole::Admin->value, WorkspaceRole::Member->value])],
        ]);

        $this->workspaces->invite($workspace, $this->user(), $data['email'], WorkspaceRole::from($data['role']));
        $this->toast('Invitation sent.');

        return back();
    }

    public function cancelInvitation(WorkspaceInvitation $invitation): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->authorize('manage', $workspace);
        abort_unless($invitation->workspace_id === $workspace->id, 404);

        $invitation->delete();
        $this->toast('Invitation cancelled.');

        return back();
    }

    public function updateRole(Request $request, User $member): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->authorize('manage', $workspace);
        abort_unless($workspace->hasMember($member), 404);

        $data = $request->validate(['role' => ['required', Rule::in([WorkspaceRole::Admin->value, WorkspaceRole::Member->value])]]);
        $this->workspaces->changeRole($workspace, $this->user(), $member, WorkspaceRole::from($data['role']));
        $this->toast('Role updated.');

        return back();
    }

    public function remove(User $member): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->authorize('manage', $workspace);
        abort_unless($workspace->hasMember($member), 404);

        $this->workspaces->removeMember($workspace, $this->user(), $member);
        $this->toast('Member removed.');

        return back();
    }
}
