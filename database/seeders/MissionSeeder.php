<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mission;
use App\Models\User;

class MissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing checkers
        $checkers = User::role('checker')->get();

        if ($checkers->isEmpty()) {
            // Create a default checker if none exist
            $checker = User::create([
                'name' => 'Default Checker',
                'email' => 'default.checker@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            $checker->assignRole('checker');
            $checkers = collect([$checker]);
        }

        // Create 10 dummy missions
        Mission::factory(10)->create()->each(function ($mission) use ($checkers) {
            // Assign some missions to checkers
            if (rand(0, 1) && $checkers->isNotEmpty()) {
                $mission->agent_id = $checkers->random()->id;
                $mission->status = 'assigned';
                $mission->save();
            }
            // Create a checklist for each mission
            $checklist = \App\Models\Checklist::factory()->create([
                'mission_id' => $mission->id,
            ]);
            // Create 5-10 checklist items for each checklist
            \App\Models\ChecklistItem::factory(rand(5, 10))->create([
                'checklist_id' => $checklist->id,
            ])->each(function ($item) {
                // Attach 0-3 photos to each item
                \App\Models\ChecklistPhoto::factory(rand(0, 3))->create([
                    'checklist_item_id' => $item->id,
                ]);
            });
        });

        // Create a few completed missions for the checker dashboard
        if ($checkers->isNotEmpty()) {
            Mission::factory(3)->create([
                'agent_id' => $checkers->first()->id,
                'status' => 'completed',
            ])->each(function ($mission) {
                $checklist = \App\Models\Checklist::factory()->create([
                    'mission_id' => $mission->id,
                ]);
                \App\Models\ChecklistItem::factory(rand(5, 10))->create([
                    'checklist_id' => $checklist->id,
                ])->each(function ($item) {
                    \App\Models\ChecklistPhoto::factory(rand(0, 3))->create([
                        'checklist_item_id' => $item->id,
                    ]);
                });
            });
        }
    }
} 