<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $provider
 * @property string $external_id
 * @property string $event_type
 * @property string $payload_hash
 * @property array<string, mixed> $payload
 * @property string $status
 * @property int $attempts
 * @property string|null $error
 * @property Carbon|null $occurred_at
 * @property Carbon|null $processed_at
 * @property Carbon|null $created_at
 */
class WebhookEvent extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['payload'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'processed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
