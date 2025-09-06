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
        // This migration is a workaround for a duplicate migration issue
        // The mission_id column should already exist in the notifications table
        // We're just creating an empty migration to satisfy Laravel's migration system
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to do here
    }
};
