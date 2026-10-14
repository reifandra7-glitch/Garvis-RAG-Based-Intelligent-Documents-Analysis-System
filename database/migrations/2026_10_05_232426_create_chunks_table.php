<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->unsignedInteger('page_number')->nullable();
            $table->longText('content');
            $table->unsignedInteger('char_count')->default(0);
            $table->unsignedBigInteger('vector_id')->nullable()->index(); // ID di FAISS
            $table->timestamps();

            $table->unique(['document_id', 'chunk_index']);
            $table->index(['document_id', 'page_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chunks');
    }
};