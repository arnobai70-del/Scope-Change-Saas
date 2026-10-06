<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->ulid('public_id')->unique();
            $table->string('reference', 40);
            $table->unsignedBigInteger('current_revision_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reminders_muted_at')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'reference']);
            $table->index(['workspace_id', 'status']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('change_request_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision_no');
            $table->string('title');
            $table->text('description');
            $table->text('scope_reason')->nullable();
            $table->text('scope_excerpt')->nullable();
            $table->bigInteger('price_minor')->default(0);
            $table->char('currency', 3);
            $table->json('timeline_json');
            $table->string('payment_rule', 20)->default('none');
            $table->string('payment_url', 2048)->nullable();
            $table->text('payment_instructions')->nullable();
            $table->text('terms_note')->nullable();
            $table->string('terms_version', 20)->default('2026-10');
            $table->json('snapshot_json')->nullable();
            $table->char('snapshot_hash', 64)->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['change_request_id', 'revision_no']);
        });

        Schema::create('change_scope_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revision_id')->constrained('change_request_revisions')->cascadeOnDelete();
            $table->foreignId('scope_item_id')->constrained('scope_items')->cascadeOnDelete();
            $table->string('relation_type', 20)->default('extends');
            $table->timestamps();
            $table->unique(['revision_id', 'scope_item_id']);
        });

        Schema::create('approval_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revision_id')->constrained('change_request_revisions')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('access_policy', 20)->default('link');
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('client_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revision_id')->unique()->constrained('change_request_revisions')->cascadeOnDelete();
            $table->string('decision', 20);
            $table->string('client_name');
            $table->string('client_email');
            $table->string('accepted_terms_version', 20)->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('decided_at');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::create('change_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revision_id')->nullable()->constrained('change_request_revisions')->nullOnDelete();
            $table->string('actor_type', 20);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->text('body');
            $table->string('visibility', 20)->default('shared');
            $table->timestamps();
            $table->index(['change_request_id', 'created_at']);
        });

        Schema::create('payment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revision_id')->constrained('change_request_revisions')->cascadeOnDelete();
            $table->string('status', 20);
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('method_type', 30)->default('external');
            $table->string('external_url', 2048)->nullable();
            $table->string('reference')->nullable();
            $table->timestamp('marked_sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('revision_id');
        });

        Schema::create('proof_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status', 20)->default('queued');
            $table->string('file_path')->nullable();
            $table->char('checksum', 64)->nullable();
            $table->char('content_hash', 64)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique(['change_request_id', 'version']);
        });

        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->timestamp('sent_at');
            $table->timestamps();
            $table->unique(['change_request_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
        Schema::dropIfExists('proof_packs');
        Schema::dropIfExists('payment_records');
        Schema::dropIfExists('change_comments');
        Schema::dropIfExists('client_decisions');
        Schema::dropIfExists('approval_links');
        Schema::dropIfExists('change_scope_links');
        Schema::dropIfExists('change_request_revisions');
        Schema::dropIfExists('change_requests');
    }
};
