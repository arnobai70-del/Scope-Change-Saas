<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->char('currency', 3)->default('USD');
            $table->string('timezone', 64)->default('UTC');
            $table->string('service_type', 60)->nullable();
            $table->string('brand_color', 7)->default('#4f46e5');
            $table->string('logo_path')->nullable();
            $table->string('plan', 20)->default('free');
            $table->timestamp('trial_ends_at')->nullable();
            $table->unsignedSmallInteger('default_expiry_days')->default(7);
            $table->boolean('reminders_enabled')->default(true);
            $table->text('default_payment_instructions')->nullable();
            $table->string('default_payment_url', 2048)->nullable();
            $table->unsignedInteger('change_request_sequence')->default(0);
            $table->timestamp('onboarded_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('workspace_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'user_id']);
        });

        Schema::create('workspace_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->string('role', 20)->default('member');
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_invitations');
        Schema::dropIfExists('workspace_user');
        Schema::dropIfExists('workspaces');
    }
};
