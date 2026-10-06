<?php

namespace App\Models\Concerns;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant-owned model. Every query from the application layer is expected to
 * go through forWorkspace() and every write is checked by a policy.
 *
 * @property int $workspace_id
 * @property-read Workspace $workspace
 */
trait BelongsToWorkspace
{
    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForWorkspace(Builder $query, Workspace|int $workspace): Builder
    {
        return $query->where($this->qualifyColumn('workspace_id'), $workspace instanceof Workspace ? $workspace->id : $workspace);
    }
}
