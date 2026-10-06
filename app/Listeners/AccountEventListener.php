<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\SecurityNotice;
use App\Services\AnalyticsService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;

/**
 * Account lifecycle listeners, registered through Laravel's event discovery
 * (every handle* method type-hints its event).
 */
class AccountEventListener
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        }
    }

    public function handleRegistered(Registered $event): void
    {
        if ($event->user instanceof User) {
            $this->analytics->track('signup_completed', $event->user->current_workspace_id, $event->user->id);
        }
    }

    public function handleVerified(Verified $event): void
    {
        if ($event->user instanceof User) {
            $this->analytics->track('email_verified', $event->user->current_workspace_id, $event->user->id);
        }
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            $event->user->notify(new SecurityNotice('password_reset'));
        }
    }

    public function handleTwoFactorEnabled(TwoFactorAuthenticationConfirmed $event): void
    {
        $event->user->notify(new SecurityNotice('two_factor_enabled'));
    }

    public function handleTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $event->user->notify(new SecurityNotice('two_factor_disabled'));
    }
}
