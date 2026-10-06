<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only audit record. Each event stores the hash of the previous
 * event for the same entity, forming a tamper-evident chain.
 *
 * @property int $id
 * @property int|null $workspace_id
 * @property string $actor_type
 * @property string|null $actor_id
 * @property string|null $actor_label
 * @property string $entity_type
 * @property int $entity_id
 * @property string $event
 * @property array<string, mixed>|null $metadata_json
 * @property string|null $previous_hash
 * @property string $hash
 * @property Carbon $occurred_at
 */
class AuditEvent extends Model
{
    public $timestamps = false;

    /** Microsecond precision keeps the hash chain verifiable after a round trip. */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit events are append-only.'));
        static::deleting(fn () => throw new LogicException('Audit events are append-only.'));
    }
}
