<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reusable change request / scope text. workspace_id null = system template.
 *
 * @property int $id
 * @property int|null $workspace_id
 * @property string $type
 * @property string $name
 * @property array{title?: string, description?: string, scope_reason?: string, terms_note?: string, body?: string} $content_json
 */
#[Fillable(['type', 'name', 'content_json'])]
class Template extends Model
{
    protected function casts(): array
    {
        return ['content_json' => 'array'];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * System templates plus the workspace's own.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAvailableTo(Builder $query, Workspace $workspace): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('workspace_id')->orWhere('workspace_id', $workspace->id));
    }

    public function isSystem(): bool
    {
        return $this->workspace_id === null;
    }
}
