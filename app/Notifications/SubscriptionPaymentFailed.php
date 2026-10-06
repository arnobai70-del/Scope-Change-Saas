<?php

namespace App\Notifications;

use App\Models\Workspace;
use App\Notifications\Concerns\AppNotification;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionPaymentFailed extends AppNotification
{
    public function __construct(public readonly Workspace $workspace)
    {
        parent::__construct();
    }

    protected function template(): string
    {
        return 'owner.subscription_failed';
    }

    protected function workspaceId(): ?int
    {
        return $this->workspace->id;
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mail()
            ->subject('Subscription payment failed')
            ->line("We couldn't collect the latest subscription payment for {$this->workspace->name}.")
            ->line('Please update your payment method to keep your plan active.')
            ->action('Manage billing', route('billing.show'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['kind' => 'billing', 'headline' => 'Subscription payment failed', 'url' => route('billing.show')];
    }
}
