<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Workspace;
use App\Notifications\Concerns\AppNotification;
use Illuminate\Notifications\Messages\MailMessage;

class WorkspaceInvitationNotification extends AppNotification
{
    public function __construct(public readonly Workspace $workspace, public readonly User $inviter, public readonly string $token)
    {
        parent::__construct();
    }

    protected function template(): string
    {
        return 'team.invitation';
    }

    protected function workspaceId(): ?int
    {
        return $this->workspace->id;
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mail()
            ->subject("{$this->inviter->name} invited you to {$this->workspace->name}")
            ->line("{$this->inviter->name} invited you to join the {$this->workspace->name} workspace.")
            ->action('Accept invitation', route('invitations.accept', ['token' => $this->token]))
            ->line('This invitation expires in 7 days. Sign up or log in with this email address to accept it.');
    }
}
