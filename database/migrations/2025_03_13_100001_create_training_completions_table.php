<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phishing_message_id')->constrained('phishing_messages')->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique('phishing_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_completions');
    }
};
