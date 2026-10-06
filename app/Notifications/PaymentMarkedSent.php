<?php

namespace App\Notifications;

use App\Notifications\Concerns\ChangeRequestNotification;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentMarkedSent extends ChangeRequestNotification
{
    protected function template(): string
    {
        return 'owner.payment_marked_sent';
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->ownerChannels($notifiable, 'payment_email');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mail()
            ->subject("Client marked payment sent: {$this->changeRequest->reference}")
            ->line('Your client says they sent payment for "'.$this->title().'".')
            ->line('Check your account, then confirm receipt so work can start.')
            ->action('Confirm payment', $this->appUrl());
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return $this->databasePayload('Client marked payment sent for "'.$this->title().'"', 'payment');
    }
}
