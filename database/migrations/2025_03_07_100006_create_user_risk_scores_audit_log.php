<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('department')->nullable();
            $table->string('ou')->nullable();
            $table->decimal('score', 5, 2)->default(0); // 0-100 or similar
            $table->json('factors')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['email', 'calculated_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('url')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->timestamp('created_at');

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_risk_scores');
        Schema::dropIfExists('audit_logs');
    }
};
