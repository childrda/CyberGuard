<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('user_identifier'); // email or identifier, matches shield_points_ledger
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('badge_id')->constrained('badges')->cascadeOnDelete();
            $table->foreignId('score_period_id')->nullable()->constrained('score_periods')->nullOnDelete();
            $table->timestamp('awarded_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'user_identifier', 'badge_id']);
            $table->index(['tenant_id', 'user_identifier']);
            $table->index(['badge_id', 'awarded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
    }
};
