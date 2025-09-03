<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\ChecklistPhoto;
use App\Models\ContractTemplate;
use App\Models\BailMobiliteSignature;
use App\Models\Notification;
use App\Models\IncidentReport;
use App\Models\CorrectiveAction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ComprehensiveDummyDataSeeder extends Seeder
{
    private array $addresses = [
        '123 Rue de la Paix, 75001 Paris',
        '45 Avenue des Champs-Élysées, 75008 Paris',
        '78 Boulevard Saint-Germain, 75006 Paris',
        '12 Rue de Rivoli, 75004 Paris',
        '34 Avenue Montaigne, 75008 Paris',
        '56 Rue du Faubourg Saint-Honoré, 75008 Paris',
        '89 Boulevard Haussmann, 75009 Paris',
        '23 Rue de la République, 69002 Lyon',
        '67 Cours Mirabeau, 13100 Aix-en-Provence',
        '91 Promenade des Anglais, 06000 Nice'
    ];

    private array $tenantNames = [
        'Marie Dubois', 'Jean Martin', 'Sophie Leroy', 'Pierre Moreau',
        'Camille Bernard', 'Lucas Petit', 'Emma Durand', 'Hugo Roux',
        'Léa Fournier', 'Nathan Girard', 'Chloé Bonnet', 'Maxime Lambert',
        'Manon Rousseau', 'Antoine Lefebvre', 'Jade Mercier'
    ];

    private array $phoneNumbers = [
        '+33 6 12 34 56 78', '+33 6 23 45 67 89', '+33 6 34 56 78 90',
        '+33 6 45 67 89 01', '+33 6 56 78 90 12', '+33 6 67 89 01 23',
        '+33 6 78 90 12 34', '+33 6 89 01 23 45', '+33 6 90 12 34 56'
    ];

    public function run(): void
    {
        $this->command->info('🚀 Starting comprehensive dummy data seeding...');

        // Create users for all roles
        $users = $this->createUsers();
        $this->command->info('✅ Created users for all roles');

        // Create contract templates
        $contractTemplates = $this->createContractTemplates($users['admin']);
        $this->command->info('✅ Created contract templates');

        // Create bail mobilités in all states
        $bailMobilites = $this->createBailMobilites($users['ops']);
        $this->command->info('✅ Created bail mobilités in all status states');

        // Create missions with realistic scheduling
        $missions = $this->createMissions($bailMobilites, $users['checkers'], $users['ops']);
        $this->command->info('✅ Created missions with realistic scheduling');

        // Create completed checklists with photos
        $this->createChecklists($missions, $users['checkers']);
        $this->command->info('✅ Created completed checklists with photos');

        // Create signatures and contracts
        $this->createSignatures($bailMobilites, $contractTemplates);
        $this->command->info('✅ Created signatures and signed contracts');

        // Create notifications
        $this->createNotifications($bailMobilites, $users);
        $this->command->info('✅ Created realistic notifications');

        // Create incident reports
        $this->createIncidents($bailMobilites, $missions, $users);
        $this->command->info('✅ Created incident reports with corrective actions');

        $this->command->info('🎉 Comprehensive dummy data seeding completed successfully!');
    }

    private function createUsers(): array
    {
        $this->command->info('Creating users for all roles...');

        // Create Admin users
        $admin1 = User::firstOrCreate([
            'email' => 'admin@bailmobilite.com',
        ], [
            'name' => 'Admin Principal',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin1->assignRole('admin');

        $admin2 = User::firstOrCreate([
            'email' => 'admin2@bailmobilite.com',
        ], [
            'name' => 'Admin Secondaire',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin2->assignRole('admin');

        // Create Ops users
        $ops1 = User::firstOrCreate([
            'email' => 'ops@bailmobilite.com',
        ], [
            'name' => 'Ops Manager',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $ops1->assignRole('ops');

        $ops2 = User::firstOrCreate([
            'email' => 'ops2@bailmobilite.com',
        ], [
            'name' => 'Ops Assistant',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $ops2->assignRole('ops');

        $ops3 = User::firstOrCreate([
            'email' => 'ops3@bailmobilite.com',
        ], [
            'name' => 'Ops Coordinator',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $ops3->assignRole('ops');

        // Create Checker users
        $checkers = [];
        $checkerData = [
            ['email' => 'checker@bailmobilite.com', 'name' => 'Checker Principal'],
            ['email' => 'checker2@bailmobilite.com', 'name' => 'Checker Mobile'],
            ['email' => 'checker3@bailmobilite.com', 'name' => 'Checker Expert'],
            ['email' => 'checker4@bailmobilite.com', 'name' => 'Checker Junior'],
            ['email' => 'checker5@bailmobilite.com', 'name' => 'Checker Senior'],
            ['email' => 'checker6@bailmobilite.com', 'name' => 'Checker Freelance'],
        ];

        foreach ($checkerData as $data) {
            $checker = User::firstOrCreate([
                'email' => $data['email'],
            ], [
                'name' => $data['name'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $checker->assignRole('checker');
            $checkers[] = $checker;
        }

        return [
            'admin' => $admin1,
            'admins' => [$admin1, $admin2],
            'ops' => [$ops1, $ops2, $ops3],
            'checkers' => $checkers,
        ];
    }

    private function createContractTemplates(User $admin): array
    {
        $this->command->info('Creating contract templates...');

        $templates = [];

        // Entry contract template
        $entryTemplate = ContractTemplate::create([
            'name' => 'Contrat d\'Entrée Bail Mobilité Standard',
            'type' => 'entry',
            'content' => $this->getEntryContractContent(),
            'admin_signature' => $this->generateSignatureData(),
            'admin_signed_at' => now()->subDays(30),
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $templates[] = $entryTemplate;

        // Exit contract template
        $exitTemplate = ContractTemplate::create([
            'name' => 'Contrat de Sortie Bail Mobilité Standard',
            'type' => 'exit',
            'content' => $this->getExitContractContent(),
            'admin_signature' => $this->generateSignatureData(),
            'admin_signed_at' => now()->subDays(25),
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $templates[] = $exitTemplate;

        // Premium entry template
        $premiumEntryTemplate = ContractTemplate::create([
            'name' => 'Contrat d\'Entrée Bail Mobilité Premium',
            'type' => 'entry',
            'content' => $this->getPremiumEntryContractContent(),
            'admin_signature' => $this->generateSignatureData(),
            'admin_signed_at' => now()->subDays(20),
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $templates[] = $premiumEntryTemplate;

        // Draft template (not signed)
        $draftTemplate = ContractTemplate::create([
            'name' => 'Contrat Bail Mobilité - Brouillon',
            'type' => 'entry',
            'content' => $this->getDraftContractContent(),
            'admin_signature' => null,
            'admin_signed_at' => null,
            'is_active' => false,
            'created_by' => $admin->id,
        ]);
        $templates[] = $draftTemplate;

        return $templates;
    }

    private function createBailMobilites(array $opsUsers): array
    {
        $this->command->info('Creating bail mobilités in all status states...');

        $bailMobilites = [];
        $statuses = ['assigned', 'in_progress', 'completed', 'incident'];

        // Create 25 bail mobilités with various statuses and realistic dates
        for ($i = 0; $i < 25; $i++) {
            $status = $statuses[$i % count($statuses)];
            $opsUser = $opsUsers[$i % count($opsUsers)];
            
            // Generate realistic dates based on status
            [$startDate, $endDate] = $this->generateRealisticDates($status, $i);

            $bailMobilite = BailMobilite::create([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'address' => $this->addresses[$i % count($this->addresses)],
                'tenant_name' => $this->tenantNames[$i % count($this->tenantNames)],
                'tenant_phone' => $this->phoneNumbers[$i % count($this->phoneNumbers)],
                'tenant_email' => strtolower(str_replace(' ', '.', $this->tenantNames[$i % count($this->tenantNames)])) . '@example.com',
                'notes' => $this->generateRealisticNotes($status),
                'status' => $status,
                'ops_user_id' => $opsUser->id,
            ]);

            $bailMobilites[] = $bailMobilite;
        }

        return $bailMobilites;
    }

    private function createMissions(array $bailMobilites, array $checkers, array $opsUsers): array
    {
        $this->command->info('Creating missions with realistic scheduling...');

        $missions = [];

        foreach ($bailMobilites as $bailMobilite) {
            $checker = $checkers[array_rand($checkers)];
            $opsUser = $opsUsers[array_rand($opsUsers)];

            // Create entry mission
            $entryMission = Mission::create([
                'type' => 'checkin',
                'mission_type' => 'entry',
                'scheduled_at' => $bailMobilite->start_date->copy()->subDays(1),
                'scheduled_time' => $this->generateRandomTime(),
                'address' => $bailMobilite->address,
                'tenant_name' => $bailMobilite->tenant_name,
                'tenant_phone' => $bailMobilite->tenant_phone,
                'tenant_email' => $bailMobilite->tenant_email,
                'notes' => 'Mission d\'entrée - État des lieux et remise des clés',
                'agent_id' => $checker->id,
                'status' => $this->getMissionStatusBasedOnBailMobilite($bailMobilite, 'entry'),
                'bail_mobilite_id' => $bailMobilite->id,
                'ops_assigned_by' => $opsUser->id,
            ]);

            // Update bail mobilité with entry mission
            $bailMobilite->update(['entry_mission_id' => $entryMission->id]);
            $missions[] = $entryMission;

            // Create exit mission
            $exitMission = Mission::create([
                'type' => 'checkout',
                'mission_type' => 'exit',
                'scheduled_at' => $bailMobilite->end_date->copy()->addDays(1),
                'scheduled_time' => $this->generateRandomTime(),
                'address' => $bailMobilite->address,
                'tenant_name' => $bailMobilite->tenant_name,
                'tenant_phone' => $bailMobilite->tenant_phone,
                'tenant_email' => $bailMobilite->tenant_email,
                'notes' => 'Mission de sortie - État des lieux et récupération des clés',
                'agent_id' => $checker->id,
                'status' => $this->getMissionStatusBasedOnBailMobilite($bailMobilite, 'exit'),
                'bail_mobilite_id' => $bailMobilite->id,
                'ops_assigned_by' => $opsUser->id,
            ]);

            // Update bail mobilité with exit mission
            $bailMobilite->update(['exit_mission_id' => $exitMission->id]);
            $missions[] = $exitMission;
        }

        return $missions;
    }

    private function createChecklists(array $missions, array $checkers): void
    {
        $this->command->info('Creating completed checklists with photos...');

        foreach ($missions as $mission) {
            // Only create checklists for completed missions
            if ($mission->status !== 'completed') {
                continue;
            }

            $checklist = Checklist::create([
                'mission_id' => $mission->id,
                'general_info' => $this->generateRealisticGeneralInfo(),
                'rooms' => $this->generateRealisticRoomsData(),
                'utilities' => $this->generateRealisticUtilitiesData(),
                'tenant_signature' => $this->generateSignatureData(),
                'agent_signature' => $this->generateSignatureData(),
                'status' => 'completed',
                'ops_validation_comments' => $this->generateValidationComments(),
                'validated_by' => null, // Will be set randomly
                'validated_at' => now()->subDays(rand(1, 10)),
            ]);

            // Create checklist items with photos
            $this->createChecklistItems($checklist);
        }
    }

    private function createChecklistItems(Checklist $checklist): void
    {
        $items = [
            ['item_name' => 'État des murs salon', 'category' => 'living_room', 'condition' => 'good'],
            ['item_name' => 'État du sol cuisine', 'category' => 'kitchen', 'condition' => 'good'],
            ['item_name' => 'Fonctionnement électricité', 'category' => 'utilities', 'condition' => 'good'],
            ['item_name' => 'État des fenêtres', 'category' => 'general', 'condition' => 'good'],
            ['item_name' => 'Plomberie salle de bain', 'category' => 'bathroom', 'condition' => 'good'],
            ['item_name' => 'État des portes', 'category' => 'general', 'condition' => 'good'],
        ];

        foreach ($items as $itemData) {
            $item = ChecklistItem::create([
                'checklist_id' => $checklist->id,
                'item_name' => $itemData['item_name'],
                'category' => $itemData['category'],
                'condition' => $itemData['condition'],
                'comment' => $this->generateItemNotes($itemData['condition']),
            ]);

            // Create 1-3 photos per item
            $photoCount = rand(1, 3);
            for ($i = 0; $i < $photoCount; $i++) {
                ChecklistPhoto::create([
                    'checklist_item_id' => $item->id,
                    'photo_path' => $this->generateDummyPhotoPath($itemData['category'], $i),
                ]);
            }
        }
    }

    private function createSignatures(array $bailMobilites, array $contractTemplates): void
    {
        $this->command->info('Creating signatures and signed contracts...');

        foreach ($bailMobilites as $bailMobilite) {
            // Only create signatures for in_progress and completed bail mobilités
            if (!in_array($bailMobilite->status, ['in_progress', 'completed'])) {
                continue;
            }

            $entryTemplate = $contractTemplates[0]; // Use first template for entry
            $exitTemplate = $contractTemplates[1]; // Use second template for exit

            // Create entry signature
            BailMobiliteSignature::create([
                'bail_mobilite_id' => $bailMobilite->id,
                'signature_type' => 'entry',
                'contract_template_id' => $entryTemplate->id,
                'tenant_signature' => $this->generateSignatureData(),
                'tenant_signed_at' => $bailMobilite->start_date->copy()->subDays(1)->addHours(rand(9, 17)),
                'contract_pdf_path' => $this->generateContractPdfPath($bailMobilite, 'entry'),
            ]);

            // Create exit signature only for completed bail mobilités
            if ($bailMobilite->status === 'completed') {
                BailMobiliteSignature::create([
                    'bail_mobilite_id' => $bailMobilite->id,
                    'signature_type' => 'exit',
                    'contract_template_id' => $exitTemplate->id,
                    'tenant_signature' => $this->generateSignatureData(),
                    'tenant_signed_at' => $bailMobilite->end_date->copy()->addDays(1)->addHours(rand(9, 17)),
                    'contract_pdf_path' => $this->generateContractPdfPath($bailMobilite, 'exit'),
                ]);
            }
        }
    }

    private function createNotifications(array $bailMobilites, array $users): void
    {
        $this->command->info('Creating realistic notifications...');

        foreach ($bailMobilites as $bailMobilite) {
            $opsUser = $users['ops'][array_rand($users['ops'])];

            // Exit reminder notifications for in_progress bail mobilités
            if ($bailMobilite->status === 'in_progress' && $bailMobilite->getRemainingDays() <= 10) {
                Notification::create([
                    'type' => Notification::TYPE_EXIT_REMINDER,
                    'recipient_id' => $opsUser->id,
                    'bail_mobilite_id' => $bailMobilite->id,
                    'scheduled_at' => now()->subDays(rand(1, 5)),
                    'sent_at' => now()->subDays(rand(1, 5)),
                    'status' => 'sent',
                    'data' => [
                        'tenant_name' => $bailMobilite->tenant_name,
                        'end_date' => $bailMobilite->end_date->format('Y-m-d'),
                        'remaining_days' => $bailMobilite->getRemainingDays(),
                    ],
                ]);
            }

            // Mission assigned notifications
            Notification::create([
                'type' => Notification::TYPE_MISSION_ASSIGNED,
                'recipient_id' => $users['checkers'][array_rand($users['checkers'])]->id,
                'bail_mobilite_id' => $bailMobilite->id,
                'scheduled_at' => $bailMobilite->start_date->copy()->subDays(2),
                'sent_at' => $bailMobilite->start_date->copy()->subDays(2),
                'status' => 'sent',
                'data' => [
                    'mission_type' => 'entry',
                    'tenant_name' => $bailMobilite->tenant_name,
                    'address' => $bailMobilite->address,
                ],
            ]);

            // Checklist validation notifications for completed missions
            if ($bailMobilite->status === 'completed') {
                Notification::create([
                    'type' => Notification::TYPE_CHECKLIST_VALIDATION,
                    'recipient_id' => $opsUser->id,
                    'bail_mobilite_id' => $bailMobilite->id,
                    'scheduled_at' => now()->subDays(rand(1, 7)),
                    'sent_at' => now()->subDays(rand(1, 7)),
                    'status' => 'sent',
                    'data' => [
                        'tenant_name' => $bailMobilite->tenant_name,
                        'mission_type' => 'entry',
                    ],
                ]);
            }
        }

        // Create some pending notifications
        for ($i = 0; $i < 5; $i++) {
            $bailMobilite = $bailMobilites[array_rand($bailMobilites)];
            $opsUser = $users['ops'][array_rand($users['ops'])];

            Notification::create([
                'type' => Notification::TYPE_CALENDAR_UPDATE,
                'recipient_id' => $opsUser->id,
                'bail_mobilite_id' => $bailMobilite->id,
                'scheduled_at' => now()->addHours(rand(1, 24)),
                'sent_at' => null,
                'status' => 'pending',
                'data' => [
                    'update_type' => 'status_change',
                    'tenant_name' => $bailMobilite->tenant_name,
                    'new_status' => $bailMobilite->status,
                ],
            ]);
        }
    }

    private function createIncidents(array $bailMobilites, array $missions, array $users): void
    {
        $this->command->info('Creating incident reports with corrective actions...');

        $incidentTypes = [
            IncidentReport::TYPE_MISSING_CHECKLIST,
            IncidentReport::TYPE_INCOMPLETE_CHECKLIST,
            IncidentReport::TYPE_MISSING_TENANT_SIGNATURE,
            IncidentReport::TYPE_MISSING_REQUIRED_PHOTOS,
            IncidentReport::TYPE_OVERDUE_MISSION,
        ];

        // Create incidents for bail mobilités with incident status
        foreach ($bailMobilites as $bailMobilite) {
            if ($bailMobilite->status !== 'incident') {
                continue;
            }

            $incidentType = $incidentTypes[array_rand($incidentTypes)];
            $severity = $this->getIncidentSeverity($incidentType);
            $mission = collect($missions)->where('bail_mobilite_id', $bailMobilite->id)->first();

            $incident = IncidentReport::create([
                'bail_mobilite_id' => $bailMobilite->id,
                'mission_id' => $mission?->id,
                'type' => $incidentType,
                'severity' => $severity,
                'title' => $this->getIncidentTitle($incidentType),
                'description' => $this->getIncidentDescription($incidentType, $bailMobilite),
                'metadata' => $this->getIncidentMetadata($incidentType),
                'status' => rand(0, 1) ? IncidentReport::STATUS_OPEN : IncidentReport::STATUS_IN_PROGRESS,
                'detected_at' => now()->subDays(rand(1, 10)),
                'created_by' => $users['ops'][array_rand($users['ops'])]->id,
            ]);

            // Create corrective actions
            $this->createCorrectiveActions($incident, $users);
        }

        // Create some resolved incidents
        for ($i = 0; $i < 3; $i++) {
            $bailMobilite = $bailMobilites[array_rand($bailMobilites)];
            $incidentType = $incidentTypes[array_rand($incidentTypes)];
            $mission = collect($missions)->where('bail_mobilite_id', $bailMobilite->id)->first();

            $incident = IncidentReport::create([
                'bail_mobilite_id' => $bailMobilite->id,
                'mission_id' => $mission?->id,
                'type' => $incidentType,
                'severity' => IncidentReport::SEVERITY_LOW,
                'title' => $this->getIncidentTitle($incidentType),
                'description' => $this->getIncidentDescription($incidentType, $bailMobilite),
                'metadata' => $this->getIncidentMetadata($incidentType),
                'status' => IncidentReport::STATUS_RESOLVED,
                'detected_at' => now()->subDays(rand(15, 30)),
                'resolved_at' => now()->subDays(rand(1, 14)),
                'created_by' => $users['ops'][array_rand($users['ops'])]->id,
                'resolved_by' => $users['ops'][array_rand($users['ops'])]->id,
                'resolution_notes' => 'Incident résolu avec succès. Toutes les actions correctives ont été mises en place.',
            ]);

            $this->createCorrectiveActions($incident, $users);
        }
    }

    private function createCorrectiveActions(IncidentReport $incident, array $users): void
    {
        $actions = [
            [
                'title' => 'Contacter le checker',
                'description' => 'Contacter le checker pour compléter la checklist manquante',
            ],
            [
                'title' => 'Programmer une visite',
                'description' => 'Programmer une nouvelle visite pour finaliser l\'état des lieux',
            ],
            [
                'title' => 'Demander les photos',
                'description' => 'Demander les photos manquantes pour compléter le dossier',
            ],
            [
                'title' => 'Valider la signature',
                'description' => 'Obtenir et valider la signature du locataire sur les documents',
            ],
            [
                'title' => 'Mettre à jour le statut',
                'description' => 'Mettre à jour le statut de la mission dans le système',
            ],
        ];

        $actionCount = rand(1, 3);
        for ($i = 0; $i < $actionCount; $i++) {
            $action = $actions[array_rand($actions)];
            $status = rand(0, 1) ? CorrectiveAction::STATUS_PENDING : CorrectiveAction::STATUS_COMPLETED;
            
            CorrectiveAction::create([
                'incident_report_id' => $incident->id,
                'title' => $action['title'],
                'description' => $action['description'],
                'assigned_to' => $users['ops'][array_rand($users['ops'])]->id,
                'priority' => [
                    CorrectiveAction::PRIORITY_LOW,
                    CorrectiveAction::PRIORITY_MEDIUM,
                    CorrectiveAction::PRIORITY_HIGH
                ][array_rand([
                    CorrectiveAction::PRIORITY_LOW,
                    CorrectiveAction::PRIORITY_MEDIUM,
                    CorrectiveAction::PRIORITY_HIGH
                ])],
                'status' => $status,
                'due_date' => now()->addDays(rand(1, 7)),
                'completed_at' => $status === CorrectiveAction::STATUS_COMPLETED ? now()->subDays(rand(1, 5)) : null,
                'completion_notes' => $status === CorrectiveAction::STATUS_COMPLETED ? 'Action corrective terminée avec succès.' : null,
                'created_by' => $users['ops'][array_rand($users['ops'])]->id,
            ]);
        }
    }

    // Helper methods for generating realistic data

    private function generateRealisticDates(string $status, int $index): array
    {
        $baseDate = now()->subDays($index * 3);
        
        switch ($status) {
            case 'assigned':
                $startDate = $baseDate->copy()->addDays(rand(1, 30));
                $endDate = $startDate->copy()->addDays(rand(30, 90));
                break;
            case 'in_progress':
                $startDate = $baseDate->copy()->subDays(rand(1, 30));
                $endDate = $startDate->copy()->addDays(rand(30, 90));
                break;
            case 'completed':
                $startDate = $baseDate->copy()->subDays(rand(60, 120));
                $endDate = $startDate->copy()->addDays(rand(30, 90));
                break;
            case 'incident':
                $startDate = $baseDate->copy()->subDays(rand(10, 60));
                $endDate = $startDate->copy()->addDays(rand(30, 90));
                break;
            default:
                $startDate = $baseDate->copy()->addDays(rand(1, 30));
                $endDate = $startDate->copy()->addDays(rand(30, 90));
        }

        return [$startDate, $endDate];
    }

    private function generateRealisticNotes(string $status): string
    {
        $notes = [
            'assigned' => [
                'Nouveau bail mobilité assigné, en attente de confirmation du locataire.',
                'Dossier complet reçu, mission d\'entrée à programmer.',
                'Locataire confirmé, préparation des documents en cours.',
            ],
            'in_progress' => [
                'Bail mobilité en cours, locataire installé depuis quelques semaines.',
                'Tout se passe bien, aucun incident signalé.',
                'Locataire satisfait, appartement en bon état.',
            ],
            'completed' => [
                'Bail mobilité terminé avec succès, état des lieux de sortie effectué.',
                'Locataire parti, appartement rendu en bon état.',
                'Mission complétée, tous les documents signés.',
            ],
            'incident' => [
                'Incident détecté, nécessite une intervention.',
                'Problème signalé par le locataire, investigation en cours.',
                'Situation à résoudre rapidement.',
            ],
        ];

        return $notes[$status][array_rand($notes[$status])];
    }

    private function generateRandomTime(): string
    {
        $hours = [9, 10, 11, 14, 15, 16, 17];
        $minutes = [0, 15, 30, 45];
        
        return sprintf('%02d:%02d', $hours[array_rand($hours)], $minutes[array_rand($minutes)]);
    }

    private function getMissionStatusBasedOnBailMobilite(BailMobilite $bailMobilite, string $missionType): string
    {
        switch ($bailMobilite->status) {
            case 'assigned':
                return 'assigned';
            case 'in_progress':
                return $missionType === 'entry' ? 'completed' : 'assigned';
            case 'completed':
                return 'completed';
            case 'incident':
                return 'in_progress';
            default:
                return 'assigned';
        }
    }

    private function generateSignatureData(): string
    {
        // Generate a dummy signature hash
        return hash('sha256', 'signature_' . uniqid() . '_' . time());
    }

    private function generateRealisticGeneralInfo(): array
    {
        return [
            'heating' => [
                'type' => ['gas', 'electric', 'central'][array_rand(['gas', 'electric', 'central'])],
                'condition' => ['good', 'fair', 'poor'][array_rand(['good', 'fair', 'poor'])],
                'comment' => 'Système de chauffage fonctionnel',
            ],
            'hot_water' => [
                'type' => ['boiler', 'central', 'electric'][array_rand(['boiler', 'central', 'electric'])],
                'condition' => ['good', 'fair'][array_rand(['good', 'fair'])],
                'comment' => 'Eau chaude disponible',
            ],
            'keys' => [
                'count' => rand(2, 5),
                'condition' => 'good',
                'comment' => 'Toutes les clés remises',
            ],
        ];
    }

    private function generateRealisticRoomsData(): array
    {
        $conditions = ['good', 'fair', 'poor'];
        
        return [
            'entrance' => [
                'walls' => $conditions[array_rand($conditions)],
                'floor' => $conditions[array_rand($conditions)],
                'ceiling' => $conditions[array_rand($conditions)],
                'door' => $conditions[array_rand($conditions)],
                'windows' => $conditions[array_rand($conditions)],
                'electrical' => $conditions[array_rand($conditions)],
            ],
            'living_room' => [
                'walls' => $conditions[array_rand($conditions)],
                'floor' => $conditions[array_rand($conditions)],
                'ceiling' => $conditions[array_rand($conditions)],
                'windows' => $conditions[array_rand($conditions)],
                'electrical' => $conditions[array_rand($conditions)],
                'heating' => $conditions[array_rand($conditions)],
            ],
            'kitchen' => [
                'walls' => $conditions[array_rand($conditions)],
                'floor' => $conditions[array_rand($conditions)],
                'ceiling' => $conditions[array_rand($conditions)],
                'windows' => $conditions[array_rand($conditions)],
                'electrical' => $conditions[array_rand($conditions)],
                'plumbing' => $conditions[array_rand($conditions)],
                'appliances' => $conditions[array_rand($conditions)],
            ],
        ];
    }

    private function generateRealisticUtilitiesData(): array
    {
        return [
            'electricity_meter' => [
                'number' => 'EM' . rand(1000, 9999),
                'reading' => rand(1000, 9999) + (rand(0, 99) / 100),
            ],
            'gas_meter' => [
                'number' => 'GM' . rand(1000, 9999),
                'reading' => rand(100, 999) + (rand(0, 99) / 100),
            ],
            'water_meter' => [
                'number' => 'WM' . rand(1000, 9999),
                'reading' => rand(100, 999) + (rand(0, 99) / 100),
            ],
        ];
    }

    private function generateValidationComments(): string
    {
        $comments = [
            'Checklist complète et conforme, validation approuvée.',
            'Quelques points mineurs à noter, mais globalement satisfaisant.',
            'Excellent travail, toutes les photos sont de bonne qualité.',
            'Checklist validée, mission terminée avec succès.',
            'Bon état général, aucun problème majeur détecté.',
        ];

        return $comments[array_rand($comments)];
    }

    private function generateItemNotes(string $condition): string
    {
        $notes = [
            'good' => [
                'En excellent état, aucun problème détecté.',
                'Parfait état, conforme aux attentes.',
                'Très bon état général.',
            ],
            'fair' => [
                'État correct avec quelques signes d\'usure normale.',
                'Quelques marques mineures mais fonctionnel.',
                'État acceptable, usure normale.',
            ],
            'poor' => [
                'État dégradé, nécessite une attention particulière.',
                'Problèmes visibles, réparations recommandées.',
                'État préoccupant, intervention nécessaire.',
            ],
        ];

        return $notes[$condition][array_rand($notes[$condition])];
    }

    private function generateDummyPhotoPath(string $category, int $index): string
    {
        return "checklist_photos/{$category}_" . uniqid() . "_{$index}.jpg";
    }

    private function generateContractPdfPath(BailMobilite $bailMobilite, string $type): string
    {
        return "contracts/bail_mobilite_{$bailMobilite->id}_{$type}_" . uniqid() . ".pdf";
    }

    private function getIncidentSeverity(string $type): string
    {
        $severityMap = [
            IncidentReport::TYPE_MISSING_CHECKLIST => IncidentReport::SEVERITY_HIGH,
            IncidentReport::TYPE_INCOMPLETE_CHECKLIST => IncidentReport::SEVERITY_MEDIUM,
            IncidentReport::TYPE_MISSING_TENANT_SIGNATURE => IncidentReport::SEVERITY_HIGH,
            IncidentReport::TYPE_MISSING_REQUIRED_PHOTOS => IncidentReport::SEVERITY_MEDIUM,
            IncidentReport::TYPE_OVERDUE_MISSION => IncidentReport::SEVERITY_CRITICAL,
        ];

        return $severityMap[$type] ?? IncidentReport::SEVERITY_MEDIUM;
    }

    private function getIncidentTitle(string $type): string
    {
        $titles = [
            IncidentReport::TYPE_MISSING_CHECKLIST => 'Checklist manquante',
            IncidentReport::TYPE_INCOMPLETE_CHECKLIST => 'Checklist incomplète',
            IncidentReport::TYPE_MISSING_TENANT_SIGNATURE => 'Signature locataire manquante',
            IncidentReport::TYPE_MISSING_REQUIRED_PHOTOS => 'Photos obligatoires manquantes',
            IncidentReport::TYPE_OVERDUE_MISSION => 'Mission en retard',
        ];

        return $titles[$type] ?? 'Incident détecté';
    }

    private function getIncidentDescription(string $type, BailMobilite $bailMobilite): string
    {
        $descriptions = [
            IncidentReport::TYPE_MISSING_CHECKLIST => "La checklist pour le bail mobilité de {$bailMobilite->tenant_name} n'a pas été soumise dans les délais requis.",
            IncidentReport::TYPE_INCOMPLETE_CHECKLIST => "La checklist soumise pour {$bailMobilite->tenant_name} est incomplète et nécessite des informations supplémentaires.",
            IncidentReport::TYPE_MISSING_TENANT_SIGNATURE => "La signature du locataire {$bailMobilite->tenant_name} est manquante sur les documents contractuels.",
            IncidentReport::TYPE_MISSING_REQUIRED_PHOTOS => "Les photos obligatoires pour l'état des lieux de {$bailMobilite->tenant_name} n'ont pas été fournies.",
            IncidentReport::TYPE_OVERDUE_MISSION => "La mission pour {$bailMobilite->tenant_name} est en retard et nécessite une intervention immédiate.",
        ];

        return $descriptions[$type] ?? "Incident détecté pour {$bailMobilite->tenant_name}";
    }

    private function getIncidentMetadata(string $type): array
    {
        return [
            'detection_method' => 'automated_check',
            'priority' => $this->getIncidentSeverity($type),
            'category' => 'operational',
            'auto_generated' => true,
        ];
    }

    // Contract content templates

    private function getEntryContractContent(): string
    {
        return "CONTRAT DE BAIL MOBILITÉ - ENTRÉE\n\n" .
               "Le présent contrat établit les conditions d'entrée dans le logement meublé.\n\n" .
               "ARTICLE 1 - OBJET\n" .
               "Le bailleur met à disposition du locataire un logement meublé situé à l'adresse suivante : {{address}}\n\n" .
               "ARTICLE 2 - DURÉE\n" .
               "Le présent bail est conclu pour une durée de {{duration}} mois, du {{start_date}} au {{end_date}}.\n\n" .
               "ARTICLE 3 - LOYER ET CHARGES\n" .
               "Le loyer mensuel est fixé à {{rent}} euros, charges comprises.\n\n" .
               "ARTICLE 4 - ÉTAT DES LIEUX\n" .
               "Un état des lieux contradictoire sera établi lors de la remise des clés.\n\n" .
               "SIGNATURES\n" .
               "Fait à {{city}}, le {{date}}\n\n" .
               "Le Bailleur                    Le Locataire\n" .
               "{{admin_signature}}           {{tenant_signature}}";
    }

    private function getExitContractContent(): string
    {
        return "CONTRAT DE BAIL MOBILITÉ - SORTIE\n\n" .
               "Le présent document acte la fin du bail mobilité et la restitution du logement.\n\n" .
               "ARTICLE 1 - FIN DE BAIL\n" .
               "Le bail mobilité prend fin le {{end_date}} conformément aux termes du contrat initial.\n\n" .
               "ARTICLE 2 - ÉTAT DES LIEUX DE SORTIE\n" .
               "Un état des lieux de sortie a été réalisé le {{exit_date}}.\n\n" .
               "ARTICLE 3 - RESTITUTION\n" .
               "Le locataire restitue les clés et le logement dans l'état conforme à l'inventaire d'entrée.\n\n" .
               "ARTICLE 4 - DÉPÔT DE GARANTIE\n" .
               "Le dépôt de garantie sera restitué sous déduction des éventuels frais.\n\n" .
               "SIGNATURES\n" .
               "Fait à {{city}}, le {{date}}\n\n" .
               "Le Bailleur                    Le Locataire\n" .
               "{{admin_signature}}           {{tenant_signature}}";
    }

    private function getPremiumEntryContractContent(): string
    {
        return "CONTRAT DE BAIL MOBILITÉ PREMIUM - ENTRÉE\n\n" .
               "Le présent contrat premium établit les conditions d'entrée dans le logement meublé haut de gamme.\n\n" .
               "ARTICLE 1 - OBJET\n" .
               "Le bailleur met à disposition du locataire un logement meublé premium situé à : {{address}}\n\n" .
               "ARTICLE 2 - SERVICES INCLUS\n" .
               "- Ménage hebdomadaire\n" .
               "- Conciergerie 24h/24\n" .
               "- Internet haut débit\n" .
               "- Accès salle de sport\n\n" .
               "ARTICLE 3 - DURÉE ET CONDITIONS\n" .
               "Durée : {{duration}} mois, du {{start_date}} au {{end_date}}\n" .
               "Loyer : {{rent}} euros/mois, services premium inclus\n\n" .
               "SIGNATURES\n" .
               "Fait à {{city}}, le {{date}}\n\n" .
               "Le Bailleur                    Le Locataire\n" .
               "{{admin_signature}}           {{tenant_signature}}";
    }

    private function getDraftContractContent(): string
    {
        return "CONTRAT DE BAIL MOBILITÉ - BROUILLON\n\n" .
               "[EN COURS DE RÉDACTION]\n\n" .
               "Ce contrat est en cours de finalisation.\n" .
               "Les termes et conditions seront précisés ultérieurement.\n\n" .
               "ARTICLE 1 - [À COMPLÉTER]\n" .
               "ARTICLE 2 - [À COMPLÉTER]\n" .
               "ARTICLE 3 - [À COMPLÉTER]\n\n" .
               "Document non finalisé - Ne pas utiliser pour signature.";
    }
}