<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('code', 40)->nullable();
            $table->bigInteger('base_amount_minor')->default(0);
            $table->char('currency', 3);
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('revision_allowance')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'status']);
        });

        Schema::create('scope_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->json('content_json')->nullable();
            $table->char('content_hash', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'version']);
        });

        Schema::create('scope_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('baseline_id')->constrained('scope_baselines')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('title');
            $table->text('detail')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scope_items');
        Schema::dropIfExists('scope_baselines');
        Schema::dropIfExists('projects');
    }
};
