<?php

namespace App\Notifications;

use App\Models\ChangeComment;
use App\Models\ChangeRequest;
use App\Notifications\Concerns\ChangeRequestNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

class ClientAskedQuestion extends ChangeRequestNotification
{
    public function __construct(ChangeRequest $changeRequest, public readonly ChangeComment $comment)
    {
        parent::__construct($changeRequest);
    }

    protected function template(): string
    {
        return 'owner.client_question';
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->ownerChannels($notifiable, 'client_questioned_email');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mail()
            ->subject("Question about {$this->changeRequest->reference}")
            ->line(($this->comment->actor_name ?? 'Your client').' asked a question about "'.$this->title().'":')
            ->line('> '.Str::limit($this->comment->body, 1000))
            ->action('Reply', $this->appUrl());
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return $this->databasePayload(($this->comment->actor_name ?? 'Client').' asked a question on "'.$this->title().'"', 'question');
    }
}
