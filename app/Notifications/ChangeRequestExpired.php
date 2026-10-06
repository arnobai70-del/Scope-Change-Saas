<?php

namespace App\Notifications;

use App\Notifications\Concerns\ChangeRequestNotification;
use Illuminate\Notifications\Messages\MailMessage;

class ChangeRequestExpired extends ChangeRequestNotification
{
    protected function template(): string
    {
        return 'owner.expired';
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->ownerChannels($notifiable, 'expired_email');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mail()
            ->subject("Expired without a decision: {$this->changeRequest->reference}")
            ->line('The approval link for "'.$this->title().'" expired before the client decided.')
            ->line('You can reissue a fresh link from the request page.')
            ->action('Open request', $this->appUrl());
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return $this->databasePayload('"'.$this->title().'" expired without a decision', 'expired');
    }
}
