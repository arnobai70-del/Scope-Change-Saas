<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkspaceService
{
    public function __construct(
        private readonly AuditTrailService $audit,
        private readonly UsageLimitService $usage,
    ) {}

    public function createFor(User $user, ?string $name = null): Workspace
    {
        return DB::transaction(function () use ($user, $name) {
            $name = $name ?: Str::before($user->name, ' ')."'s workspace";

            $workspace = new Workspace(['name' => $name, 'timezone' => $user->timezone ?: 'UTC']);
            $workspace->owner_user_id = $user->id;
            $workspace->slug = $this->uniqueSlug($name);
            $workspace->trial_ends_at = Carbon::now()->addDays((int) config('plans.trial_days', 14));
            $workspace->save();

            $workspace->members()->attach($user->id, ['role' => WorkspaceRole::Owner->value, 'joined_at' => Carbon::now()]);
            $user->forceFill(['current_workspace_id' => $workspace->id])->save();

            $this->audit->record($workspace, 'workspace.created', ActorType::User, $user);

            return $workspace;
        });
    }

    public function invite(Workspace $workspace, User $inviter, string $email, WorkspaceRole $role): WorkspaceInvitation
    {
        $email = mb_strtolower(trim($email));

        if ($role === WorkspaceRole::Owner) {
            throw ValidationException::withMessages(['role' => 'Ownership cannot be granted by invitation.']);
        }

        if ($workspace->members()->where('users.email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'This person is already a member.']);
        }

        $this->usage->ensureCanAddSeat($workspace);

        $token = Str::random(48);

        $invitation = DB::transaction(function () use ($workspace, $inviter, $email, $role, $token) {
            $workspace->invitations()->where('email', $email)->whereNull('accepted_at')->delete();

            /** @var WorkspaceInvitation $invitation */
            $invitation = $workspace->invitations()->create([
                'email' => $email,
                'role' => $role->value,
                'token_hash' => hash('sha256', $token),
                'expires_at' => Carbon::now()->addDays(7),
                'invited_by' => $inviter->id,
            ]);

            $this->audit->record($workspace, 'team.invited', ActorType::User, $inviter, ['role' => $role->value]);

            return $invitation;
        });

        Notification::route('mail', $email)->notify(new WorkspaceInvitationNotification($workspace, $inviter, $token));

        return $invitation;
    }

    public function acceptInvitation(string $token, User $user): Workspace
    {
        $invitation = WorkspaceInvitation::query()->where('token_hash', hash('sha256', $token))->first();

        if ($invitation === null || ! $invitation->isPending()) {
            throw ValidationException::withMessages(['invitation' => 'This invitation is invalid or has expired.']);
        }

        if (! hash_equals($invitation->email, $user->email)) {
            throw ValidationException::withMessages(['invitation' => 'This invitation was sent to a different email address.']);
        }

        return DB::transaction(function () use ($invitation, $user) {
            $workspace = $invitation->workspace;

            if (! $workspace->hasMember($user)) {
                $workspace->members()->attach($user->id, [
                    'role' => $invitation->role,
                    'invited_at' => $invitation->created_at,
                    'joined_at' => Carbon::now(),
                ]);
            }

            $invitation->forceFill(['accepted_at' => Carbon::now()])->save();
            $user->forceFill(['current_workspace_id' => $workspace->id])->save();

            $this->audit->record($workspace, 'team.joined', ActorType::User, $user, ['role' => $invitation->role]);

            return $workspace;
        });
    }

    public function removeMember(Workspace $workspace, User $actor, User $member): void
    {
        if ($member->id === $workspace->owner_user_id) {
            throw ValidationException::withMessages(['member' => 'The workspace owner cannot be removed.']);
        }

        DB::transaction(function () use ($workspace, $actor, $member) {
            $workspace->members()->detach($member->id);

            if ($member->current_workspace_id === $workspace->id) {
                $member->forceFill(['current_workspace_id' => null])->save();
            }

            $this->audit->record($workspace, 'team.removed', ActorType::User, $actor, ['user_id' => $member->id]);
        });
    }

    public function changeRole(Workspace $workspace, User $actor, User $member, WorkspaceRole $role): void
    {
        if ($member->id === $workspace->owner_user_id || $role === WorkspaceRole::Owner) {
            throw ValidationException::withMessages(['role' => 'The owner role cannot be changed here.']);
        }

        $workspace->members()->updateExistingPivot($member->id, ['role' => $role->value]);
        $this->audit->record($workspace, 'team.role_changed', ActorType::User, $actor, ['user_id' => $member->id, 'role' => $role->value]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;

        while (Workspace::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(5));
        }

        return $slug;
    }
}
