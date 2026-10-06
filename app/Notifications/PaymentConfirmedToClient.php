<?php

namespace App\Notifications;

use App\Notifications\Concerns\ChangeRequestNotification;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentConfirmedToClient extends ChangeRequestNotification
{
    protected function template(): string
    {
        return 'client.payment_confirmed';
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr = $this->changeRequest;
        $payment = $cr->currentPayment();

        return $this->mail()
            ->subject("Payment received for {$cr->reference}")
            ->line("{$cr->workspace->name} confirmed they received your payment".($payment ? ' of '.$payment->amount()->format() : '').' for:')
            ->line('**'.$this->title().'**')
            ->line('Thank you. Reference: '.$cr->reference);
    }
}
