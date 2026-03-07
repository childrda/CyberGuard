<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phishing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('template_id')->constrained('phishing_templates')->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft, pending_approval, approved, scheduled, sending, paused, completed, archived
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('send_window_start_minute')->nullable(); // 0-1439 (minute of day)
            $table->integer('send_window_end_minute')->nullable();
            $table->integer('throttle_per_minute')->nullable();
            $table->boolean('randomize_send_times')->default(true);
            $table->json('allowed_domains')->nullable(); // override config
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('phishing_campaign_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('phishing_campaigns')->cascadeOnDelete();
            $table->string('target_type'); // user, group, csv
            $table->string('target_identifier'); // email, group email, or "csv_batch_xyz"
            $table->string('display_name')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'target_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phishing_campaign_targets');
        Schema::dropIfExists('phishing_campaigns');
    }
};
