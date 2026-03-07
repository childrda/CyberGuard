<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('slug')->unique();
            $table->text('google_credentials_path')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->json('addon_config')->nullable();
            $table->json('campaign_settings')->nullable();
            $table->json('reporting_rules')->nullable();
            $table->string('remediation_policy')->default('analyst_approval_required');
            $table->string('google_admin_user')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
