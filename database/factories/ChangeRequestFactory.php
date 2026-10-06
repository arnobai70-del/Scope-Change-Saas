<?php

namespace Database\Factories;

use App\Enums\ChangeRequestStatus;
use App\Models\ChangeRequest;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Low-level factory. Tests should prefer ChangeRequestService::createDraft()
 * so revisions, references and audit events are created consistently.
 *
 * @extends Factory<ChangeRequest>
 */
class ChangeRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'workspace_id' => fn (array $attributes) => Project::query()->whereKey($attributes['project_id'])->value('workspace_id'),
            'public_id' => (string) Str::ulid(),
            'reference' => 'CR-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'status' => ChangeRequestStatus::Draft,
        ];
    }
}
