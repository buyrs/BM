<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('file_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_name');
            $table->string('path');
            $table->bigInteger('size');
            $table->string('mime_type');
            $table->string('file_hash')->nullable();
            $table->json('metadata')->nullable(); // For image dimensions, etc.
            $table->foreignId('property_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('mission_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('checklist_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->string('storage_disk')->default('local');
            $table->boolean('is_public')->default(false);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['property_id', 'mission_id']);
            $table->index(['uploaded_by', 'created_at']);
            $table->index(['mime_type']);
            $table->index(['file_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_metadata');
    }
};
