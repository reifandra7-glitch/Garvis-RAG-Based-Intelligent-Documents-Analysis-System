<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('query_id')->constrained()->cascadeOnDelete();
            $table->longText('answer');
            $table->boolean('is_grounded')->default(true);
            $table->json('sources')->nullable();
            $table->string('model_used')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->enum('status', ['success', 'not_found', 'error'])->default('success');
            $table->timestamps();

            $table->unique('query_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};