<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
            $table->string('area');              // kitchen, bathroom, bedroom, etc.
            $table->string('item');              // specific item like 'sink', 'wall', etc.
            $table->string('condition');         // excellent, good, fair, poor, critical
            $table->string('previous_condition')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['property_id', 'area', 'item']);
            $table->index(['property_id', 'recorded_at']);
            $table->index('condition');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_conditions');
    }
};
