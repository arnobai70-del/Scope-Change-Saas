<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use App\Notifications\Concerns\ChangeRequestNotification;
use Illuminate\Notifications\Messages\MailMessage;

class ClientReminder extends ChangeRequestNotification
{
    public function __construct(ChangeRequest $changeRequest, public readonly string $kind, public readonly string $muteUrl)
    {
        parent::__construct($changeRequest);
    }

    protected function template(): string
    {
        return 'client.reminder.'.$this->kind;
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr = $this->changeRequest;
        $workspace = $cr->workspace;
        $final = $this->kind === 'final';

        return $this->mail()
            ->subject(($final ? 'Final reminder: ' : 'Reminder: ').$this->title()." ({$cr->reference})")
            ->line("{$workspace->name} is still waiting for your decision on a requested change to {$cr->project->title}.")
            ->line($final
                ? 'The approval link expires within 24 hours.'
                : 'Please use the link from the original email to review it. If you can no longer find it, reply to the sender and they can send a fresh link.')
            ->line('You are receiving this because a change request was sent to this address. You can stop reminders for this request: '.$this->muteUrl);
    }
}
