<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key', 64)->unique();
            $table->string('hash')->unique(); // store hashed key for verification
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_secrets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('gmail_addon');
            $table->string('secret_hash'); // hashed shared secret
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_secrets');
        Schema::dropIfExists('api_keys');
    }
};
