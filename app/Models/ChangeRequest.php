<?php

namespace App\Models;

use App\Enums\ChangeRequestStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\ChangeRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $project_id
 * @property int|null $created_by
 * @property string $public_id
 * @property string $reference
 * @property int|null $current_revision_id
 * @property ChangeRequestStatus $status
 * @property string|null $recipient_name
 * @property string|null $recipient_email
 * @property Carbon|null $expires_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $first_viewed_at
 * @property Carbon|null $last_viewed_at
 * @property Carbon|null $decided_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $reminders_muted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read ChangeRequestRevision|null $currentRevision
 */
#[Fillable(['project_id', 'recipient_name', 'recipient_email', 'expires_at'])]
class ChangeRequest extends Model
{
    use BelongsToWorkspace;

    /** @use HasFactory<ChangeRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ChangeRequestStatus::class,
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'first_viewed_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'decided_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'reminders_muted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<ChangeRequestRevision, $this> */
    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(ChangeRequestRevision::class, 'current_revision_id');
    }

    /** @return HasMany<ChangeRequestRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(ChangeRequestRevision::class)->orderBy('revision_no');
    }

    /** @return HasMany<ApprovalLink, $this> */
    public function approvalLinks(): HasMany
    {
        return $this->hasMany(ApprovalLink::class);
    }

    /** @return HasMany<ClientDecision, $this> */
    public function decisions(): HasMany
    {
        return $this->hasMany(ClientDecision::class);
    }

    /** @return HasMany<ChangeComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(ChangeComment::class)->orderBy('created_at')->orderBy('id');
    }

    /** @return HasMany<PaymentRecord, $this> */
    public function paymentRecords(): HasMany
    {
        return $this->hasMany(PaymentRecord::class);
    }

    /** @return HasMany<ProofPack, $this> */
    public function proofPacks(): HasMany
    {
        return $this->hasMany(ProofPack::class)->orderByDesc('version');
    }

    /** @return HasMany<ReminderLog, $this> */
    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }

    /** @return HasOne<ApprovalLink, $this> */
    public function activeLink(): HasOne
    {
        return $this->hasOne(ApprovalLink::class)->whereNull('revoked_at')->latestOfMany();
    }

    /**
     * Payment record for the current revision, if one is tracked.
     */
    public function currentPayment(): ?PaymentRecord
    {
        if ($this->current_revision_id === null) {
            return null;
        }

        return $this->paymentRecords()->where('revision_id', $this->current_revision_id)->first();
    }

    public function currentDecision(): ?ClientDecision
    {
        if ($this->current_revision_id === null) {
            return null;
        }

        return $this->decisions()->where('revision_id', $this->current_revision_id)->first();
    }

    public function isExpiredByDate(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
