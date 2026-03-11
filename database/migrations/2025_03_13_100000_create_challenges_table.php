<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug'); // unique per tenant, e.g. "fall-2025-report-rate"
            $table->string('type')->default('tenant'); // tenant, classroom, school, department, ou
            $table->foreignId('score_period_id')->nullable()->constrained('score_periods')->nullOnDelete();
            $table->string('goal_type')->nullable(); // e.g. report_rate, most_improved, top_points
            $table->json('goal_config')->nullable();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'active', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
