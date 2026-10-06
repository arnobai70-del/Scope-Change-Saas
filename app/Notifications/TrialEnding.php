<?php

namespace App\Notifications;

use App\Models\Workspace;
use App\Notifications\Concerns\AppNotification;
use Illuminate\Notifications\Messages\MailMessage;

class TrialEnding extends AppNotification
{
    public function __construct(public readonly Workspace $workspace, public readonly int $daysLeft)
    {
        parent::__construct();
    }

    protected function template(): string
    {
        return 'owner.trial_ending';
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
        $days = $this->daysLeft === 1 ? '1 day' : "{$this->daysLeft} days";

        return $this->mail()
            ->subject("Your trial ends in {$days}")
            ->line("The trial for {$this->workspace->name} ends in {$days}. After that the workspace moves to the Free plan; nothing is deleted.")
            ->action('Choose a plan', route('billing.show'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['kind' => 'trial', 'headline' => "Your trial ends in {$this->daysLeft} day(s)", 'url' => route('billing.show')];
    }
}
