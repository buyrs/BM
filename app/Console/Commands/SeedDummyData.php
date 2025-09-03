<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ComprehensiveDummyDataSeeder;

class SeedDummyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:dummy-data {--fresh : Clear existing data before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with comprehensive dummy data for all features';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('fresh')) {
            $this->info('🗑️  Clearing existing data...');
            
            // Clear data in reverse dependency order
            \App\Models\ChecklistPhoto::truncate();
            \App\Models\ChecklistItem::truncate();
            \App\Models\CorrectiveAction::truncate();
            \App\Models\IncidentReport::truncate();
            \App\Models\Notification::truncate();
            \App\Models\BailMobiliteSignature::truncate();
            \App\Models\Checklist::truncate();
            \App\Models\Mission::truncate();
            \App\Models\BailMobilite::truncate();
            \App\Models\ContractTemplate::truncate();
            
            // Clear users except super admin
            \App\Models\User::whereDoesntHave('roles', function($query) {
                $query->where('name', 'super-admin');
            })->delete();
            
            $this->info('✅ Existing data cleared');
        }

        $this->info('🚀 Starting comprehensive dummy data seeding...');
        
        $seeder = new ComprehensiveDummyDataSeeder();
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->newLine();
        $this->info('📊 Data Summary:');
        $this->table(
            ['Model', 'Count'],
            [
                ['Users', \App\Models\User::count()],
                ['Bail Mobilités', \App\Models\BailMobilite::count()],
                ['Missions', \App\Models\Mission::count()],
                ['Checklists', \App\Models\Checklist::count()],
                ['Checklist Items', \App\Models\ChecklistItem::count()],
                ['Checklist Photos', \App\Models\ChecklistPhoto::count()],
                ['Contract Templates', \App\Models\ContractTemplate::count()],
                ['Signatures', \App\Models\BailMobiliteSignature::count()],
                ['Notifications', \App\Models\Notification::count()],
                ['Incident Reports', \App\Models\IncidentReport::count()],
                ['Corrective Actions', \App\Models\CorrectiveAction::count()],
            ]
        );
        
        $this->newLine();
        $this->info('🎉 Comprehensive dummy data seeding completed successfully!');
        $this->info('💡 You can now test all features with realistic data.');
        
        return Command::SUCCESS;
    }
}