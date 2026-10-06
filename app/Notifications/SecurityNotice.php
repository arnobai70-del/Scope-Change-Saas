<?php

namespace App\Notifications;

use App\Notifications\Concerns\AppNotification;
use Illuminate\Notifications\Messages\MailMessage;

class SecurityNotice extends AppNotification
{
    public function __construct(public readonly string $event)
    {
        parent::__construct();
    }

    protected function template(): string
    {
        return 'security.'.$this->event;
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $what = match ($this->event) {
            'password_changed' => 'Your password was changed.',
            'password_reset' => 'Your password was reset.',
            'two_factor_enabled' => 'Two-factor authentication was turned on.',
            'two_factor_disabled' => 'Two-factor authentication was turned off.',
            default => 'A security setting on your account changed.',
        };

        return $this->mail()
            ->subject('Security notice for your account')
            ->line($what)
            ->line('If this was you, no action is needed. If not, reset your password immediately and contact support.')
            ->action('Review security settings', route('security.edit'));
    }
}
