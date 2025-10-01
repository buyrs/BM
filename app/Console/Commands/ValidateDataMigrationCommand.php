<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Mission;
use App\Models\Checklist;

class ValidateDataMigrationCommand extends Command
{
    protected $signature = 'migrate:validate 
                            {--verbose : Show detailed validation information}
                            {--quick : Run only quick validation checks}';

    protected $description = 'Validate data migration integrity';

    public function handle()
    {
        $verbose = $this->option('verbose');
        $quick = $this->option('quick');

        $this->info('Starting data migration validation...');
        
        if ($quick) {
            $this->info('Running quick validation checks only.');
        }

        $results = [];

        // Validate users
        $results['users'] = $this->validateUsers($verbose);
        
        if (!$quick) {
            // Validate missions
            $results['missions'] = $this->validateMissions($verbose);
            
            // Validate checklists
            $results['checklists'] = $this->validateChecklists($verbose);
            
            // Validate relationships
            $results['relationships'] = $this->validateRelationships($verbose);
        }

        // Display summary
        $this->displayValidationSummary($results);

        // Check for critical failures
        $criticalFailures = collect($results)->filter(function ($result) {
            return isset($result['critical']) && $result['critical'];
        })->count();

        if ($criticalFailures > 0) {
            $this->error("Validation failed with {$criticalFailures} critical issues!");
            return 1;
        }

        $this->info('Validation completed successfully!');
        return 0;
    }

    private function validateUsers($verbose)
    {
        $this->info('Validating users...');
        
        $totalUsers = User::count();
        $adminUsers = User::where('role', 'admin')->count();
        $opsUsers = User::where('role', 'ops')->count();
        $checkerUsers = User::where('role', 'checker')->count();
        $usersWithoutRole = User::whereNull('role')->count();
        
        if ($verbose) {
            $this->line("Total users: {$totalUsers}");
            $this->line("Admin users: {$adminUsers}");
            $this->line("Ops users: {$opsUsers}");
            $this->line("Checker users: {$checkerUsers}");
            $this->line("Users without role: {$usersWithoutRole}");
        }
        
        $valid = $usersWithoutRole === 0;
        
        if (!$valid) {
            $this->warn("Found {$usersWithoutRole} users without assigned roles!");
        }
        
        return [
            'valid' => $valid,
            'details' => [
                'total' => $totalUsers,
                'admins' => $adminUsers,
                'ops' => $opsUsers,
                'checkers' => $checkerUsers,
                'without_role' => $usersWithoutRole
            ]
        ];
    }

    private function validateMissions($verbose)
    {
        $this->info('Validating missions...');
        
        $totalMissions = Mission::count();
        $approvedMissions = Mission::where('status', 'approved')->count();
        $pendingMissions = Mission::where('status', 'pending')->count();
        $completedMissions = Mission::where('status', 'completed')->count();
        $missionsWithoutAdmin = Mission::whereNull('admin_id')->count();
        $missionsWithoutChecker = Mission::whereNull('checker_id')->count();
        
        if ($verbose) {
            $this->line("Total missions: {$totalMissions}");
            $this->line("Approved missions: {$approvedMissions}");
            $this->line("Pending missions: {$pendingMissions}");
            $this->line("Completed missions: {$completedMissions}");
            $this->line("Missions without admin: {$missionsWithoutAdmin}");
            $this->line("Missions without checker: {$missionsWithoutChecker}");
        }
        
        $valid = $missionsWithoutAdmin === 0 && $missionsWithoutChecker === 0;
        
        if (!$valid) {
            $this->warn("Found missions without required relationships!");
        }
        
        return [
            'valid' => $valid,
            'details' => [
                'total' => $totalMissions,
                'approved' => $approvedMissions,
                'pending' => $pendingMissions,
                'completed' => $completedMissions,
                'without_admin' => $missionsWithoutAdmin,
                'without_checker' => $missionsWithoutChecker
            ]
        ];
    }

    private function validateChecklists($verbose)
    {
        $this->info('Validating checklists...');
        
        $totalChecklists = Checklist::count();
        $checkinChecklists = Checklist::where('type', 'checkin')->count();
        $checkoutChecklists = Checklist::where('type', 'checkout')->count();
        $pendingChecklists = Checklist::where('status', 'pending')->count();
        $completedChecklists = Checklist::where('status', 'completed')->count();
        $checklistsWithoutMission = Checklist::whereNull('mission_id')->count();
        
        if ($verbose) {
            $this->line("Total checklists: {$totalChecklists}");
            $this->line("Check-in checklists: {$checkinChecklists}");
            $this->line("Check-out checklists: {$checkoutChecklists}");
            $this->line("Pending checklists: {$pendingChecklists}");
            $this->line("Completed checklists: {$completedChecklists}");
            $this->line("Checklists without mission: {$checklistsWithoutMission}");
        }
        
        $valid = $checklistsWithoutMission === 0;
        
        if (!$valid) {
            $this->warn("Found checklists without mission relationships!");
        }
        
        return [
            'valid' => $valid,
            'details' => [
                'total' => $totalChecklists,
                'checkin' => $checkinChecklists,
                'checkout' => $checkoutChecklists,
                'pending' => $pendingChecklists,
                'completed' => $completedChecklists,
                'without_mission' => $checklistsWithoutMission
            ]
        ];
    }

    private function validateRelationships($verbose)
    {
        $this->info('Validating relationships...');
        
        // Validate that all missions have associated checklists
        $missionsWithoutChecklists = Mission::doesntHave('checklists')->count();
        
        // Validate that all checklists have associated items
        $checklistsWithoutItems = Checklist::doesntHave('checklistItems')->count();
        
        if ($verbose) {
            $this->line("Missions without checklists: {$missionsWithoutChecklists}");
            $this->line("Checklists without items: {$checklistsWithoutItems}");
        }
        
        $valid = $missionsWithoutChecklists === 0 && $checklistsWithoutItems === 0;
        
        if (!$valid) {
            $this->warn("Found incomplete relationship chains!");
        }
        
        return [
            'valid' => $valid,
            'details' => [
                'missions_without_checklists' => $missionsWithoutChecklists,
                'checklists_without_items' => $checklistsWithoutItems
            ]
        ];
    }

    private function displayValidationSummary($results)
    {
        $this->info('');
        $this->info('=== Validation Summary ===');
        
        foreach ($results as $entity => $result) {
            $status = $result['valid'] ? '✓ PASS' : '✗ FAIL';
            $this->line("{$entity}: {$status}");
            
            if (isset($result['details'])) {
                foreach ($result['details'] as $key => $value) {
                    $this->line("  {$key}: {$value}");
                }
            }
        }
    }
}