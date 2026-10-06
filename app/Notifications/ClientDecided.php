<?php

namespace App\Notifications;

use App\Enums\ClientDecisionType;
use App\Models\ChangeRequest;
use App\Models\ClientDecision;
use App\Notifications\Concerns\ChangeRequestNotification;
use Illuminate\Notifications\Messages\MailMessage;

class ClientDecided extends ChangeRequestNotification
{
    public function __construct(ChangeRequest $changeRequest, public readonly ClientDecision $decision)
    {
        parent::__construct($changeRequest);
    }

    protected function template(): string
    {
        return 'owner.client_'.$this->decision->decision->value;
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->ownerChannels($notifiable, 'client_decided_email');
    }

    private function verb(): string
    {
        return $this->decision->decision === ClientDecisionType::Approved ? 'approved' : 'declined';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->mail()
            ->subject(ucfirst($this->verb()).": {$this->title()} ({$this->changeRequest->reference})")
            ->line("{$this->decision->client_name} {$this->verb()} the change request \"{$this->title()}\".");

        if ($this->decision->reason) {
            $mail->line('Reason: '.$this->decision->reason);
        }

        return $mail->action('Open request', $this->appUrl());
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return $this->databasePayload("{$this->decision->client_name} {$this->verb()} \"{$this->title()}\"", $this->verb());
    }
}
