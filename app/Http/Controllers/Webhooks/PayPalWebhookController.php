<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessProviderWebhook;
use App\Models\WebhookEvent;
use App\Services\PayPalClient;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PayPalWebhookController extends Controller
{
    private const HEADERS = ['paypal-auth-algo', 'paypal-cert-url', 'paypal-transmission-id', 'paypal-transmission-sig', 'paypal-transmission-time'];

    public function __invoke(Request $request, PayPalClient $paypal): JsonResponse
    {
        $raw = $request->getContent();
        $headers = [];

        foreach (self::HEADERS as $name) {
            $headers[$name] = $request->header($name);
        }

        // PayPal confirms the delivery is genuine before any business data is read.
        if (! $paypal->verifyWebhook($headers, $raw)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $payload = json_decode($raw, true);

        if (! is_array($payload) || ! is_string($payload['id'] ?? null) || ! is_string($payload['event_type'] ?? null)) {
            return response()->json(['message' => 'Malformed payload.'], 422);
        }

        try {
            $event = WebhookEvent::query()->create([
                'provider' => 'paypal',
                'external_id' => $payload['id'],
                'event_type' => $payload['event_type'],
                'payload_hash' => hash('sha256', $raw),
                'payload' => $payload,
                'status' => 'pending',
                'occurred_at' => is_string($payload['create_time'] ?? null) ? Carbon::parse($payload['create_time']) : null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Duplicate delivery: already stored, acknowledge without reprocessing.
            return response()->json(['status' => 'duplicate']);
        }

        ProcessProviderWebhook::dispatch($event->id);

        return response()->json(['status' => 'accepted']);
    }
}
