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
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('favorable'); // Polymorphic: can favorite Property, Mission, etc.
            $table->timestamps();
            
            // Unique constraint to prevent duplicate favorites
            $table->unique(['user_id', 'favorable_type', 'favorable_id']);
            
            // Index for quick user favorites lookup
            $table->index(['user_id', 'favorable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
