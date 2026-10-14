<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('history_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->text('question');
            $table->enum('mode', ['qa', 'compliance'])->default('qa'); // qa = KF-05, compliance = KF-08
            $table->timestamps();

            $table->index(['history_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queries');
    }
};