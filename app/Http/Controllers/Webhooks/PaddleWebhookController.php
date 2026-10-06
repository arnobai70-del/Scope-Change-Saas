<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessProviderWebhook;
use App\Models\WebhookEvent;
use App\Services\PaddleWebhookVerifier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PaddleWebhookController extends Controller
{
    public function __invoke(Request $request, PaddleWebhookVerifier $verifier): JsonResponse
    {
        $raw = $request->getContent();

        // Verify the signature before reading any business data.
        if (! $verifier->verify($raw, $request->header('Paddle-Signature'), config('billing.paddle.webhook_secret'), (int) config('billing.paddle.webhook_tolerance', 300))) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $payload = json_decode($raw, true);

        if (! is_array($payload) || ! is_string($payload['event_id'] ?? null) || ! is_string($payload['event_type'] ?? null)) {
            return response()->json(['message' => 'Malformed payload.'], 422);
        }

        try {
            $event = WebhookEvent::query()->create([
                'provider' => 'paddle',
                'external_id' => $payload['event_id'],
                'event_type' => $payload['event_type'],
                'payload_hash' => hash('sha256', $raw),
                'payload' => $payload,
                'status' => 'pending',
                'occurred_at' => is_string($payload['occurred_at'] ?? null) ? Carbon::parse($payload['occurred_at']) : null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Duplicate delivery: already stored, acknowledge without reprocessing.
            return response()->json(['status' => 'duplicate']);
        }

        ProcessProviderWebhook::dispatch($event->id);

        return response()->json(['status' => 'accepted']);
    }
}
