<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $provider
 * @property string $provider_subscription_id
 * @property string|null $provider_customer_id
 * @property string $plan
 * @property string $billing_interval
 * @property string $status
 * @property int $quantity
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $current_period_ends_at
 * @property Carbon|null $cancels_at
 * @property Carbon|null $canceled_at
 * @property Carbon|null $provider_updated_at
 * @property-read Workspace $workspace
 */
class Subscription extends Model
{
    /** Statuses that grant paid plan access. past_due keeps access during dunning. */
    public const ACTIVE_STATUSES = ['active', 'trialing', 'past_due'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'cancels_at' => 'datetime',
            'canceled_at' => 'datetime',
            'provider_updated_at' => 'datetime',
            'quantity' => 'integer',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<SubscriptionItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function onGracePeriod(): bool
    {
        return $this->cancels_at !== null && $this->cancels_at->isFuture();
    }
}
