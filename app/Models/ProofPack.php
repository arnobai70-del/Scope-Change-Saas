<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $change_request_id
 * @property int $version
 * @property string $status
 * @property string|null $file_path
 * @property string|null $checksum
 * @property string|null $content_hash
 * @property int|null $requested_by
 * @property Carbon|null $generated_at
 * @property string|null $error
 * @property Carbon|null $created_at
 * @property-read ChangeRequest $changeRequest
 */
#[Fillable(['change_request_id', 'version', 'status', 'requested_by'])]
class ProofPack extends Model
{
    use BelongsToWorkspace;

    protected function casts(): array
    {
        return ['generated_at' => 'datetime', 'version' => 'integer'];
    }

    /** @return BelongsTo<ChangeRequest, $this> */
    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && $this->file_path !== null;
    }
}
