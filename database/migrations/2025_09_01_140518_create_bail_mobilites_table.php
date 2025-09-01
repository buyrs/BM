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
        Schema::create('bail_mobilites', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('address');
            $table->string('tenant_name');
            $table->string('tenant_phone')->nullable();
            $table->string('tenant_email')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'incident'])->default('assigned');
            $table->foreignId('ops_user_id')->nullable()->constrained('users');
            $table->foreignId('entry_mission_id')->nullable()->constrained('missions');
            $table->foreignId('exit_mission_id')->nullable()->constrained('missions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bail_mobilites');
    }
};
