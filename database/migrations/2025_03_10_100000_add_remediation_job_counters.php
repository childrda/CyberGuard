<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remediation_jobs', function (Blueprint $table) {
            $table->unsignedInteger('removed_count')->default(0)->after('failure_summary');
            $table->unsignedInteger('skipped_count')->default(0)->after('removed_count');
            $table->unsignedInteger('dry_run_count')->default(0)->after('skipped_count');
            $table->unsignedInteger('failed_count')->default(0)->after('dry_run_count');
        });
    }

    public function down(): void
    {
        Schema::table('remediation_jobs', function (Blueprint $table) {
            $table->dropColumn(['removed_count', 'skipped_count', 'dry_run_count', 'failed_count']);
        });
    }
};
