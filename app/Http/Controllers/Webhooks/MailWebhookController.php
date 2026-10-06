<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\EmailDelivery;
use App\Models\EmailSuppression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Provider-neutral bounce / complaint feedback endpoint. Point your email
 * provider (or a small adapter) at it with the shared secret header.
 * Hard bounces and complaints suppress the address immediately; soft
 * bounces suppress it after three occurrences.
 */
class MailWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('services.mail_webhook.secret');

        if (! is_string($secret) || $secret === '' || ! hash_equals($secret, (string) $request->header('X-Webhook-Secret'))) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $data = $request->validate([
            'type' => ['required', 'in:hard_bounce,soft_bounce,complaint,delivered'],
            'email' => ['required', 'email'],
            'message_id' => ['nullable', 'string', 'max:255'],
        ]);

        $email = mb_strtolower($data['email']);

        if (isset($data['message_id'])) {
            EmailDelivery::query()->where('message_id', $data['message_id'])->where('recipient', $email)->update(['status' => $data['type']]);
        }

        if ($data['type'] === 'delivered') {
            return response()->json(['status' => 'ok']);
        }

        $suppression = EmailSuppression::query()->firstOrNew(['email' => $email]);
        $suppression->bounce_count = (int) $suppression->bounce_count + 1;
        $suppression->reason = $data['type'] === 'soft_bounce' && $suppression->bounce_count < 3
            ? EmailSuppression::PENDING
            : $data['type'];
        $suppression->save();

        return response()->json(['status' => 'ok']);
    }
}
