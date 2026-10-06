<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\Concerns\BelongsToWorkspace;
use App\Support\Money;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $client_id
 * @property string $title
 * @property string|null $code
 * @property int $base_amount_minor
 * @property string $currency
 * @property ProjectStatus $status
 * @property int|null $revision_allowance
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Client $client
 */
#[Fillable(['client_id', 'title', 'code', 'base_amount_minor', 'currency', 'status', 'revision_allowance', 'starts_on', 'ends_on'])]
class Project extends Model
{
    use BelongsToWorkspace;

    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'base_amount_minor' => 'integer',
            'revision_allowance' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return HasMany<ScopeBaseline, $this> */
    public function baselines(): HasMany
    {
        return $this->hasMany(ScopeBaseline::class)->orderByDesc('version');
    }

    /** @return HasMany<ChangeRequest, $this> */
    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class);
    }

    /**
     * The newest baseline version, locked or not.
     */
    public function currentBaseline(): ?ScopeBaseline
    {
        return $this->baselines()->first();
    }

    /**
     * The newest locked baseline: the agreed scope clients see.
     */
    public function lockedBaseline(): ?ScopeBaseline
    {
        return $this->baselines()->whereNotNull('locked_at')->first();
    }

    public function baseAmount(): Money
    {
        return new Money($this->base_amount_minor, $this->currency);
    }
}
