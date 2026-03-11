<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attack_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('attack_id')->nullable()->constrained('phishing_attacks')->nullOnDelete();
            $table->string('filename'); // stored name on disk
            $table->string('original_name'); // user-facing name
            $table->string('mime_type', 128)->nullable();
            $table->string('storage_path'); // path under storage disk
            $table->string('public_url')->nullable(); // optional CDN or signed URL
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'attack_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attack_assets');
    }
};
