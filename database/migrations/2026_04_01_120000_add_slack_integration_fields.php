<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('slack_alerts_enabled')->default(false)->after('webhook_secret');
            $table->text('slack_bot_token')->nullable()->after('slack_alerts_enabled');
            $table->string('slack_channel')->nullable()->after('slack_bot_token');
        });

        Schema::table('reported_messages', function (Blueprint $table) {
            $table->string('slack_channel')->nullable()->after('user_actions');
            $table->string('slack_message_ts')->nullable()->after('slack_channel');
            $table->index(['slack_channel', 'slack_message_ts'], 'reported_messages_slack_idx');
        });
    }

    public function down(): void
    {
        Schema::table('reported_messages', function (Blueprint $table) {
            $table->dropIndex('reported_messages_slack_idx');
            $table->dropColumn(['slack_channel', 'slack_message_ts']);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['slack_alerts_enabled', 'slack_bot_token', 'slack_channel']);
        });
    }
};

