<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();          // contoh: llama3:8b
            $table->string('display_name');            // contoh: Llama 3 (8B)
            $table->string('provider')->default('ollama');
            $table->string('size')->nullable();        // contoh: 4.7 GB
            $table->json('options')->nullable();       // temperature, num_ctx, dll
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};