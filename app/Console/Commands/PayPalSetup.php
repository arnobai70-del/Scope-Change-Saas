<?php

namespace App\Console\Commands;

use App\Services\PayPalClient;
use App\Support\Money;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('billing:paypal-setup {--webhook-url= : Public HTTPS URL of /webhooks/paypal, to register the webhook}')]
#[Description('Create the PayPal product and subscription plans (and optionally the webhook) and print the .env lines')]
class PayPalSetup extends Command
{
    public const WEBHOOK_EVENTS = [
        'BILLING.SUBSCRIPTION.ACTIVATED',
        'BILLING.SUBSCRIPTION.UPDATED',
        'BILLING.SUBSCRIPTION.RE-ACTIVATED',
        'BILLING.SUBSCRIPTION.SUSPENDED',
        'BILLING.SUBSCRIPTION.CANCELLED',
        'BILLING.SUBSCRIPTION.EXPIRED',
        'BILLING.SUBSCRIPTION.PAYMENT.FAILED',
        'PAYMENT.SALE.COMPLETED',
        'PAYMENT.SALE.REFUNDED',
        'PAYMENT.SALE.REVERSED',
    ];

    public function handle(PayPalClient $paypal): int
    {
        if (! $paypal->hasCredentials()) {
            $this->error('Set PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET in .env first, then run php artisan config:clear.');

            return self::FAILURE;
        }

        $mode = $paypal->isLive() ? 'live' : 'sandbox';

        try {
            $product = $paypal->post('/v1/catalogs/products', [
                'name' => mb_substr((string) config('app.name').' subscription', 0, 127),
                'type' => 'SERVICE',
                'category' => 'SOFTWARE',
            ]);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->line('If these are '.($mode === 'live' ? 'sandbox' : 'live').' keys, set PAYPAL_MODE='.($mode === 'live' ? 'sandbox' : 'live').' and try again.');

            return self::FAILURE;
        }

        $productId = $product->json('id');

        if ($product->failed() || ! is_string($productId)) {
            $this->error('PayPal did not create the product: '.$product->body());

            return self::FAILURE;
        }

        $this->info("Connected to PayPal ({$mode}). Product {$productId} created.");

        /** @var array<string, array<string, mixed>> $plans */
        $plans = config('plans.plans', []);
        $lines = [];

        foreach (['solo', 'pro', 'agency'] as $key) {
            $definition = $plans[$key] ?? null;

            if ($definition === null) {
                continue;
            }

            foreach (['month' => 'MONTHLY', 'year' => 'YEARLY'] as $interval => $suffix) {
                $minor = (int) $definition[$interval === 'month' ? 'monthly_minor' : 'yearly_minor'];
                $price = (new Money($minor, 'USD'))->toDecimalString();

                $response = $paypal->post('/v1/billing/plans', [
                    'product_id' => $productId,
                    'name' => $definition['name'].' ('.($interval === 'month' ? 'monthly' : 'yearly').')',
                    'status' => 'ACTIVE',
                    'billing_cycles' => [[
                        'frequency' => ['interval_unit' => strtoupper($interval), 'interval_count' => 1],
                        'tenure_type' => 'REGULAR',
                        'sequence' => 1,
                        'total_cycles' => 0,
                        'pricing_scheme' => ['fixed_price' => ['value' => $price, 'currency_code' => 'USD']],
                    ]],
                    'payment_preferences' => ['auto_bill_outstanding' => true, 'payment_failure_threshold' => 3],
                ]);

                $planId = $response->json('id');

                if ($response->failed() || ! is_string($planId)) {
                    $this->error("PayPal did not create the {$key} {$interval} plan: ".$response->body());

                    return self::FAILURE;
                }

                $this->line("  {$definition['name']} {$interval}: \${$price} → {$planId}");
                $lines[] = 'PAYPAL_PLAN_'.strtoupper($key).'_'.$suffix.'='.$planId;
            }
        }

        $webhookUrl = $this->option('webhook-url');

        if (is_string($webhookUrl) && $webhookUrl !== '') {
            if (! str_starts_with($webhookUrl, 'https://')) {
                $this->error('The webhook URL must start with https://');

                return self::FAILURE;
            }

            $webhook = $paypal->post('/v1/notifications/webhooks', [
                'url' => $webhookUrl,
                'event_types' => array_map(fn (string $name) => ['name' => $name], self::WEBHOOK_EVENTS),
            ]);

            $webhookId = $webhook->json('id');

            if ($webhook->failed() || ! is_string($webhookId)) {
                $this->warn('PayPal did not register the webhook: '.$webhook->body());
            } else {
                $lines[] = 'PAYPAL_WEBHOOK_ID='.$webhookId;
            }
        }

        $this->newLine();
        $this->info('Add these lines to .env, then run php artisan config:clear:');
        $this->newLine();
        $this->line('BILLING_PROVIDER=paypal');
        $this->line('PAYPAL_MODE='.$mode);

        foreach ($lines as $line) {
            $this->line($line);
        }

        return self::SUCCESS;
    }
}
