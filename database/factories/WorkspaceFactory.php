<?php

namespace Database\Factories;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'owner_user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'currency' => 'USD',
            'timezone' => 'UTC',
            'plan' => 'free',
            'trial_ends_at' => now()->addDays(14),
            'onboarded_at' => now(),
            'default_expiry_days' => 7,
            'reminders_enabled' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Workspace $workspace) {
            $workspace->members()->syncWithoutDetaching([$workspace->owner_user_id => ['role' => WorkspaceRole::Owner->value, 'joined_at' => now()]]);
            $workspace->owner->forceFill(['current_workspace_id' => $workspace->id])->save();
        });
    }

    public function free(): static
    {
        return $this->state(fn () => ['trial_ends_at' => null]);
    }

    public function notOnboarded(): static
    {
        return $this->state(fn () => ['onboarded_at' => null]);
    }
}
