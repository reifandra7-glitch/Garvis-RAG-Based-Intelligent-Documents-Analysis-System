<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name')->unique();
            $table->string('path');
            $table->string('extension', 10);
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');                // bytes
            $table->string('checksum', 64)->nullable();        // sha256, cegah duplikat
            $table->unsignedInteger('page_count')->default(0);
            $table->unsignedInteger('chunk_count')->default(0);
            $table->enum('status', ['pending', 'processing', 'processed', 'failed'])
                  ->default('pending');
            $table->text('error_message')->nullable();
            $table->boolean('is_guideline')->default(false);   // dokumen acuan KF-08
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};