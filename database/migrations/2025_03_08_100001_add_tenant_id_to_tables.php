<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
        });
        Schema::table('phishing_templates', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
        });
        Schema::table('phishing_campaigns', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
        });
        Schema::table('reported_messages', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            $table->uuid('correlation_id')->nullable()->after('tenant_id');
        });
        Schema::table('user_risk_scores', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) { $table->dropConstrainedForeignId('tenant_id'); });
        Schema::table('phishing_templates', function (Blueprint $table) { $table->dropConstrainedForeignId('tenant_id'); });
        Schema::table('phishing_campaigns', function (Blueprint $table) { $table->dropConstrainedForeignId('tenant_id'); });
        Schema::table('reported_messages', function (Blueprint $table) { $table->dropConstrainedForeignId('tenant_id'); });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('correlation_id');
        });
        Schema::table('user_risk_scores', function (Blueprint $table) { $table->dropConstrainedForeignId('tenant_id'); });
    }
};
