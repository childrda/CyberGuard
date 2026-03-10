<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phishing_attacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name'); // internal name, e.g. "Google account deactivation"
            $table->text('description')->nullable(); // what the phishing message mimics (for analysts)
            $table->string('subject');
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->text('html_body');
            $table->text('text_body')->nullable();
            $table->unsignedTinyInteger('difficulty_rating')->default(3); // 1=obvious, 5=very realistic
            $table->unsignedInteger('times_sent')->default(0);
            $table->unsignedInteger('times_clicked')->default(0);
            $table->string('landing_page_type')->default('training'); // training, credential_capture
            $table->foreignId('training_page_id')->nullable()->constrained('landing_pages')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'active']);
            $table->index('difficulty_rating');
        });

        Schema::create('campaign_attack', function (Blueprint $table) {
            $table->foreignId('campaign_id')->constrained('phishing_campaigns')->cascadeOnDelete();
            $table->foreignId('attack_id')->constrained('phishing_attacks')->cascadeOnDelete();
            $table->primary(['campaign_id', 'attack_id']);
        });

        Schema::table('phishing_messages', function (Blueprint $table) {
            $table->foreignId('attack_id')->nullable()->after('campaign_id')->constrained('phishing_attacks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('phishing_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attack_id');
        });
        Schema::dropIfExists('campaign_attack');
        Schema::dropIfExists('phishing_attacks');
    }
};
