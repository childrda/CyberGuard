<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reported_messages', function (Blueprint $table) {
            $table->uuid('correlation_id')->nullable()->after('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('reported_messages', function (Blueprint $table) {
            $table->dropColumn('correlation_id');
        });
    }
};
