<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $workspace_id
 * @property int|null $user_id
 * @property string $event
 * @property array<string, int|float|string|bool|null>|null $properties
 * @property Carbon $occurred_at
 */
class AnalyticsEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
