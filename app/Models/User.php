<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $timezone
 * @property string $locale
 * @property int|null $current_workspace_id
 * @property bool $is_admin
 * @property string $status
 * @property array<string, bool>|null $notification_preferences
 * @property Carbon|null $last_login_at
 * @property-read Workspace|null $currentWorkspace
 */
#[Fillable(['name', 'email', 'password', 'timezone', 'locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'notification_preferences' => 'array',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'timezone' => 'UTC',
        'locale' => 'en',
        'status' => 'active',
        'is_admin' => false,
        'current_workspace_id' => null,
        'notification_preferences' => null,
        'last_login_at' => null,
    ];

    /**
     * Notification preference defaults; users can opt out per type.
     */
    public const NOTIFICATION_DEFAULTS = [
        'client_viewed_email' => false,
        'client_decided_email' => true,
        'client_questioned_email' => true,
        'payment_email' => true,
        'expired_email' => true,
        'weekly_digest' => false,
    ];

    /**
     * Emails are compared case-insensitively.
     */
    protected function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = mb_strtolower(trim($value));
    }

    /** @return BelongsToMany<Workspace, $this> */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->withPivot(['role', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    /** @return BelongsTo<Workspace, $this> */
    public function currentWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    public function wantsNotification(string $key): bool
    {
        $prefs = array_merge(self::NOTIFICATION_DEFAULTS, $this->notification_preferences ?? []);

        return (bool) ($prefs[$key] ?? false);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }
}
