<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        if (\DB::getDriverName() === 'sqlite') {
            $indexes = \DB::select("PRAGMA index_list({$table})");
            foreach ($indexes as $idx) {
                if ($idx->name === $index) {
                    return true;
                }
            }
            return false;
        } else {
            $indexes = \DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
            return count($indexes) > 0;
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add performance indexes to missions table
        Schema::table('missions', function (Blueprint $table) {
            if (!$this->indexExists('missions', 'missions_status_scheduled_idx')) {
                $table->index(['status', 'scheduled_at'], 'missions_status_scheduled_idx');
            }
            if (!$this->indexExists('missions', 'missions_agent_status_idx')) {
                $table->index(['agent_id', 'status'], 'missions_agent_status_idx');
            }
            if (!$this->indexExists('missions', 'missions_type_scheduled_idx')) {
                $table->index(['type', 'scheduled_at'], 'missions_type_scheduled_idx');
            }
        });

        // Add performance indexes to checklists table  
        Schema::table('checklists', function (Blueprint $table) {
            if (!$this->indexExists('checklists', 'checklists_status_created_idx')) {
                $table->index(['status', 'created_at'], 'checklists_status_created_idx');
            }
            if (!$this->indexExists('checklists', 'checklists_mission_status_idx')) {
                $table->index(['mission_id', 'status'], 'checklists_mission_status_idx');
            }
        });

        // Add performance indexes to bail_mobilites table
        Schema::table('bail_mobilites', function (Blueprint $table) {
            if (!$this->indexExists('bail_mobilites', 'bail_mobilites_status_start_idx')) {
                $table->index(['status', 'start_date'], 'bail_mobilites_status_start_idx');
            }
            if (!$this->indexExists('bail_mobilites', 'bail_mobilites_ops_status_idx')) {
                $table->index(['ops_user_id', 'status'], 'bail_mobilites_ops_status_idx');
            }
            if (!$this->indexExists('bail_mobilites', 'bail_mobilites_date_range_idx')) {
                $table->index(['start_date', 'end_date'], 'bail_mobilites_date_range_idx');
            }
        });

        // Add performance indexes to notifications table
        Schema::table('notifications', function (Blueprint $table) {
            if (!$this->indexExists('notifications', 'notifications_recipient_status_idx')) {
                $table->index(['recipient_id', 'status'], 'notifications_recipient_status_idx');
            }
            if (!$this->indexExists('notifications', 'notifications_created_status_idx')) {
                $table->index(['created_at', 'status'], 'notifications_created_status_idx');
            }
        });

        // Add performance indexes to contract_templates table
        Schema::table('contract_templates', function (Blueprint $table) {
            if (!$this->indexExists('contract_templates', 'contract_templates_active_type_idx')) {
                $table->index(['is_active', 'type'], 'contract_templates_active_type_idx');
            }
            if (!$this->indexExists('contract_templates', 'contract_templates_creator_date_idx')) {
                $table->index(['created_by', 'created_at'], 'contract_templates_creator_date_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            if ($this->indexExists('missions', 'missions_status_scheduled_idx')) {
                $table->dropIndex('missions_status_scheduled_idx');
            }
            if ($this->indexExists('missions', 'missions_agent_status_idx')) {
                $table->dropIndex('missions_agent_status_idx');
            }
            if ($this->indexExists('missions', 'missions_type_scheduled_idx')) {
                $table->dropIndex('missions_type_scheduled_idx');
            }
        });

        Schema::table('checklists', function (Blueprint $table) {
            if ($this->indexExists('checklists', 'checklists_status_created_idx')) {
                $table->dropIndex('checklists_status_created_idx');
            }
            if ($this->indexExists('checklists', 'checklists_mission_status_idx')) {
                $table->dropIndex('checklists_mission_status_idx');
            }
        });

        Schema::table('bail_mobilites', function (Blueprint $table) {
            if ($this->indexExists('bail_mobilites', 'bail_mobilites_status_start_idx')) {
                $table->dropIndex('bail_mobilites_status_start_idx');
            }
            if ($this->indexExists('bail_mobilites', 'bail_mobilites_ops_status_idx')) {
                $table->dropIndex('bail_mobilites_ops_status_idx');
            }
            if ($this->indexExists('bail_mobilites', 'bail_mobilites_date_range_idx')) {
                $table->dropIndex('bail_mobilites_date_range_idx');
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            if ($this->indexExists('notifications', 'notifications_recipient_status_idx')) {
                $table->dropIndex('notifications_recipient_status_idx');
            }
            if ($this->indexExists('notifications', 'notifications_created_status_idx')) {
                $table->dropIndex('notifications_created_status_idx');
            }
        });

        Schema::table('contract_templates', function (Blueprint $table) {
            if ($this->indexExists('contract_templates', 'contract_templates_active_type_idx')) {
                $table->dropIndex('contract_templates_active_type_idx');
            }
            if ($this->indexExists('contract_templates', 'contract_templates_creator_date_idx')) {
                $table->dropIndex('contract_templates_creator_date_idx');
            }
        });
    }
};
