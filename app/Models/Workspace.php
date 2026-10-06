<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $owner_user_id
 * @property string $name
 * @property string $slug
 * @property string $currency
 * @property string $timezone
 * @property string|null $service_type
 * @property string $brand_color
 * @property string|null $logo_path
 * @property string $plan
 * @property Carbon|null $trial_ends_at
 * @property int $default_expiry_days
 * @property bool $reminders_enabled
 * @property string|null $default_payment_instructions
 * @property string|null $default_payment_url
 * @property int $change_request_sequence
 * @property Carbon|null $onboarded_at
 * @property Carbon|null $suspended_at
 * @property string|null $suspension_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'currency', 'timezone', 'service_type', 'brand_color', 'default_expiry_days',
    'reminders_enabled', 'default_payment_instructions', 'default_payment_url',
])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'USD',
        'timezone' => 'UTC',
        'brand_color' => '#4f46e5',
        'plan' => 'free',
        'default_expiry_days' => 7,
        'reminders_enabled' => true,
        'change_request_sequence' => 0,
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'onboarded_at' => 'datetime',
            'suspended_at' => 'datetime',
            'reminders_enabled' => 'boolean',
            'default_expiry_days' => 'integer',
            'change_request_sequence' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot(['role', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    /** @return HasMany<WorkspaceInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }

    /** @return HasMany<Client, $this> */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** @return HasMany<ChangeRequest, $this> */
    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class);
    }

    /** @return HasMany<Template, $this> */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function roleOf(User $user): ?WorkspaceRole
    {
        /** @var User|null $member */
        $member = $this->members()->whereKey($user->id)->first();

        if ($member === null) {
            return null;
        }

        /** @var object{role: string} $pivot */
        $pivot = $member->getRelationValue('pivot');

        return WorkspaceRole::tryFrom($pivot->role);
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->whereKey($user->id)->exists();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->whereIn('status', Subscription::ACTIVE_STATUSES)
            ->latest('id')
            ->first();
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
