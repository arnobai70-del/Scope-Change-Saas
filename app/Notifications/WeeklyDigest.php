<?php

namespace App\Notifications;

use App\Models\Workspace;
use App\Notifications\Concerns\AppNotification;
use Illuminate\Notifications\Messages\MailMessage;

class WeeklyDigest extends AppNotification
{
    /**
     * @param  array{pending: int, approved: int, approved_value: string, questions: int}  $stats
     */
    public function __construct(public readonly Workspace $workspace, public readonly array $stats)
    {
        parent::__construct();
    }

    protected function template(): string
    {
        return 'owner.weekly_digest';
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
            ->subject("Your week in {$this->workspace->name}")
            ->line("Approved last week: {$this->stats['approved']} ({$this->stats['approved_value']})")
            ->line("Waiting on clients: {$this->stats['pending']}")
            ->line("Open questions: {$this->stats['questions']}")
            ->action('Open dashboard', route('dashboard'))
            ->line('You opted in to this digest. Turn it off in notification settings.');
    }
}
