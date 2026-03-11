<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shield_points_ledger', function (Blueprint $table) {
            $table->foreignId('score_period_id')
                ->nullable()
                ->after('reported_message_id')
                ->constrained('score_periods')
                ->nullOnDelete();
        });

        Schema::table('shield_points_ledger', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'score_period_id', 'user_identifier'],
                'shield_points_ledger_tenant_score_period_user_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('shield_points_ledger', function (Blueprint $table) {
            $table->dropIndex('shield_points_ledger_tenant_score_period_user_idx');
            $table->dropForeign(['score_period_id']);
        });
    }
};
