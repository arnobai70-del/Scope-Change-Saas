<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->string('message_id')->nullable()->index();
            $table->string('template', 120);
            $table->string('template_version', 20)->default('1');
            $table->string('recipient');
            $table->string('status', 20)->default('sent');
            $table->json('metadata_json')->nullable();
            $table->timestamps();
            $table->index(['recipient', 'status']);
        });

        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('reason', 30);
            $table->unsignedInteger('bounce_count')->default(0);
            $table->timestamps();
        });

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('name');
            $table->json('content_json');
            $table->timestamps();
            $table->index(['workspace_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
        Schema::dropIfExists('email_suppressions');
        Schema::dropIfExists('email_deliveries');
        Schema::dropIfExists('notifications');
    }
};
