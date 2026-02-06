<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $indexName]
        );
        
        return $result[0]->count > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to users table
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'users_role_index')) {
                $table->index('role');
            }
            if (!$this->indexExists('users', 'users_created_at_index')) {
                $table->index('created_at');
            }
            if (!$this->indexExists('users', 'users_role_created_at_index')) {
                $table->index(['role', 'created_at']);
            }
        });

        // Add indexes to properties table
        Schema::table('properties', function (Blueprint $table) {
            if (!$this->indexExists('properties', 'properties_property_type_index')) {
                $table->index('property_type');
            }
            if (!$this->indexExists('properties', 'properties_created_at_index')) {
                $table->index('created_at');
            }
            if (!$this->indexExists('properties', 'properties_property_type_created_at_index')) {
                $table->index(['property_type', 'created_at']);
            }
        });

        // Add indexes to missions table
        Schema::table('missions', function (Blueprint $table) {
            if (!$this->indexExists('missions', 'missions_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('missions', 'missions_created_at_index')) {
                $table->index('created_at');
            }
        });

        // Add indexes to checklists table
        Schema::table('checklists', function (Blueprint $table) {
            if (!$this->indexExists('checklists', 'checklists_mission_id_index')) {
                $table->index('mission_id');
            }
            if (!$this->indexExists('checklists', 'checklists_created_at_index')) {
                $table->index('created_at');
            }
            if (!$this->indexExists('checklists', 'checklists_mission_id_created_at_index')) {
                $table->index(['mission_id', 'created_at']);
            }
        });

        // Add indexes to checklist_items table
        Schema::table('checklist_items', function (Blueprint $table) {
            if (!$this->indexExists('checklist_items', 'checklist_items_checklist_id_index')) {
                $table->index('checklist_id');
            }
            if (!$this->indexExists('checklist_items', 'checklist_items_amenity_id_index')) {
                $table->index('amenity_id');
            }
            if (!$this->indexExists('checklist_items', 'checklist_items_state_index')) {
                $table->index('state');
            }
            if (!$this->indexExists('checklist_items', 'checklist_items_checklist_id_state_index')) {
                $table->index(['checklist_id', 'state']);
            }
            if (!$this->indexExists('checklist_items', 'checklist_items_amenity_id_state_index')) {
                $table->index(['amenity_id', 'state']);
            }
        });

        // Add indexes to amenities table
        Schema::table('amenities', function (Blueprint $table) {
            if (!$this->indexExists('amenities', 'amenities_amenity_type_id_index')) {
                $table->index('amenity_type_id');
            }
            if (!$this->indexExists('amenities', 'amenities_name_index')) {
                $table->index('name');
            }
        });

        // Add indexes to jobs table for better queue performance
        Schema::table('jobs', function (Blueprint $table) {
            if (!$this->indexExists('jobs', 'jobs_available_at_index')) {
                $table->index('available_at');
            }
            if (!$this->indexExists('jobs', 'jobs_queue_available_at_index')) {
                $table->index(['queue', 'available_at']);
            }
        });

        // Add indexes to failed_jobs table
        Schema::table('failed_jobs', function (Blueprint $table) {
            if (!$this->indexExists('failed_jobs', 'failed_jobs_queue_index')) {
                $table->index('queue');
            }
            if (!$this->indexExists('failed_jobs', 'failed_jobs_failed_at_index')) {
                $table->index('failed_at');
            }
            if (!$this->indexExists('failed_jobs', 'failed_jobs_queue_failed_at_index')) {
                $table->index(['queue', 'failed_at']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from users table
        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'users_role_index')) {
                $table->dropIndex(['role']);
            }
            if ($this->indexExists('users', 'users_created_at_index')) {
                $table->dropIndex(['created_at']);
            }
            if ($this->indexExists('users', 'users_role_created_at_index')) {
                $table->dropIndex(['role', 'created_at']);
            }
        });

        // Remove indexes from properties table
        Schema::table('properties', function (Blueprint $table) {
            if ($this->indexExists('properties', 'properties_property_type_index')) {
                $table->dropIndex(['property_type']);
            }
            if ($this->indexExists('properties', 'properties_created_at_index')) {
                $table->dropIndex(['created_at']);
            }
            if ($this->indexExists('properties', 'properties_property_type_created_at_index')) {
                $table->dropIndex(['property_type', 'created_at']);
            }
        });

        // Remove indexes from missions table
        Schema::table('missions', function (Blueprint $table) {
            if ($this->indexExists('missions', 'missions_status_index')) {
                $table->dropIndex(['status']);
            }
            if ($this->indexExists('missions', 'missions_created_at_index')) {
                $table->dropIndex(['created_at']);
            }
        });

        // Remove indexes from checklists table
        Schema::table('checklists', function (Blueprint $table) {
            if ($this->indexExists('checklists', 'checklists_mission_id_index')) {
                $table->dropIndex(['mission_id']);
            }
            if ($this->indexExists('checklists', 'checklists_created_at_index')) {
                $table->dropIndex(['created_at']);
            }
            if ($this->indexExists('checklists', 'checklists_mission_id_created_at_index')) {
                $table->dropIndex(['mission_id', 'created_at']);
            }
        });

        // Remove indexes from checklist_items table
        Schema::table('checklist_items', function (Blueprint $table) {
            if ($this->indexExists('checklist_items', 'checklist_items_checklist_id_index')) {
                $table->dropIndex(['checklist_id']);
            }
            if ($this->indexExists('checklist_items', 'checklist_items_amenity_id_index')) {
                $table->dropIndex(['amenity_id']);
            }
            if ($this->indexExists('checklist_items', 'checklist_items_state_index')) {
                $table->dropIndex(['state']);
            }
            if ($this->indexExists('checklist_items', 'checklist_items_checklist_id_state_index')) {
                $table->dropIndex(['checklist_id', 'state']);
            }
            if ($this->indexExists('checklist_items', 'checklist_items_amenity_id_state_index')) {
                $table->dropIndex(['amenity_id', 'state']);
            }
        });

        // Remove indexes from amenities table
        Schema::table('amenities', function (Blueprint $table) {
            if ($this->indexExists('amenities', 'amenities_amenity_type_id_index')) {
                $table->dropIndex(['amenity_type_id']);
            }
            if ($this->indexExists('amenities', 'amenities_name_index')) {
                $table->dropIndex(['name']);
            }
        });

        // Remove indexes from jobs table
        Schema::table('jobs', function (Blueprint $table) {
            if ($this->indexExists('jobs', 'jobs_available_at_index')) {
                $table->dropIndex(['available_at']);
            }
            if ($this->indexExists('jobs', 'jobs_queue_available_at_index')) {
                $table->dropIndex(['queue', 'available_at']);
            }
        });

        // Remove indexes from failed_jobs table
        Schema::table('failed_jobs', function (Blueprint $table) {
            if ($this->indexExists('failed_jobs', 'failed_jobs_queue_index')) {
                $table->dropIndex(['queue']);
            }
            if ($this->indexExists('failed_jobs', 'failed_jobs_failed_at_index')) {
                $table->dropIndex(['failed_at']);
            }
            if ($this->indexExists('failed_jobs', 'failed_jobs_queue_failed_at_index')) {
                $table->dropIndex(['queue', 'failed_at']);
            }
        });
    }
};