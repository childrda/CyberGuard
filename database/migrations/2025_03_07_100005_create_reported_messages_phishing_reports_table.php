<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reported_messages', function (Blueprint $table) {
            $table->id();
            $table->string('reporter_email');
            $table->string('reporter_name')->nullable();
            $table->string('gmail_message_id')->nullable();
            $table->string('gmail_thread_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('from_address')->nullable();
            $table->text('from_display')->nullable();
            $table->string('to_addresses')->nullable();
            $table->timestamp('message_date')->nullable();
            $table->text('snippet')->nullable();
            $table->json('headers')->nullable();
            $table->string('report_type')->default('phish'); // phish, spam, safe
            $table->string('source')->default('addon'); // addon, manual
            $table->foreignId('phishing_message_id')->nullable()->constrained('phishing_messages')->nullOnDelete(); // if matched to simulation
            $table->string('analyst_status')->nullable(); // pending, analyst_confirmed_real, analyst_confirmed_simulation, false_positive
            $table->foreignId('analyst_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('analyst_reviewed_at')->nullable();
            $table->text('analyst_notes')->nullable();
            $table->json('user_actions')->nullable(); // ["clicked_link", "opened_attachment", "entered_password", "not_sure"]
            $table->timestamps();

            $table->index(['reporter_email', 'created_at']);
            $table->index('analyst_status');
        });

        Schema::create('phishing_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reported_message_id')->constrained('reported_messages')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('phishing_messages')->nullOnDelete();
            $table->string('event_type')->default('reported'); // reported, marked_safe, analyst_confirmed_*
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('reported_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phishing_reports');
        Schema::dropIfExists('reported_messages');
    }
};
