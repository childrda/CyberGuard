<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel's BelongsToMany expects pivot columns named after the models
     * (phishing_campaign_id, phishing_attack_id). Rename from campaign_id/attack_id.
     */
    public function up(): void
    {
        Schema::table('campaign_attack', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['attack_id']);
            $table->dropPrimary(['campaign_id', 'attack_id']);
        });
        Schema::table('campaign_attack', function (Blueprint $table) {
            $table->renameColumn('campaign_id', 'phishing_campaign_id');
            $table->renameColumn('attack_id', 'phishing_attack_id');
        });
        Schema::table('campaign_attack', function (Blueprint $table) {
            $table->primary(['phishing_campaign_id', 'phishing_attack_id']);
            $table->foreign('phishing_campaign_id')->references('id')->on('phishing_campaigns')->cascadeOnDelete();
            $table->foreign('phishing_attack_id')->references('id')->on('phishing_attacks')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('campaign_attack', function (Blueprint $table) {
            $table->dropForeign(['phishing_campaign_id']);
            $table->dropForeign(['phishing_attack_id']);
            $table->dropPrimary(['phishing_campaign_id', 'phishing_attack_id']);
        });
        Schema::table('campaign_attack', function (Blueprint $table) {
            $table->renameColumn('phishing_campaign_id', 'campaign_id');
            $table->renameColumn('phishing_attack_id', 'attack_id');
        });
        Schema::table('campaign_attack', function (Blueprint $table) {
            $table->primary(['campaign_id', 'attack_id']);
            $table->foreign('campaign_id')->references('id')->on('phishing_campaigns')->cascadeOnDelete();
            $table->foreign('attack_id')->references('id')->on('phishing_attacks')->cascadeOnDelete();
        });
    }
};
