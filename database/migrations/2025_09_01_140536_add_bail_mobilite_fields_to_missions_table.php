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
        Schema::table('missions', function (Blueprint $table) {
            $table->foreignId('bail_mobilite_id')->nullable()->constrained('bail_mobilites');
            $table->enum('mission_type', ['entry', 'exit'])->nullable();
            $table->foreignId('ops_assigned_by')->nullable()->constrained('users');
            $table->time('scheduled_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropForeign(['bail_mobilite_id']);
            $table->dropColumn('bail_mobilite_id');
            $table->dropColumn('mission_type');
            $table->dropForeign(['ops_assigned_by']);
            $table->dropColumn('ops_assigned_by');
            $table->dropColumn('scheduled_time');
        });
    }
};
