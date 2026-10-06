<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20)->default('paddle');
            $table->string('provider_subscription_id')->unique();
            $table->string('provider_customer_id')->nullable();
            $table->string('plan', 20);
            $table->string('billing_interval', 10)->default('month');
            $table->string('status', 20);
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('cancels_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('provider_updated_at')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'status']);
        });

        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('provider_price_id');
            $table->string('provider_product_id')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status', 20)->nullable();
            $table->timestamps();
            $table->unique(['subscription_id', 'provider_price_id']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 20)->default('paddle');
            $table->string('provider_transaction_id')->unique();
            $table->string('provider_subscription_id')->nullable();
            $table->string('status', 30);
            $table->bigInteger('total_minor')->default(0);
            $table->char('currency', 3)->default('USD');
            $table->string('invoice_number')->nullable();
            $table->timestamp('billed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('metric', 40);
            $table->string('period', 7);
            $table->unsignedInteger('value')->default(0);
            $table->timestamps();
            $table->unique(['workspace_id', 'metric', 'period']);
        });

        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('scope', 20)->default('global');
            $table->boolean('enabled')->default(false);
            $table->json('config')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20);
            $table->string('external_id');
            $table->string('event_type', 80);
            $table->char('payload_hash', 64);
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'external_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('usage_counters');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('subscriptions');
    }
};
