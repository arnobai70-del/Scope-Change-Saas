<?php

namespace App\Models;

use App\Enums\PaymentRule;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * A commercial version of a change request. Once locked (sent to the
 * client) a revision is immutable: edits create revision N+1.
 *
 * @property int $id
 * @property int $change_request_id
 * @property int $revision_no
 * @property string $title
 * @property string $description
 * @property string|null $scope_reason
 * @property string|null $scope_excerpt
 * @property int $price_minor
 * @property string $currency
 * @property array{type: string, value?: int|string|null, note?: string|null} $timeline_json
 * @property PaymentRule $payment_rule
 * @property string|null $payment_url
 * @property string|null $payment_instructions
 * @property string|null $terms_note
 * @property string $terms_version
 * @property array<string, mixed>|null $snapshot_json
 * @property string|null $snapshot_hash
 * @property Carbon|null $locked_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property-read ChangeRequest $changeRequest
 * @property-read ClientDecision|null $decision
 * @property-read Collection<int, ScopeItem> $scopeItems
 */
#[Fillable([
    'revision_no', 'title', 'description', 'scope_reason', 'scope_excerpt', 'price_minor', 'currency',
    'timeline_json', 'payment_rule', 'payment_url', 'payment_instructions', 'terms_note', 'created_by',
])]
class ChangeRequestRevision extends Model
{
    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'revision_no' => 'integer',
            'timeline_json' => 'array',
            'snapshot_json' => 'array',
            'payment_rule' => PaymentRule::class,
            'locked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // No silent mutation: a locked revision can never change commercially.
        static::updating(function (self $revision): void {
            if ($revision->getOriginal('locked_at') !== null) {
                throw new LogicException('Locked change request revisions are immutable. Create a new revision instead.');
            }
        });

        static::deleting(function (self $revision): void {
            if ($revision->locked_at !== null) {
                throw new LogicException('Locked change request revisions cannot be deleted.');
            }
        });
    }

    /** @return BelongsTo<ChangeRequest, $this> */
    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    /** @return HasOne<ClientDecision, $this> */
    public function decision(): HasOne
    {
        return $this->hasOne(ClientDecision::class, 'revision_id');
    }

    /** @return BelongsToMany<ScopeItem, $this> */
    public function scopeItems(): BelongsToMany
    {
        return $this->belongsToMany(ScopeItem::class, 'change_scope_links', 'revision_id', 'scope_item_id')
            ->withPivot('relation_type')
            ->withTimestamps();
    }

    public function price(): Money
    {
        return new Money($this->price_minor, $this->currency);
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function timelineLabel(): string
    {
        $timeline = $this->timeline_json;
        $value = $timeline['value'] ?? null;
        $note = isset($timeline['note']) && $timeline['note'] !== '' ? ' — '.$timeline['note'] : '';

        return match ($timeline['type']) {
            'days' => sprintf('+%d %s', (int) $value, (int) $value === 1 ? 'day' : 'days').$note,
            'weeks' => sprintf('+%d %s', (int) $value, (int) $value === 1 ? 'week' : 'weeks').$note,
            'date' => 'New delivery date: '.(is_string($value) ? Carbon::parse($value)->toFormattedDateString() : '—').$note,
            default => 'No timeline impact'.$note,
        };
    }
}
