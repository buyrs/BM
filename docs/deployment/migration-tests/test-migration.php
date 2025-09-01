<?php

/**
 * Script de test de migration
 * 
 * Ce script teste la migration des missions vers les Bail Mobilité
 * sur un environnement de test avec des données de démonstration.
 * 
 * Usage: php artisan test:migration [--reset] [--verbose]
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Mission;
use App\Models\BailMobilite;
use App\Models\User;
use App\Models\Agent;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use Carbon\Carbon;

class TestMigration extends Command
{
    protected $signature = 'test:migration 
                           {--reset : Réinitialiser les données de test avant le test}
                           {--verbose : Affichage détaillé des opérations}';

    protected $description = 'Teste la migration des missions vers les Bail Mobilité';

    private $testData = [];
    private $results = [
        'tests_passed' => 0,
        'tests_failed' => 0,
        'errors' => []
    ];

    public function handle()
    {
        $this->info('=== Test de Migration - Missions vers Bail Mobilité ===');
        $this->newLine();

        try {
            if ($this->option('reset')) {
                $this->resetTestData();
            }

            $this->createTestData();
            $this->runMigrationTests();
            $this->validateResults();
            $this->displayResults();

            return $this->results['tests_failed'] === 0 ? 0 : 1;

        } catch (\Exception $e) {
            $this->error('Erreur lors du test: ' . $e->getMessage());
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
    }

    private function resetTestData()
    {
        $this->info('Réinitialisation des données de test...');

        DB::transaction(function () {
            // Supprimer les données de test existantes
            BailMobilite::where('notes', 'LIKE', '%TEST_MIGRATION%')->delete();
            Mission::where('notes', 'LIKE', '%TEST_MIGRATION%')->delete();
            User::where('email', 'LIKE', '%test-migration%')->delete();
            Agent::where('name', 'LIKE', '%Test Migration%')->delete();
        });

        $this->info('Données de test réinitialisées');
    }

    private function createTestData()
    {
        $this->info('Création des données de test...');

        DB::transaction(function () {
            // Créer un utilisateur Ops de test
            $this->testData['ops_user'] = User::create([
                'name' => 'Ops Test Migration',
                'email' => 'ops-test-migration@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            $this->testData['ops_user']->assignRole('ops');

            // Créer des agents de test
            $this->testData['agents'] = collect([
                Agent::create(['name' => 'Agent Test Migration 1', 'email' => 'agent1-test@example.com']),
                Agent::create(['name' => 'Agent Test Migration 2', 'email' => 'agent2-test@example.com']),
            ]);

            // Créer des missions de test représentant différents scénarios
            $this->createTestScenarios();
        });

        $this->info('Données de test créées');
    }

    private function createTestScenarios()
    {
        $scenarios = [
            'simple_stay' => $this->createSimpleStayScenario(),
            'complex_stay' => $this->createComplexStayScenario(),
            'single_mission' => $this->createSingleMissionScenario(),
            'overlapping_stays' => $this->createOverlappingStaysScenario(),
        ];

        $this->testData['scenarios'] = $scenarios;
    }

    private function createSimpleStayScenario()
    {
        $startDate = now()->addDays(10);
        $endDate = now()->addDays(40);

        return [
            'description' => 'Séjour simple avec entrée et sortie',
            'missions' => [
                Mission::create([
                    'address' => '123 Rue de Test, Paris',
                    'scheduled_date' => $startDate->format('Y-m-d'),
                    'scheduled_time' => '10:00',
                    'status' => 'completed',
                    'assigned_agent_id' => $this->testData['agents'][0]->id,
                    'notes' => 'Mission d\'entrée - TEST_MIGRATION - Nom: Jean Dupont, Tel: 0123456789',
                ]),
                Mission::create([
                    'address' => '123 Rue de Test, Paris',
                    'scheduled_date' => $endDate->format('Y-m-d'),
                    'scheduled_time' => '14:00',
                    'status' => 'assigned',
                    'assigned_agent_id' => $this->testData['agents'][1]->id,
                    'notes' => 'Mission de sortie - TEST_MIGRATION',
                ]),
            ]
        ];
    }

    private function createComplexStayScenario()
    {
        $startDate = now()->addDays(5);
        $endDate = now()->addDays(35);

        $entryMission = Mission::create([
            'address' => '456 Avenue Complex, Lyon',
            'scheduled_date' => $startDate->format('Y-m-d'),
            'scheduled_time' => '11:00',
            'status' => 'completed',
            'assigned_agent_id' => $this->testData['agents'][0]->id,
            'notes' => 'Mission d\'entrée complexe - TEST_MIGRATION - Nom: Marie Martin, Email: marie@example.com',
        ]);

        // Créer une checklist pour cette mission
        $checklist = Checklist::create([
            'mission_id' => $entryMission->id,
            'status' => 'completed',
            'tenant_comment' => 'Locataire satisfait',
            'ops_validated' => true,
            'ops_validated_at' => now(),
        ]);

        // Ajouter des items de checklist
        ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'category' => 'general',
            'item' => 'État général',
            'status' => 'good',
            'comment' => 'Logement en bon état',
        ]);

        $exitMission = Mission::create([
            'address' => '456 Avenue Complex, Lyon',
            'scheduled_date' => $endDate->format('Y-m-d'),
            'scheduled_time' => '15:00',
            'status' => 'assigned',
            'notes' => 'Mission de sortie complexe - TEST_MIGRATION',
        ]);

        return [
            'description' => 'Séjour complexe avec checklist complète',
            'missions' => [$entryMission, $exitMission],
            'checklist' => $checklist,
        ];
    }

    private function createSingleMissionScenario()
    {
        return [
            'description' => 'Mission unique (doit être ignorée)',
            'missions' => [
                Mission::create([
                    'address' => '789 Rue Unique, Marseille',
                    'scheduled_date' => now()->addDays(15)->format('Y-m-d'),
                    'scheduled_time' => '12:00',
                    'status' => 'completed',
                    'notes' => 'Mission unique - TEST_MIGRATION',
                ]),
            ]
        ];
    }

    private function createOverlappingStaysScenario()
    {
        $baseDate = now()->addDays(20);

        return [
            'description' => 'Séjours qui se chevauchent (même adresse)',
            'missions' => [
                // Premier séjour
                Mission::create([
                    'address' => '321 Place Overlap, Nice',
                    'scheduled_date' => $baseDate->format('Y-m-d'),
                    'scheduled_time' => '10:00',
                    'status' => 'completed',
                    'notes' => 'Entrée séjour 1 - TEST_MIGRATION - Nom: Pierre Durand',
                ]),
                Mission::create([
                    'address' => '321 Place Overlap, Nice',
                    'scheduled_date' => $baseDate->addDays(20)->format('Y-m-d'),
                    'scheduled_time' => '14:00',
                    'status' => 'completed',
                    'notes' => 'Sortie séjour 1 - TEST_MIGRATION',
                ]),
                // Deuxième séjour (même mois)
                Mission::create([
                    'address' => '321 Place Overlap, Nice',
                    'scheduled_date' => $baseDate->addDays(25)->format('Y-m-d'),
                    'scheduled_time' => '11:00',
                    'status' => 'assigned',
                    'notes' => 'Entrée séjour 2 - TEST_MIGRATION - Nom: Sophie Blanc',
                ]),
                Mission::create([
                    'address' => '321 Place Overlap, Nice',
                    'scheduled_date' => $baseDate->addDays(45)->format('Y-m-d'),
                    'scheduled_time' => '15:00',
                    'status' => 'assigned',
                    'notes' => 'Sortie séjour 2 - TEST_MIGRATION',
                ]),
            ]
        ];
    }

    private function runMigrationTests()
    {
        $this->info('Exécution des tests de migration...');

        // Test 1: Exécuter la migration en mode dry-run
        $this->test('Migration en mode simulation', function () {
            $exitCode = $this->call('migrate:missions-to-bm', [
                '--dry-run' => true,
                '--batch-size' => 10
            ]);
            return $exitCode === 0;
        });

        // Test 2: Exécuter la migration réelle
        $this->test('Migration réelle', function () {
            $exitCode = $this->call('migrate:missions-to-bm', [
                '--batch-size' => 10
            ]);
            return $exitCode === 0;
        });

        // Test 3: Vérifier la création des BM
        $this->test('Création des Bail Mobilité', function () {
            $bmCount = BailMobilite::where('notes', 'LIKE', '%TEST_MIGRATION%')->count();
            // On s'attend à 3 BM (simple, complex, overlapping x2)
            return $bmCount >= 3;
        });

        // Test 4: Vérifier les relations
        $this->test('Relations entre BM et missions', function () {
            $bms = BailMobilite::where('notes', 'LIKE', '%TEST_MIGRATION%')->get();
            
            foreach ($bms as $bm) {
                if (!$bm->entryMission || !$bm->exitMission) {
                    return false;
                }
                
                if ($bm->entryMission->bail_mobilite_id !== $bm->id) {
                    return false;
                }
                
                if ($bm->exitMission->bail_mobilite_id !== $bm->id) {
                    return false;
                }
            }
            
            return true;
        });

        // Test 5: Vérifier la copie des checklists
        $this->test('Copie des checklists', function () {
            $complexBm = BailMobilite::where('address', '456 Avenue Complex, Lyon')->first();
            
            if (!$complexBm || !$complexBm->entryMission) {
                return false;
            }
            
            return $complexBm->entryMission->checklist !== null;
        });

        // Test 6: Vérifier l'extraction des informations locataire
        $this->test('Extraction des informations locataire', function () {
            $simpleBm = BailMobilite::where('address', '123 Rue de Test, Paris')->first();
            
            if (!$simpleBm) {
                return false;
            }
            
            return $simpleBm->tenant_name === 'Jean Dupont';
        });

        // Test 7: Vérifier les statuts
        $this->test('Attribution des statuts corrects', function () {
            $bms = BailMobilite::where('notes', 'LIKE', '%TEST_MIGRATION%')->get();
            
            foreach ($bms as $bm) {
                // Vérifier que le statut est cohérent avec les missions
                $entryCompleted = $bm->entryMission && $bm->entryMission->status === 'completed';
                $exitCompleted = $bm->exitMission && $bm->exitMission->status === 'completed';
                
                if ($entryCompleted && $exitCompleted && $bm->status !== 'completed') {
                    return false;
                }
                
                if ($entryCompleted && !$exitCompleted && $bm->status !== 'in_progress') {
                    return false;
                }
                
                if (!$entryCompleted && $bm->status !== 'assigned') {
                    return false;
                }
            }
            
            return true;
        });

        // Test 8: Vérifier que les missions uniques sont ignorées
        $this->test('Missions uniques ignorées', function () {
            $uniqueMission = Mission::where('address', '789 Rue Unique, Marseille')->first();
            return $uniqueMission && !$uniqueMission->migrated_to_bm_id;
        });
    }

    private function test($description, $callback)
    {
        try {
            $result = $callback();
            
            if ($result) {
                $this->results['tests_passed']++;
                $status = '<fg=green>✓</fg=green>';
            } else {
                $this->results['tests_failed']++;
                $status = '<fg=red>✗</fg=red>';
            }
            
            $this->line("$status $description");
            
            if ($this->option('verbose') && !$result) {
                $this->warn("  Test échoué: $description");
            }
            
        } catch (\Exception $e) {
            $this->results['tests_failed']++;
            $this->results['errors'][] = [
                'test' => $description,
                'error' => $e->getMessage()
            ];
            
            $this->line("<fg=red>✗</fg=red> $description");
            $this->error("  Erreur: " . $e->getMessage());
        }
    }

    private function validateResults()
    {
        $this->info('Validation des résultats...');

        // Vérifier l'intégrité des données
        $this->validateDataIntegrity();
        
        // Vérifier les performances
        $this->validatePerformance();
    }

    private function validateDataIntegrity()
    {
        $this->test('Intégrité des données - Pas de missions orphelines', function () {
            $orphanMissions = Mission::whereNotNull('bail_mobilite_id')
                ->whereDoesntHave('bailMobilite')
                ->count();
            return $orphanMissions === 0;
        });

        $this->test('Intégrité des données - Pas de BM sans missions', function () {
            $bmWithoutMissions = BailMobilite::where(function ($query) {
                $query->whereNull('entry_mission_id')
                      ->orWhereNull('exit_mission_id');
            })->count();
            return $bmWithoutMissions === 0;
        });

        $this->test('Intégrité des données - Dates cohérentes', function () {
            $bms = BailMobilite::where('notes', 'LIKE', '%TEST_MIGRATION%')->get();
            
            foreach ($bms as $bm) {
                if ($bm->start_date >= $bm->end_date) {
                    return false;
                }
                
                if ($bm->entryMission && $bm->entryMission->scheduled_date !== $bm->start_date) {
                    return false;
                }
                
                if ($bm->exitMission && $bm->exitMission->scheduled_date !== $bm->end_date) {
                    return false;
                }
            }
            
            return true;
        });
    }

    private function validatePerformance()
    {
        $this->test('Performance - Temps de requête acceptable', function () {
            $start = microtime(true);
            
            // Requête complexe pour tester les performances
            BailMobilite::with(['entryMission', 'exitMission', 'opsUser'])
                ->where('notes', 'LIKE', '%TEST_MIGRATION%')
                ->get();
            
            $duration = microtime(true) - $start;
            
            // La requête ne doit pas prendre plus de 1 seconde
            return $duration < 1.0;
        });

        $this->test('Performance - Index utilisés correctement', function () {
            // Vérifier que les requêtes utilisent les index
            $explain = DB::select("EXPLAIN SELECT * FROM bail_mobilites WHERE status = 'assigned'");
            
            // Vérifier qu'un index est utilisé (pas de "Using filesort" ou "Using temporary")
            foreach ($explain as $row) {
                if (isset($row->Extra) && 
                    (strpos($row->Extra, 'Using filesort') !== false || 
                     strpos($row->Extra, 'Using temporary') !== false)) {
                    return false;
                }
            }
            
            return true;
        });
    }

    private function displayResults()
    {
        $this->newLine();
        $this->info('=== Résultats des Tests ===');
        
        $total = $this->results['tests_passed'] + $this->results['tests_failed'];
        
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Tests exécutés', $total],
                ['Tests réussis', $this->results['tests_passed']],
                ['Tests échoués', $this->results['tests_failed']],
                ['Taux de réussite', $total > 0 ? round(($this->results['tests_passed'] / $total) * 100, 2) . '%' : '0%'],
            ]
        );

        if (!empty($this->results['errors'])) {
            $this->newLine();
            $this->error('Erreurs détectées:');
            foreach ($this->results['errors'] as $error) {
                $this->line("- {$error['test']}: {$error['error']}");
            }
        }

        if ($this->results['tests_failed'] === 0) {
            $this->newLine();
            $this->info('🎉 Tous les tests sont passés avec succès !');
            $this->info('La migration peut être exécutée en production.');
        } else {
            $this->newLine();
            $this->error('❌ Certains tests ont échoué.');
            $this->error('Corrigez les problèmes avant de procéder à la migration en production.');
        }

        // Nettoyage optionnel
        if ($this->confirm('Voulez-vous nettoyer les données de test ?', true)) {
            $this->resetTestData();
            $this->info('Données de test nettoyées.');
        }
    }
}