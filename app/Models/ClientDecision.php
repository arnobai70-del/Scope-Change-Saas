<?php

namespace App\Models;

use App\Enums\ClientDecisionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $change_request_id
 * @property int $revision_id
 * @property ClientDecisionType $decision
 * @property string $client_name
 * @property string $client_email
 * @property string|null $accepted_terms_version
 * @property string|null $reason
 * @property Carbon $decided_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $metadata_json
 * @property-read ChangeRequestRevision $revision
 */
#[Fillable([
    'change_request_id', 'revision_id', 'decision', 'client_name', 'client_email', 'accepted_terms_version',
    'reason', 'decided_at', 'ip_address', 'user_agent', 'metadata_json',
])]
#[Hidden(['ip_address', 'user_agent'])]
class ClientDecision extends Model
{
    protected function casts(): array
    {
        return [
            'decision' => ClientDecisionType::class,
            'decided_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $decision): void {
            // Only privacy retention pruning (clearing IP / user agent) is allowed.
            $dirty = array_keys($decision->getDirty());
            if (array_diff($dirty, ['ip_address', 'user_agent', 'updated_at']) !== []) {
                throw new LogicException('Client decisions are immutable.');
            }
        });
    }

    /** @return BelongsTo<ChangeRequestRevision, $this> */
    public function revision(): BelongsTo
    {
        return $this->belongsTo(ChangeRequestRevision::class, 'revision_id');
    }

    /** @return BelongsTo<ChangeRequest, $this> */
    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }
}
