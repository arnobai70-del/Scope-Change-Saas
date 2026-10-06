<?php

namespace App\Listeners;

use App\Models\EmailDelivery;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;

/**
 * Logs every sent email (template + recipient, never the body) so bounces
 * can be matched and Proof Packs can show what was sent.
 */
class RecordEmailDelivery
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message;
        $headers = $message->getHeaders();
        $template = $headers->get('X-App-Template')?->getBodyAsString() ?? 'framework';
        $version = $headers->get('X-App-Template-Version')?->getBodyAsString() ?? '1';
        $workspace = $headers->get('X-App-Workspace')?->getBodyAsString();

        foreach ($message->getTo() as $address) {
            /** @var Address $address */
            EmailDelivery::query()->create([
                'workspace_id' => is_numeric($workspace) ? (int) $workspace : null,
                'message_id' => $event->sent->getMessageId(),
                'template' => mb_substr($template, 0, 120),
                'template_version' => mb_substr($version, 0, 20),
                'recipient' => mb_strtolower($address->getAddress()),
                'status' => 'sent',
            ]);
        }
    }
}
