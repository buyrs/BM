<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Amenity;
use Illuminate\Database\Seeder;

class MissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users by role using Spatie permissions
        $admin = User::role('administrators')->first();
        $ops = User::role('ops-staff')->first();
        $checker = User::role('controllers')->first();

        // Check if required users exist
        if (!$admin || !$ops || !$checker) {
            echo "Required users not found. Skipping mission seeding.\n";
            return;
        }

        // Sample missions data
        $missions = [
            [
                'title' => 'Luxury Apartment Check-in',
                'description' => 'Complete property inspection for new tenant move-in',
                'property_address' => '123 Main Street, Downtown',
                'checkin_date' => now()->addDays(2),
                'checkout_date' => now()->addDays(5),
                'status' => 'approved',
            ],
            [
                'title' => 'Studio Apartment Check-out',
                'description' => 'Final inspection before tenant departure',
                'property_address' => '456 Oak Avenue, Midtown',
                'checkin_date' => now()->subDays(1),
                'checkout_date' => now()->addDays(1),
                'status' => 'in_progress',
            ],
            [
                'title' => 'Family Home Inspection',
                'description' => 'Comprehensive property assessment for rental listing',
                'property_address' => '789 Pine Road, Suburbia',
                'checkin_date' => now()->addDays(7),
                'checkout_date' => now()->addDays(10),
                'status' => 'pending',
            ],
            [
                'title' => 'Penthouse Suite Check-in',
                'description' => 'Premium property inspection for VIP client',
                'property_address' => '321 Sky Tower, Business District',
                'checkin_date' => now()->addDays(3),
                'checkout_date' => now()->addDays(6),
                'status' => 'approved',
            ],
            [
                'title' => 'Garden Apartment Check-out',
                'description' => 'End-of-lease property evaluation',
                'property_address' => '654 Garden Lane, Residential Area',
                'checkin_date' => now()->subDays(3),
                'checkout_date' => now()->subDays(1),
                'status' => 'completed',
            ],
        ];

        foreach ($missions as $missionData) {
            // Create mission
            $mission = Mission::create([
                'title' => $missionData['title'],
                'description' => $missionData['description'],
                'property_address' => $missionData['property_address'],
                'checkin_date' => $missionData['checkin_date'],
                'checkout_date' => $missionData['checkout_date'],
                'status' => $missionData['status'],
                'admin_id' => $admin ? $admin->id : null,
                'ops_id' => $ops ? $ops->id : null,
                'checker_id' => $checker ? $checker->id : null,
            ]);

            // Create checklists for each mission
            $checklistTypes = ['checkin', 'checkout'];

            foreach ($checklistTypes as $type) {
                $checklist = Checklist::create([
                    'mission_id' => $mission->id,
                    'type' => $type,
                    'status' => $type === 'checkin' ? 'pending' : 'completed',
                ]);

                // Create checklist items for random amenities
                $amenities = Amenity::inRandomOrder()->limit(rand(5, 10))->get();

                foreach ($amenities as $amenity) {
                    $states = ['bad', 'average', 'good', 'excellent', 'need_a_fix'];
                    $comments = [
                        'Working properly',
                        'Minor wear and tear',
                        'Needs maintenance',
                        'Excellent condition',
                        'Replacement recommended',
                        'Recently serviced',
                    ];

                    ChecklistItem::create([
                        'checklist_id' => $checklist->id,
                        'amenity_id' => $amenity->id,
                        'state' => $states[array_rand($states)],
                        'comment' => $comments[array_rand($comments)],
                    ]);
                }
            }
        }

        // Create additional missions with different statuses
        for ($i = 1; $i <= 5; $i++) {
            $statuses = ['pending', 'approved', 'in_progress', 'completed', 'cancelled'];
            $status = $statuses[array_rand($statuses)];

            $mission = Mission::create([
                'title' => "Sample Mission {$i}",
                'description' => "This is a sample mission for testing purposes",
                'property_address' => "Sample Address {$i}, Test City",
                'checkin_date' => now()->addDays(rand(1, 30)),
                'checkout_date' => now()->addDays(rand(31, 60)),
                'status' => $status,
                'admin_id' => $admin ? $admin->id : null,
                'ops_id' => $ops ? $ops->id : null,
                'checker_id' => $checker ? $checker->id : null,
            ]);

            // Create checklists
            foreach (['checkin', 'checkout'] as $type) {
                $checklist = Checklist::create([
                    'mission_id' => $mission->id,
                    'type' => $type,
                    'status' => $status === 'completed' ? 'completed' : 'pending',
                ]);

                // Add some checklist items
                $amenities = Amenity::inRandomOrder()->limit(rand(3, 8))->get();
                foreach ($amenities as $amenity) {
                    ChecklistItem::create([
                        'checklist_id' => $checklist->id,
                        'amenity_id' => $amenity->id,
                        'state' => ['good', 'excellent'][array_rand(['good', 'excellent'])],
                        'comment' => 'Standard inspection completed',
                    ]);
                }
            }
        }
    }
}