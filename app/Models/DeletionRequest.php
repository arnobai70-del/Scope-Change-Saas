<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $workspace_id
 * @property int|null $user_id
 * @property string $scope
 * @property string $status
 * @property string|null $reason
 * @property Carbon|null $processed_at
 */
#[Fillable(['workspace_id', 'user_id', 'scope', 'reason'])]
class DeletionRequest extends Model
{
    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }
}
