<?php

namespace App\Models;

use App\Enums\ScopeItemType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int $baseline_id
 * @property ScopeItemType $type
 * @property string $title
 * @property string|null $detail
 * @property int $sort_order
 * @property-read ScopeBaseline $baseline
 */
#[Fillable(['type', 'title', 'detail', 'sort_order'])]
class ScopeItem extends Model
{
    protected function casts(): array
    {
        return [
            'type' => ScopeItemType::class,
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $guard = function (self $item): void {
            if (ScopeBaseline::query()->whereKey($item->baseline_id)->whereNotNull('locked_at')->exists()) {
                throw new LogicException('Items of a locked scope baseline cannot change.');
            }
        };

        static::creating($guard);
        static::updating($guard);
        static::deleting($guard);
    }

    /** @return BelongsTo<ScopeBaseline, $this> */
    public function baseline(): BelongsTo
    {
        return $this->belongsTo(ScopeBaseline::class, 'baseline_id');
    }
}
