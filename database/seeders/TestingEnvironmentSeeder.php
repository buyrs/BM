<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\ContractTemplate;
use App\Models\IncidentReport;
use App\Models\AuditLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class TestingEnvironmentSeeder extends Seeder
{
    /**
     * Seed the application's database for testing environments.
     */
    public function run(): void
    {
        // Ensure roles exist
        $this->call(RoleSeeder::class);
        
        // Create test users for each role
        $this->createTestUsers();
        
        // Create test data
        $this->createTestBailMobilites();
        $this->createTestContractTemplates();
        $this->createTestIncidents();
        $this->createTestAuditLogs();
    }

    private function createTestUsers(): void
    {
        $users = [
            [
                'name' => 'Super Admin Test',
                'email' => 'super-admin@test.com',
                'role' => 'super-admin'
            ],
            [
                'name' => 'Admin Test',
                'email' => 'admin@test.com',
                'role' => 'admin'
            ],
            [
                'name' => 'Ops Manager Test',
                'email' => 'ops@test.com',
                'role' => 'ops'
            ],
            [
                'name' => 'Field Agent Test',
                'email' => 'checker@test.com',
                'role' => 'checker'
            ],
            [
                'name' => 'Field Agent 2 Test',
                'email' => 'checker2@test.com',
                'role' => 'checker'
            ]
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate([
                'email' => $userData['email']
            ], [
                'name' => $userData['name'],
                'password' => Hash::make('password123'),
                'email_verified_at' => now()
            ]);

            if (!$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }
        }
    }

    private function createTestBailMobilites(): void
    {
        $opsUser = User::role('ops')->first();
        $checker1 = User::role('checker')->first();
        $checker2 = User::role('checker')->skip(1)->first();

        $addresses = [
            '15 Rue de Test, 75001 Paris',
            '25 Avenue Testing, 75008 Paris',
            '35 Boulevard Test, 75006 Paris'
        ];

        $tenants = [
            ['name' => 'Alice Test', 'phone' => '+33 6 11 22 33 44', 'email' => 'alice@test.com'],
            ['name' => 'Bob Test', 'phone' => '+33 6 22 33 44 55', 'email' => 'bob@test.com'],
            ['name' => 'Charlie Test', 'phone' => '+33 6 33 44 55 66', 'email' => 'charlie@test.com']
        ];

        foreach (range(0, 2) as $i) {
            $startDate = Carbon::now()->addDays($i * 30);
            $endDate = $startDate->copy()->addMonths(6);

            $bailMobilite = BailMobilite::create([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'address' => $addresses[$i],
                'tenant_name' => $tenants[$i]['name'],
                'tenant_phone' => $tenants[$i]['phone'],
                'tenant_email' => $tenants[$i]['email'],
                'notes' => "Test bail mobilité " . ($i + 1) . " for testing purposes",
                'status' => ['assigned', 'in_progress', 'completed'][$i],
                'ops_user_id' => $opsUser->id
            ]);

            // Create entry mission
            $entryMission = Mission::create([
                'type' => 'checkin',
                'scheduled_at' => $startDate->copy()->subDays(1),
                'address' => $addresses[$i],
                'tenant_name' => $tenants[$i]['name'],
                'tenant_phone' => $tenants[$i]['phone'],
                'tenant_email' => $tenants[$i]['email'],
                'notes' => "Entry inspection for {$tenants[$i]['name']}",
                'agent_id' => $checker1->id,
                'status' => $i < 2 ? 'completed' : 'assigned'
            ]);

            // Create exit mission
            $exitMission = Mission::create([
                'type' => 'checkout',
                'scheduled_at' => $endDate->copy()->addDays(1),
                'address' => $addresses[$i],
                'tenant_name' => $tenants[$i]['name'],
                'tenant_phone' => $tenants[$i]['phone'],
                'tenant_email' => $tenants[$i]['email'],
                'notes' => "Exit inspection for {$tenants[$i]['name']}",
                'agent_id' => $checker2->id,
                'status' => $i == 0 ? 'completed' : ($i == 1 ? 'assigned' : 'unassigned')
            ]);

            // Update bail mobilite with mission IDs
            $bailMobilite->update([
                'entry_mission_id' => $entryMission->id,
                'exit_mission_id' => $exitMission->id
            ]);

            // Create checklists for completed missions
            if ($entryMission->status === 'completed') {
                Checklist::create([
                    'mission_id' => $entryMission->id,
                    'general_info' => json_encode([
                        'property_condition' => 'good',
                        'keys_received' => true,
                        'utilities_functional' => true
                    ]),
                    'rooms' => json_encode([
                        'living_room' => ['condition' => 'perfect', 'notes' => 'Clean and well maintained'],
                        'bedroom' => ['condition' => 'good', 'notes' => 'Minor scuff marks on wall'],
                        'kitchen' => ['condition' => 'good', 'notes' => 'All appliances working'],
                        'bathroom' => ['condition' => 'perfect', 'notes' => 'Recently renovated']
                    ]),
                    'utilities' => json_encode([
                        'electricity' => true,
                        'water' => true,
                        'heating' => true,
                        'internet' => true
                    ]),
                    'tenant_signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==',
                    'agent_signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==',
                    'status' => 'completed'
                ]);
            }

            if ($exitMission->status === 'completed') {
                Checklist::create([
                    'mission_id' => $exitMission->id,
                    'general_info' => json_encode([
                        'property_condition' => 'good',
                        'keys_returned' => true,
                        'final_cleaning' => true
                    ]),
                    'rooms' => json_encode([
                        'living_room' => ['condition' => 'good', 'notes' => 'Normal wear and tear'],
                        'bedroom' => ['condition' => 'good', 'notes' => 'Clean, ready for next tenant'],
                        'kitchen' => ['condition' => 'perfect', 'notes' => 'Deep cleaned'],
                        'bathroom' => ['condition' => 'good', 'notes' => 'All fixtures working']
                    ]),
                    'utilities' => json_encode([
                        'electricity' => false,
                        'water' => false,
                        'heating' => false,
                        'internet' => false
                    ]),
                    'tenant_signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==',
                    'agent_signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==',
                    'status' => 'completed'
                ]);
            }
        }
    }

    private function createTestContractTemplates(): void
    {
        $adminUser = User::role('admin')->first() ?? User::role('super-admin')->first();

        $templates = [
            [
                'name' => 'Standard Entry Contract Template',
                'type' => 'entry',
                'content' => 'This is a test entry contract template for bail mobilité. It contains all necessary legal clauses and terms for property entry inspections.'
            ],
            [
                'name' => 'Standard Exit Contract Template', 
                'type' => 'exit',
                'content' => 'This is a test exit contract template for bail mobilité. It contains all necessary legal clauses and terms for property exit inspections and damage assessments.'
            ],
            [
                'name' => 'Premium Entry Contract Template',
                'type' => 'entry',
                'content' => 'Premium entry contract template with additional clauses for high-value properties and extended liability coverage.'
            ]
        ];

        foreach ($templates as $template) {
            ContractTemplate::firstOrCreate([
                'name' => $template['name'],
                'type' => $template['type']
            ], [
                'content' => $template['content'],
                'is_active' => true,
                'created_by' => $adminUser->id
            ]);
        }
    }

    private function createTestIncidents(): void
    {
        $bailMobilite = BailMobilite::first();
        $mission = Mission::first();
        $checklist = Checklist::first();
        $user = User::role('checker')->first();

        if ($bailMobilite && $mission && $checklist) {
            $incidents = [
                [
                    'type' => 'property_damage',
                    'severity' => 'medium',
                    'title' => 'Minor Wall Damage',
                    'description' => 'Small hole in bedroom wall, likely from picture hanging.',
                    'status' => 'resolved'
                ],
                [
                    'type' => 'utility_issue',
                    'severity' => 'high',
                    'title' => 'Water Leak in Bathroom',
                    'description' => 'Active leak under bathroom sink requiring immediate attention.',
                    'status' => 'in_progress'
                ],
                [
                    'type' => 'maintenance_required',
                    'severity' => 'low',
                    'title' => 'HVAC Filter Replacement',
                    'description' => 'Air conditioning filter needs replacement for optimal performance.',
                    'status' => 'open'
                ]
            ];

            foreach ($incidents as $incident) {
                IncidentReport::create([
                    'bail_mobilite_id' => $bailMobilite->id,
                    'mission_id' => $mission->id,
                    'checklist_id' => $checklist->id,
                    'type' => $incident['type'],
                    'severity' => $incident['severity'],
                    'title' => $incident['title'],
                    'description' => $incident['description'],
                    'status' => $incident['status'],
                    'detected_at' => now()->subDays(rand(1, 7)),
                    'created_by' => $user->id,
                    'metadata' => json_encode([
                        'estimated_cost' => rand(50, 500),
                        'priority' => $incident['severity'],
                        'photos' => []
                    ])
                ]);
            }
        }
    }

    private function createTestAuditLogs(): void
    {
        $users = User::all();
        
        $actions = [
            ['event_type' => 'login', 'action' => 'user_login', 'severity' => 'info'],
            ['event_type' => 'create', 'action' => 'mission_created', 'severity' => 'info'],
            ['event_type' => 'update', 'action' => 'checklist_updated', 'severity' => 'info'],
            ['event_type' => 'delete', 'action' => 'template_deleted', 'severity' => 'warning'],
            ['event_type' => 'view', 'action' => 'sensitive_data_accessed', 'severity' => 'info', 'is_sensitive' => true]
        ];

        foreach (range(1, 20) as $i) {
            $user = $users->random();
            $action = $actions[array_rand($actions)];
            
            AuditLog::create([
                'event_type' => $action['event_type'],
                'auditable_type' => 'App\\Models\\Mission',
                'auditable_id' => rand(1, 10),
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_roles' => json_encode($user->getRoleNames()->toArray()),
                'action' => $action['action'],
                'old_values' => $action['event_type'] === 'update' ? json_encode(['status' => 'assigned']) : null,
                'new_values' => $action['event_type'] === 'update' ? json_encode(['status' => 'completed']) : null,
                'metadata' => json_encode([
                    'browser' => 'Chrome',
                    'device' => 'desktop',
                    'location' => 'Paris, France'
                ]),
                'ip_address' => '192.168.1.' . rand(1, 255),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'session_id' => 'sess_' . uniqid(),
                'request_id' => 'req_' . uniqid(),
                'route_name' => 'missions.update',
                'url' => '/missions/' . rand(1, 10),
                'http_method' => $action['event_type'] === 'create' ? 'POST' : ($action['event_type'] === 'update' ? 'PUT' : 'GET'),
                'response_status' => 200,
                'severity' => $action['severity'],
                'is_sensitive' => $action['is_sensitive'] ?? false,
                'occurred_at' => now()->subDays(rand(0, 30))
            ]);
        }
    }
}
