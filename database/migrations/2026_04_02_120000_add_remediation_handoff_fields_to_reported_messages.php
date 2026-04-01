<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reported_messages', function (Blueprint $table) {
            $table->boolean('remediation_via_google_admin')->default(false)->after('slack_message_ts');
            $table->timestamp('reporter_mailbox_cleared_at')->nullable()->after('remediation_via_google_admin');
        });
    }

    public function down(): void
    {
        Schema::table('reported_messages', function (Blueprint $table) {
            $table->dropColumn(['remediation_via_google_admin', 'reporter_mailbox_cleared_at']);
        });
    }
};
