<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $change_request_id
 * @property int $revision_id
 * @property string $token_hash
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property string $access_policy
 * @property Carbon|null $last_viewed_at
 * @property-read ChangeRequest $changeRequest
 * @property-read ChangeRequestRevision $revision
 */
#[Fillable(['revision_id', 'token_hash', 'expires_at', 'access_policy'])]
class ApprovalLink extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_viewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ChangeRequest, $this> */
    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    /** @return BelongsTo<ChangeRequestRevision, $this> */
    public function revision(): BelongsTo
    {
        return $this->belongsTo(ChangeRequestRevision::class, 'revision_id');
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
