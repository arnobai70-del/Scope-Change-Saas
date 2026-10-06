<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $project_id
 * @property int $version
 * @property string $title
 * @property string|null $summary
 * @property Carbon|null $locked_at
 * @property array<string, mixed>|null $content_json
 * @property string|null $content_hash
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property-read Project $project
 * @property-read Collection<int, ScopeItem> $items
 */
#[Fillable(['version', 'title', 'summary', 'created_by'])]
class ScopeBaseline extends Model
{
    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
            'content_json' => 'array',
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $baseline): void {
            if ($baseline->getOriginal('locked_at') !== null) {
                throw new LogicException('Locked scope baselines are immutable. Create a new version instead.');
            }
        });

        static::deleting(function (self $baseline): void {
            if ($baseline->locked_at !== null) {
                throw new LogicException('Locked scope baselines cannot be deleted.');
            }
        });
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<ScopeItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ScopeItem::class, 'baseline_id')->orderBy('sort_order')->orderBy('id');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }
}
