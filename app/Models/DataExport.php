<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int|null $requested_by
 * @property string $type
 * @property string $status
 * @property string|null $file_path
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 */
#[Fillable(['type', 'status', 'requested_by'])]
class DataExport extends Model
{
    use BelongsToWorkspace;

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }
}
