<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use App\Notifications\Concerns\ChangeRequestNotification;
use Illuminate\Notifications\Messages\MailMessage;

class ChangeRequestSentToClient extends ChangeRequestNotification
{
    public function __construct(ChangeRequest $changeRequest, public readonly string $url)
    {
        parent::__construct($changeRequest);
    }

    protected function template(): string
    {
        return 'client.change_request';
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr = $this->changeRequest;
        $revision = $cr->currentRevision;
        $workspace = $cr->workspace;

        return $this->mail()
            ->subject("Approval needed: {$this->title()} ({$cr->reference})")
            ->greeting('Hello'.($cr->recipient_name ? ' '.$cr->recipient_name : '').',')
            ->line("{$workspace->name} has shared a requested change for {$cr->project->title} and needs your approval before starting the work.")
            ->line('**'.$this->title().'**')
            ->line('Price: '.($revision?->price()->format() ?? '—').' · '.($revision?->timelineLabel() ?? ''))
            ->action('Review the change', $this->url)
            ->line('You can approve, decline or ask a question. No account is needed.')
            ->line('This link expires on '.($cr->expires_at?->setTimezone($workspace->timezone)->toFormattedDayDateString() ?? 'its expiry date').'. Reference: '.$cr->reference);
    }
}
