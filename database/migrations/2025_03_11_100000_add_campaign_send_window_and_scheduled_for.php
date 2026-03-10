<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phishing_campaigns', function (Blueprint $table) {
            $table->date('window_start')->nullable()->after('approval_notes');
            $table->date('window_end')->nullable()->after('window_start');
            $table->unsignedSmallInteger('emails_per_recipient')->default(1)->after('window_end');
        });

        Schema::table('phishing_messages', function (Blueprint $table) {
            $table->timestamp('scheduled_for')->nullable()->after('queued_at');
            $table->index(['status', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::table('phishing_campaigns', function (Blueprint $table) {
            $table->dropColumn(['window_start', 'window_end', 'emails_per_recipient']);
        });
        Schema::table('phishing_messages', function (Blueprint $table) {
            $table->dropIndex(['status', 'scheduled_for']);
        });
        Schema::table('phishing_messages', function (Blueprint $table) {
            $table->dropColumn('scheduled_for');
        });
    }
};
