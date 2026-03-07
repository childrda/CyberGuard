<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phishing_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('phishing_campaigns')->cascadeOnDelete();
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('tracking_token')->unique(); // long random, non-enumerable
            $table->string('message_id')->nullable(); // Gmail/IMAP message ID if available
            $table->string('status')->default('queued'); // queued, sent, delivered, bounced, failed
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'recipient_email']);
            $table->index('tracking_token');
        });

        Schema::create('phishing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('phishing_messages')->cascadeOnDelete();
            $table->string('event_type'); // queued, sent, delivered, bounced, opened, clicked, submitted, reported, marked_safe, analyst_confirmed_real, analyst_confirmed_simulation
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['message_id', 'event_type']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phishing_events');
        Schema::dropIfExists('phishing_messages');
    }
};
