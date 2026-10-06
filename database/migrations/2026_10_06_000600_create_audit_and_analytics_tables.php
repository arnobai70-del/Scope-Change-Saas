<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->string('actor_type', 20);
            $table->string('actor_id')->nullable();
            $table->string('actor_label')->nullable();
            $table->string('entity_type', 60);
            $table->unsignedBigInteger('entity_id');
            $table->string('event', 80);
            $table->json('metadata_json')->nullable();
            $table->char('previous_hash', 64)->nullable();
            $table->char('hash', 64);
            $table->timestamp('occurred_at', 6);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['workspace_id', 'occurred_at']);
        });

        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event', 60);
            $table->json('properties')->nullable();
            $table->timestamp('occurred_at');
            $table->index(['event', 'occurred_at']);
            $table->index(['workspace_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('audit_events');
    }
};
