<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phishing_templates', function (Blueprint $table) {
            $table->foreign('training_page_id')->references('id')->on('landing_pages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('phishing_templates', function (Blueprint $table) {
            $table->dropForeign(['training_page_id']);
        });
    }
};
