<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->json('allowed_domains')->nullable()->after('domain');
            $table->boolean('directory_sync_enabled')->default(false)->after('google_admin_user');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['allowed_domains', 'directory_sync_enabled']);
        });
    }
};
