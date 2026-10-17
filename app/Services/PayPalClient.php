<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin client for the PayPal REST API (Subscriptions, Catalog products and
 * webhook verification). Access tokens are cached until shortly before
 * they expire.
 */
class PayPalClient
{
    public function isLive(): bool
    {
        return config('billing.paypal.mode') === 'live';
    }

    public function hasCredentials(): bool
    {
        return filled(config('billing.paypal.client_id')) && filled(config('billing.paypal.client_secret'));
    }

    public function baseUrl(): string
    {
        return $this->isLive() ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Where a subscriber manages or cancels automatic payments.
     */
    public function autopayUrl(): string
    {
        return $this->isLive() ? 'https://www.paypal.com/myaccount/autopay/' : 'https://www.sandbox.paypal.com/myaccount/autopay/';
    }

    public function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->retry(2, 500, throw: false);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function post(string $path, array $body = [], ?string $idempotencyKey = null): Response
    {
        $request = $this->request();

        if ($idempotencyKey !== null) {
            $request = $request->withHeaders(['PayPal-Request-Id' => $idempotencyKey]);
        }

        return $request->post($path, $body === [] ? (object) [] : $body);
    }

    public function get(string $path): Response
    {
        return $this->request()->get($path);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function subscription(string $id): ?array
    {
        if (! preg_match('/^I-[A-Z0-9]{6,40}$/', $id)) {
            return null;
        }

        $response = $this->get('/v1/billing/subscriptions/'.$id);

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new RuntimeException('PayPal subscription lookup failed with HTTP '.$response->status().'.');
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();

        return $data;
    }

    /**
     * Ask PayPal whether a webhook delivery is genuine.
     *
     * @param  array<string, string|null>  $headers  lower-case PayPal transmission headers
     */
    public function verifyWebhook(array $headers, string $rawBody): bool
    {
        $webhookId = config('billing.paypal.webhook_id');
        $event = json_decode($rawBody);

        if (! is_string($webhookId) || $webhookId === '' || ! is_object($event) || ! $this->hasCredentials()) {
            return false;
        }

        foreach (['paypal-auth-algo', 'paypal-cert-url', 'paypal-transmission-id', 'paypal-transmission-sig', 'paypal-transmission-time'] as $name) {
            if (! is_string($headers[$name] ?? null) || $headers[$name] === '') {
                return false;
            }
        }

        $response = $this->request()->post('/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $headers['paypal-auth-algo'],
            'cert_url' => $headers['paypal-cert-url'],
            'transmission_id' => $headers['paypal-transmission-id'],
            'transmission_sig' => $headers['paypal-transmission-sig'],
            'transmission_time' => $headers['paypal-transmission-time'],
            'webhook_id' => $webhookId,
            'webhook_event' => $event,
        ]);

        return $response->successful() && $response->json('verification_status') === 'SUCCESS';
    }

    private function accessToken(): string
    {
        $id = config('billing.paypal.client_id');
        $secret = config('billing.paypal.client_secret');

        if (! is_string($id) || $id === '' || ! is_string($secret) || $secret === '') {
            throw new RuntimeException('PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET are not configured.');
        }

        $cacheKey = 'paypal-token:'.hash('sha256', $this->baseUrl().$id);

        $token = Cache::get($cacheKey);

        if (is_string($token) && $token !== '') {
            return $token;
        }

        $response = Http::baseUrl($this->baseUrl())
            ->withBasicAuth($id, $secret)
            ->asForm()
            ->acceptJson()
            ->timeout(15)
            ->post('/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        $token = $response->json('access_token');

        if ($response->failed() || ! is_string($token) || $token === '') {
            throw new RuntimeException('PayPal rejected the API credentials (HTTP '.$response->status().'). Check PAYPAL_CLIENT_ID, PAYPAL_CLIENT_SECRET and PAYPAL_MODE.');
        }

        $ttl = max(60, (int) $response->json('expires_in', 3600) - 120);
        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }
}
