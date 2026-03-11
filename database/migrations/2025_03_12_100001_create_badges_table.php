<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug'); // unique per tenant, e.g. "first-report"
            $table->text('description')->nullable();
            $table->string('criteria_type'); // e.g. first_report, reports_count, training_completed, no_clicks_streak
            $table->json('criteria_config')->nullable(); // e.g. {"min_reports": 5, "period": "month"}
            $table->string('icon')->nullable(); // icon name or path
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
