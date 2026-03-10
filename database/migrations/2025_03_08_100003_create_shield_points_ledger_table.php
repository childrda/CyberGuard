<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shield_points_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('user_identifier'); // email or user_id for the tenant's user
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // platform user if linked
            $table->string('event_type');
            $table->integer('points_delta'); // can be negative
            $table->string('reason')->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained('phishing_campaigns')->nullOnDelete();
            $table->foreignId('reported_message_id')->nullable()->constrained('reported_messages')->nullOnDelete();
            $table->timestamp('created_at');

            $table->index(['tenant_id', 'user_identifier', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shield_points_ledger');
    }
};
