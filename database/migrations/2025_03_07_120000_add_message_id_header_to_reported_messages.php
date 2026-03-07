<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reported_messages', function (Blueprint $table) {
            $table->string('message_id_header', 512)->nullable()->after('headers');
        });
    }

    public function down(): void
    {
        Schema::table('reported_messages', function (Blueprint $table) {
            $table->dropColumn('message_id_header');
        });
    }
};
