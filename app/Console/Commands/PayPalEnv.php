<?php

namespace App\Console\Commands;

use App\Services\PayPalClient;
use App\Support\EnvFile;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('billing:paypal-env')]
#[Description('Read the subscription plans created by billing:paypal-setup from PayPal and write their ids to .env')]
class PayPalEnv extends Command
{
    public function handle(PayPalClient $paypal): int
    {
        if (! $paypal->hasCredentials()) {
            $this->error('Set PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET in .env first, then run php artisan config:clear.');

            return self::FAILURE;
        }

        try {
            $response = $paypal->get('/v1/billing/plans?page_size=20&total_required=true');
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        /** @var list<array<string, mixed>> $remote */
        $remote = $response->json('plans', []);

        if ($response->failed()) {
            $this->error('PayPal did not list the plans: '.$response->body());

            return self::FAILURE;
        }

        /** @var array<string, array<string, mixed>> $definitions */
        $definitions = config('plans.plans', []);
        $values = [];

        foreach (['solo', 'pro', 'agency'] as $key) {
            foreach (['month' => 'monthly', 'year' => 'yearly'] as $suffix) {
                $name = ($definitions[$key]['name'] ?? ucfirst($key)).' ('.$suffix.')';

                $match = collect($remote)
                    ->filter(fn (array $plan) => ($plan['name'] ?? null) === $name && ($plan['status'] ?? null) === 'ACTIVE' && is_string($plan['id'] ?? null))
                    ->sortByDesc(fn (array $plan) => (string) ($plan['create_time'] ?? ''))
                    ->first();

                if ($match === null) {
                    $this->error("No active PayPal plan named \"{$name}\". Run php artisan billing:paypal-setup first.");

                    return self::FAILURE;
                }

                $values['PAYPAL_PLAN_'.strtoupper($key).'_'.strtoupper($suffix)] = (string) $match['id'];
            }
        }

        $values = ['BILLING_PROVIDER' => 'paypal', 'PAYPAL_MODE' => $paypal->isLive() ? 'live' : 'sandbox'] + $values;

        EnvFile::set(app()->environmentFilePath(), $values);

        foreach ($values as $envKey => $value) {
            $this->line("  {$envKey}={$value}");
        }

        $this->info('Saved to .env. Now run: php artisan config:clear');

        return self::SUCCESS;
    }
}
