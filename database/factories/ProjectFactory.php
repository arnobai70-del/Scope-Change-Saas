<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'workspace_id' => fn (array $attributes) => Client::query()->whereKey($attributes['client_id'])->value('workspace_id'),
            'title' => rtrim(fake()->sentence(3), '.'),
            'currency' => 'USD',
            'base_amount_minor' => 500000,
            'status' => ProjectStatus::Active,
        ];
    }
}
