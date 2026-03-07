<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remediation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('reported_message_id')->constrained('reported_messages')->cascadeOnDelete();
            $table->uuid('correlation_id')->nullable();
            $table->string('status')->default('pending_review'); // pending_review, approved_for_removal, removal_in_progress, removed, partially_failed, failed
            $table->boolean('dry_run')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_summary')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('correlation_id');
        });

        Schema::create('remediation_job_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remediation_job_id')->constrained('remediation_jobs')->cascadeOnDelete();
            $table->string('mailbox_email');
            $table->string('gmail_message_id')->nullable();
            $table->string('message_identifier')->nullable(); // Message-ID header or similar
            $table->string('status')->default('pending'); // pending, logged, success, failed
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['remediation_job_id', 'status']);
        });

        Schema::create('mailbox_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('remediation_job_id')->nullable()->constrained('remediation_jobs')->nullOnDelete();
            $table->foreignId('remediation_job_item_id')->nullable()->constrained('remediation_job_items')->nullOnDelete();
            $table->string('mailbox_email');
            $table->string('message_identifier')->nullable();
            $table->string('action_attempted'); // e.g. trash, delete
            $table->string('action_result'); // success, failed, skipped, dry_run
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type')->default('user'); // user, automation, system
            $table->text('api_response_summary')->nullable();
            $table->timestamp('created_at');

            $table->index(['tenant_id', 'created_at']);
            $table->index('remediation_job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_action_logs');
        Schema::dropIfExists('remediation_job_items');
        Schema::dropIfExists('remediation_jobs');
    }
};
