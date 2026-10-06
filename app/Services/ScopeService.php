<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Enums\ScopeItemType;
use App\Models\Project;
use App\Models\ScopeBaseline;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Versioned scope baselines. An unlocked baseline is a working draft; once
 * locked it is frozen and further edits create a new version, preserving
 * the history clients agreed to.
 */
class ScopeService
{
    public function __construct(
        private readonly AuditTrailService $audit,
        private readonly AnalyticsService $analytics,
    ) {}

    /**
     * Save scope content. Edits the current draft baseline, or creates a new
     * version when the latest baseline is locked (or none exists).
     *
     * @param  array{title: string, summary?: string|null, items: list<array{type: string, title: string, detail?: string|null}>}  $data
     */
    public function save(Project $project, array $data, User $user): ScopeBaseline
    {
        return DB::transaction(function () use ($project, $data, $user) {
            $current = $project->currentBaseline();

            if ($current === null || $current->isLocked()) {
                /** @var ScopeBaseline $baseline */
                $baseline = $project->baselines()->create([
                    'version' => ($current->version ?? 0) + 1,
                    'title' => $data['title'],
                    'summary' => $data['summary'] ?? null,
                    'created_by' => $user->id,
                ]);

                $this->audit->record($project, 'scope.version_created', ActorType::User, $user, [
                    'version' => $baseline->version,
                    'previous_version' => $current?->version,
                ]);
            } else {
                $baseline = $current;
                $baseline->fill(['title' => $data['title'], 'summary' => $data['summary'] ?? null])->save();
                $baseline->items()->delete();
            }

            foreach ($data['items'] as $index => $item) {
                $baseline->items()->create([
                    'type' => ScopeItemType::from($item['type']),
                    'title' => $item['title'],
                    'detail' => $item['detail'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            return $baseline->refresh();
        });
    }

    public function lock(Project $project, User $user): ScopeBaseline
    {
        $baseline = $project->currentBaseline();

        if ($baseline === null || $baseline->items()->count() === 0) {
            throw ValidationException::withMessages(['scope' => 'Add at least one scope item before locking the baseline.']);
        }

        if ($baseline->isLocked()) {
            return $baseline;
        }

        DB::transaction(function () use ($baseline, $project, $user) {
            $content = $this->content($baseline);

            $baseline->forceFill([
                'locked_at' => Carbon::now(),
                'content_json' => $content,
                'content_hash' => hash('sha256', (string) json_encode($content)),
            ])->save();

            $this->audit->record($project, 'scope.locked', ActorType::User, $user, [
                'version' => $baseline->version,
                'content_hash' => $baseline->content_hash,
            ]);
        });

        $this->analytics->track('scope_locked', $project->workspace_id, $user->id, ['project_id' => $project->id]);

        return $baseline;
    }

    /**
     * @return array<string, mixed>
     */
    public function content(ScopeBaseline $baseline): array
    {
        return [
            'version' => $baseline->version,
            'title' => $baseline->title,
            'summary' => $baseline->summary,
            'items' => $baseline->items()->get()->map(fn ($item) => [
                'id' => $item->id,
                'type' => $item->type->value,
                'title' => $item->title,
                'detail' => $item->detail,
            ])->values()->all(),
        ];
    }
}
