<?php

namespace App\Notifications;

use App\Notifications\Concerns\ChangeRequestNotification;
use Illuminate\Notifications\Messages\MailMessage;

class ClientViewedRequest extends ChangeRequestNotification
{
    protected function template(): string
    {
        return 'owner.client_viewed';
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->ownerChannels($notifiable, 'client_viewed_email');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mail()
            ->subject("Client viewed {$this->changeRequest->reference}")
            ->line('Your client opened the change request "'.$this->title().'".')
            ->action('View request', $this->appUrl());
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return $this->databasePayload('Client viewed "'.$this->title().'"', 'viewed');
    }
}
