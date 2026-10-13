<?php

namespace App\Console\Commands;

use App\Enums\ActorType;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\WorkspaceService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('app:make-admin {email : Email address of the admin} {--name= : Name, when creating a new user}')]
#[Description('Create a platform admin, or promote an existing user to admin')]
class MakeAdmin extends Command
{
    public function handle(WorkspaceService $workspaces, AuditTrailService $audit): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']])->fails()) {
            $this->error('Please give a valid email address.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $name = (string) ($this->option('name') ?: $this->ask('Name', 'Admin'));
            $password = (string) $this->secret('Password (input hidden)');

            $check = Validator::make(['password' => $password], ['password' => ['required', Password::defaults()]]);
            if ($check->fails()) {
                $this->error($check->errors()->first('password'));

                return self::FAILURE;
            }

            $user = User::query()->create(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $workspaces->createFor($user, 'Admin workspace')->forceFill(['onboarded_at' => now()])->save();
            $this->info("Created {$email}.");
        }

        $user->forceFill(['is_admin' => true, 'status' => 'active'])->save();
        $audit->record($user, 'admin.granted', ActorType::System, 'console');

        $this->info("{$email} is now a platform admin. Sign in and open /admin.");

        return self::SUCCESS;
    }
}
