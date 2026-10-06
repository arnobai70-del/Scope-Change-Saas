<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $workspace_id
 * @property string|null $message_id
 * @property string $template
 * @property string $template_version
 * @property string $recipient
 * @property string $status
 * @property array<string, mixed>|null $metadata_json
 * @property Carbon|null $created_at
 */
class EmailDelivery extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['metadata_json' => 'array'];
    }
}
