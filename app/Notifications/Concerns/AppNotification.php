<?php

namespace App\Notifications\Concerns;

use App\Models\EmailSuppression;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;

/**
 * Base for every app notification: always queued, only dispatched after the
 * surrounding database transaction commits, never sent to suppressed
 * (bounced / complained) addresses, and tagged for the email delivery log.
 */
abstract class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Bump when the wording changes so Proof Packs can show what was sent. */
    public const TEMPLATE_VERSION = '1';

    public int $tries = 3;

    public function __construct()
    {
        $this->afterCommit();
    }

    abstract protected function template(): string;

    protected function workspaceId(): ?int
    {
        return null;
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        if ($channel !== 'mail') {
            return true;
        }

        $email = $this->recipientEmail($notifiable);

        return $email !== null && ! EmailSuppression::isSuppressed($email);
    }

    protected function mail(): MailMessage
    {
        $template = $this->template();
        $workspaceId = $this->workspaceId();

        return (new MailMessage)->withSymfonyMessage(function (Email $message) use ($template, $workspaceId) {
            $message->getHeaders()->addTextHeader('X-App-Template', $template);
            $message->getHeaders()->addTextHeader('X-App-Template-Version', static::TEMPLATE_VERSION);
            if ($workspaceId !== null) {
                $message->getHeaders()->addTextHeader('X-App-Workspace', (string) $workspaceId);
            }
        });
    }

    /**
     * Owner-facing channels honour the user's notification preferences.
     *
     * @return list<string>
     */
    protected function ownerChannels(object $notifiable, ?string $emailPreference): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && ($emailPreference === null || $notifiable->wantsNotification($emailPreference))) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    private function recipientEmail(object $notifiable): ?string
    {
        if ($notifiable instanceof User) {
            return $notifiable->email;
        }

        if ($notifiable instanceof AnonymousNotifiable) {
            $route = $notifiable->routeNotificationFor('mail');
            if (is_array($route)) {
                $key = array_key_first($route);

                return is_string($key) ? $key : (is_string($route[$key] ?? null) ? $route[$key] : null);
            }

            return is_string($route) ? $route : null;
        }

        return null;
    }
}
