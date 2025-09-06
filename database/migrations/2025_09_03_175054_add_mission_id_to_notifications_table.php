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
        Schema::table('notifications', function (Blueprint $table) {
            // Check if the column already exists to avoid duplicate column error
            if (!Schema::hasColumn('notifications', 'mission_id')) {
                $table->foreignId('mission_id')->nullable()->after('bail_mobilite_id')->constrained('missions');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Check if the column exists before trying to drop it
            if (Schema::hasColumn('notifications', 'mission_id')) {
                try {
                    $table->dropForeign(['mission_id']);
                } catch (\Exception $e) {
                    // If foreign key doesn't exist or can't be dropped, continue
                }
                $table->dropColumn('mission_id');
            }
        });
    }
};