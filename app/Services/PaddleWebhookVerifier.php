<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Verifies the Paddle-Signature header: "ts=<unix>;h1=<hmac>[;h1=<hmac>]"
 * where hmac = HMAC-SHA256("<ts>:<raw body>", secret).
 */
class PaddleWebhookVerifier
{
    public function verify(string $rawBody, ?string $header, ?string $secret, int $tolerance = 300): bool
    {
        if ($header === null || $header === '' || $secret === null || $secret === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(';', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');

            if ($key === 'ts') {
                $timestamp = $value;
            } elseif ($key === 'h1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || ! ctype_digit($timestamp) || $signatures === []) {
            return false;
        }

        if (abs(Carbon::now()->getTimestamp() - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.':'.$rawBody, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
