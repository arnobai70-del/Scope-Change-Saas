<?php

namespace App\Models;

use App\Enums\ActorType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $change_request_id
 * @property int|null $revision_id
 * @property ActorType $actor_type
 * @property int|null $actor_id
 * @property string|null $actor_name
 * @property string|null $actor_email
 * @property string $body
 * @property string $visibility
 * @property Carbon|null $created_at
 */
#[Fillable(['revision_id', 'actor_type', 'actor_id', 'actor_name', 'actor_email', 'body', 'visibility'])]
class ChangeComment extends Model
{
    protected function casts(): array
    {
        return ['actor_type' => ActorType::class];
    }

    /** @return BelongsTo<ChangeRequest, $this> */
    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function isInternal(): bool
    {
        return $this->visibility === 'internal';
    }
}
