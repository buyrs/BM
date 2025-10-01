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
        // Add indexes to users table
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
            $table->index('created_at');
            $table->index(['role', 'created_at']);
        });

        // Add indexes to properties table
        Schema::table('properties', function (Blueprint $table) {
            $table->index('property_type');
            $table->index('created_at');
            $table->index(['property_type', 'created_at']);
        });

        // Add indexes to missions table
        Schema::table('missions', function (Blueprint $table) {
            $table->index('status');
            $table->index('assigned_to');
            $table->index('property_id');
            $table->index('created_at');
            $table->index(['status', 'assigned_to']);
            $table->index(['property_id', 'status']);
            $table->index(['assigned_to', 'created_at']);
        });

        // Add indexes to checklists table
        Schema::table('checklists', function (Blueprint $table) {
            $table->index('mission_id');
            $table->index('created_at');
            $table->index(['mission_id', 'created_at']);
        });

        // Add indexes to checklist_items table
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->index('checklist_id');
            $table->index('amenity_id');
            $table->index('status');
            $table->index(['checklist_id', 'status']);
            $table->index(['amenity_id', 'status']);
        });

        // Add indexes to amenities table
        Schema::table('amenities', function (Blueprint $table) {
            $table->index('amenity_type_id');
            $table->index('name');
        });

        // Add indexes to jobs table for better queue performance
        Schema::table('jobs', function (Blueprint $table) {
            $table->index('available_at');
            $table->index(['queue', 'available_at']);
        });

        // Add indexes to failed_jobs table
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->index('queue');
            $table->index('failed_at');
            $table->index(['queue', 'failed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['role', 'created_at']);
        });

        // Remove indexes from properties table
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['property_type']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['property_type', 'created_at']);
        });

        // Remove indexes from missions table
        Schema::table('missions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['assigned_to']);
            $table->dropIndex(['property_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status', 'assigned_to']);
            $table->dropIndex(['property_id', 'status']);
            $table->dropIndex(['assigned_to', 'created_at']);
        });

        // Remove indexes from checklists table
        Schema::table('checklists', function (Blueprint $table) {
            $table->dropIndex(['mission_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['mission_id', 'created_at']);
        });

        // Remove indexes from checklist_items table
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropIndex(['checklist_id']);
            $table->dropIndex(['amenity_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['checklist_id', 'status']);
            $table->dropIndex(['amenity_id', 'status']);
        });

        // Remove indexes from amenities table
        Schema::table('amenities', function (Blueprint $table) {
            $table->dropIndex(['amenity_type_id']);
            $table->dropIndex(['name']);
        });

        // Remove indexes from jobs table
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['available_at']);
            $table->dropIndex(['queue', 'available_at']);
        });

        // Remove indexes from failed_jobs table
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->dropIndex(['queue']);
            $table->dropIndex(['failed_at']);
            $table->dropIndex(['queue', 'failed_at']);
        });
    }
};