<?php

namespace App\Notifications;

use App\Models\ChangeComment;
use App\Models\ChangeRequest;
use App\Notifications\Concerns\ChangeRequestNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

class OwnerRepliedToClient extends ChangeRequestNotification
{
    public function __construct(ChangeRequest $changeRequest, public readonly ChangeComment $comment)
    {
        parent::__construct($changeRequest);
    }

    protected function template(): string
    {
        return 'client.reply';
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr = $this->changeRequest;

        return $this->mail()
            ->subject("Reply about {$cr->reference}: {$this->title()}")
            ->line(($this->comment->actor_name ?? $cr->workspace->name).' replied to your question:')
            ->line('> '.Str::limit($this->comment->body, 1000))
            ->line('Open the original approval link to continue. Reference: '.$cr->reference);
    }
}
