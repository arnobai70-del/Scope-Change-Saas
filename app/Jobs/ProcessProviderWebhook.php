<?php

namespace App\Jobs;

use App\Models\WebhookEvent;
use App\Services\PaddleWebhookHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessProviderWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function __construct(public readonly int $webhookEventId) {}

    public function handle(PaddleWebhookHandler $handler): void
    {
        $event = WebhookEvent::query()->find($this->webhookEventId);

        // Idempotency: processed events are never applied twice.
        if ($event === null || $event->status === 'processed') {
            return;
        }

        $event->increment('attempts');

        try {
            $handler->handle($event);
        } catch (Throwable $e) {
            $event->forceFill(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)])->save();

            if ($event->attempts >= (int) config('billing.webhook_alert_threshold', 3)) {
                Log::error('Billing webhook keeps failing', ['webhook_event_id' => $event->id, 'event_type' => $event->event_type, 'attempts' => $event->attempts]);
            }

            throw $e;
        }

        $event->forceFill(['status' => 'processed', 'processed_at' => now(), 'error' => null])->save();
    }
}
