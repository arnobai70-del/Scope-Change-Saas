<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $metric
 * @property string $period
 * @property int $value
 */
class UsageCounter extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['value' => 'integer'];
    }
}
