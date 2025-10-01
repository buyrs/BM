<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Mission;
use App\Models\Checklist;

class RollbackDataMigrationCommand extends Command
{
    protected $signature = 'migrate:rollback-data 
                            {--steps=1 : Number of migration steps to rollback}
                            {--force : Force rollback without confirmation}
                            {--preserve-users : Preserve user data during rollback}';

    protected $description = 'Rollback data migration';

    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to rollback the data migration? This will delete migrated data.')) {
            $this->info('Rollback cancelled.');
            return 0;
        }

        $steps = $this->option('steps');
        $preserveUsers = $this->option('preserve-users');

        $this->info("Rolling back data migration (steps: {$steps})...");

        try {
            DB::beginTransaction();

            if ($steps >= 1) {
                $this->rollbackChecklists($preserveUsers);
            }

            if ($steps >= 2) {
                $this->rollbackMissions($preserveUsers);
            }

            if ($steps >= 3 && !$preserveUsers) {
                $this->rollbackUsers();
            }

            DB::commit();

            $this->info('Rollback completed successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Rollback failed: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    private function rollbackChecklists($preserveUsers)
    {
        $this->info('Rolling back checklists...');
        
        // Delete checklist items
        DB::table('checklist_items')->truncate();
        
        // Delete checklists
        if (!$preserveUsers) {
            DB::table('checklists')->truncate();
            $this->info('Checklists rolled back.');
        } else {
            // Only delete migrated checklists
            DB::table('checklists')->whereNotNull('legacy_id')->delete();
            $this->info('Migrated checklists rolled back.');
        }
    }

    private function rollbackMissions($preserveUsers)
    {
        $this->info('Rolling back missions...');
        
        if (!$preserveUsers) {
            DB::table('missions')->truncate();
            $this->info('Missions rolled back.');
        } else {
            // Only delete migrated missions
            DB::table('missions')->whereNotNull('legacy_id')->delete();
            $this->info('Migrated missions rolled back.');
        }
    }

    private function rollbackUsers()
    {
        $this->info('Rolling back users...');
        
        // Only delete migrated users (keep original users)
        $deleted = DB::table('users')->whereNotNull('legacy_id')->delete();
        
        $this->info("Rolled back {$deleted} migrated users.");
    }
}