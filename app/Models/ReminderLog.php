<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $change_request_id
 * @property string $kind
 * @property Carbon $sent_at
 */
#[Fillable(['change_request_id', 'kind', 'sent_at'])]
class ReminderLog extends Model
{
    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}
