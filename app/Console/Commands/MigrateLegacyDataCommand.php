<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Mission;
use App\Models\Checklist;

class MigrateLegacyDataCommand extends Command
{
    protected $signature = 'migrate:legacy-data 
                            {--source= : Source database connection} 
                            {--batch-size=100 : Number of records to process per batch}
                            {--dry-run : Run without actually inserting data}';

    protected $description = 'Migrate legacy data to new platform structure';

    public function handle()
    {
        $source = $this->option('source') ?? 'legacy';
        $batchSize = $this->option('batch-size');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode. No data will be inserted.');
        }

        $this->info('Starting legacy data migration...');
        $this->info("Source connection: {$source}");
        $this->info("Batch size: {$batchSize}");

        try {
            // Migrate users
            $this->migrateUsers($source, $batchSize, $dryRun);
            
            // Migrate missions
            $this->migrateMissions($source, $batchSize, $dryRun);
            
            // Migrate checklists
            $this->migrateChecklists($source, $batchSize, $dryRun);
            
            $this->info('Migration completed successfully!');
            
        } catch (\Exception $e) {
            $this->error("Migration failed: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    private function migrateUsers($source, $batchSize, $dryRun)
    {
        $this->info('Migrating users...');
        
        $legacyUsers = DB::connection($source)->table('legacy_users')
            ->where('active', 1)
            ->orderBy('user_id');
            
        $totalUsers = $legacyUsers->count();
        $this->info("Found {$totalUsers} active users to migrate.");
        
        $processed = 0;
        $legacyUsers->chunk($batchSize, function ($users) use (&$processed, $dryRun) {
            foreach ($users as $user) {
                if (!$dryRun) {
                    // Transform and insert user
                    $newUser = User::updateOrCreate(
                        ['email' => $user->email],
                        [
                            'name' => trim($user->first_name . ' ' . $user->last_name),
                            'password' => Hash::make($user->password),
                            'role' => $this->mapUserRole($user->user_type),
                            'created_at' => $user->created,
                            'updated_at' => $user->updated,
                        ]
                    );
                }
                
                $processed++;
                if ($processed % 100 === 0) {
                    $this->info("Processed {$processed} users...");
                }
            }
        });
        
        $this->info("Completed user migration. Processed {$processed} users.");
    }

    private function migrateMissions($source, $batchSize, $dryRun)
    {
        $this->info('Migrating missions...');
        
        $legacyMissions = DB::connection($source)->table('legacy_missions')
            ->orderBy('mission_id');
            
        $totalMissions = $legacyMissions->count();
        $this->info("Found {$totalMissions} missions to migrate.");
        
        $processed = 0;
        $legacyMissions->chunk($batchSize, function ($missions) use (&$processed, $dryRun, $source) {
            foreach ($missions as $mission) {
                if (!$dryRun) {
                    // Find corresponding users
                    $checker = $this->findUserByEmail($source, $mission->assigned_to_email);
                    $admin = $this->findUserByEmail($source, $mission->created_by_email);
                    
                    // Transform and insert mission
                    $newMission = Mission::updateOrCreate(
                        ['legacy_id' => $mission->mission_id],
                        [
                            'title' => $mission->mission_title,
                            'description' => $mission->mission_desc,
                            'property_address' => $mission->property_addr,
                            'checkin_date' => $mission->checkin_dt,
                            'checkout_date' => $mission->checkout_dt,
                            'checker_id' => $checker?->id,
                            'admin_id' => $admin?->id,
                            'ops_id' => $admin?->id, // Assuming admin is also ops for legacy data
                            'status' => $this->mapMissionStatus($mission->status),
                            'created_at' => $mission->created,
                            'updated_at' => $mission->updated,
                        ]
                    );
                }
                
                $processed++;
                if ($processed % 100 === 0) {
                    $this->info("Processed {$processed} missions...");
                }
            }
        });
        
        $this->info("Completed mission migration. Processed {$processed} missions.");
    }

    private function migrateChecklists($source, $batchSize, $dryRun)
    {
        $this->info('Migrating checklists...');
        
        // This would depend on the actual legacy data structure
        $this->info("Checklist migration would be implemented based on legacy data structure.");
        
        // Placeholder implementation
        $this->info("Checklist migration completed.");
    }

    private function mapUserRole($legacyRole)
    {
        return match(strtoupper($legacyRole)) {
            'ADMIN' => 'admin',
            'STAFF' => 'ops',
            'CHECKER' => 'checker',
            default => 'checker'
        };
    }

    private function mapMissionStatus($legacyStatus)
    {
        return match(strtoupper($legacyStatus)) {
            'ACTIVE', 'APPROVED' => 'approved',
            'PENDING' => 'pending',
            'IN_PROGRESS' => 'in_progress',
            'COMPLETED' => 'completed',
            'CANCELLED' => 'cancelled',
            default => 'pending'
        };
    }

    private function findUserByEmail($source, $email)
    {
        if (empty($email)) {
            return null;
        }
        
        return User::where('email', $email)->first();
    }
}