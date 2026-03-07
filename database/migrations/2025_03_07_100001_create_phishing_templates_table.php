<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phishing_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->longText('html_body');
            $table->longText('text_body')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('landing_page_type')->default('training'); // training, credential_capture, custom
            $table->unsignedBigInteger('training_page_id')->nullable(); // FK added in later migration after landing_pages
            $table->string('difficulty')->default('medium'); // low, medium, high
            $table->json('tags')->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phishing_templates');
    }
};
