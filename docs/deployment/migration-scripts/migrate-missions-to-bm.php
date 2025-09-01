<?php

/**
 * Script de migration des missions existantes vers le système Bail Mobilité
 * 
 * Ce script analyse les missions existantes et les convertit en Bail Mobilité
 * avec leurs missions d'entrée et sortie associées.
 * 
 * Usage: php artisan migrate:missions-to-bm [--dry-run] [--batch-size=100]
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Mission;
use App\Models\BailMobilite;
use App\Models\User;
use Carbon\Carbon;

class MigrateMissionsToBailMobilite extends Command
{
    protected $signature = 'migrate:missions-to-bm 
                           {--dry-run : Exécuter en mode simulation sans modifications}
                           {--batch-size=100 : Nombre de missions à traiter par lot}
                           {--start-date= : Date de début pour filtrer les missions (YYYY-MM-DD)}
                           {--end-date= : Date de fin pour filtrer les missions (YYYY-MM-DD)}';

    protected $description = 'Migre les missions existantes vers le système Bail Mobilité';

    private $dryRun = false;
    private $batchSize = 100;
    private $stats = [
        'missions_analyzed' => 0,
        'bm_created' => 0,
        'missions_converted' => 0,
        'errors' => 0,
        'skipped' => 0
    ];

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        $this->batchSize = (int) $this->option('batch-size');

        $this->info('=== Migration des Missions vers Bail Mobilité ===');
        $this->info('Mode: ' . ($this->dryRun ? 'SIMULATION' : 'PRODUCTION'));
        $this->info('Taille des lots: ' . $this->batchSize);
        $this->newLine();

        if (!$this->dryRun && !$this->confirm('Êtes-vous sûr de vouloir procéder à la migration en mode PRODUCTION ?')) {
            $this->info('Migration annulée.');
            return 0;
        }

        try {
            $this->performMigration();
            $this->displayResults();
            return 0;
        } catch (\Exception $e) {
            $this->error('Erreur lors de la migration: ' . $e->getMessage());
            Log::error('Migration error', ['exception' => $e]);
            return 1;
        }
    }

    private function performMigration()
    {
        // Étape 1: Analyser les missions existantes
        $this->info('Étape 1: Analyse des missions existantes...');
        $missionsQuery = $this->buildMissionsQuery();
        $totalMissions = $missionsQuery->count();
        
        $this->info("Missions à analyser: {$totalMissions}");
        $this->newLine();

        if ($totalMissions === 0) {
            $this->warn('Aucune mission à migrer trouvée.');
            return;
        }

        // Étape 2: Grouper les missions par logement et période
        $this->info('Étape 2: Groupement des missions par séjour...');
        $missionGroups = $this->groupMissionsByStay($missionsQuery);
        
        $this->info("Groupes de missions identifiés: " . count($missionGroups));
        $this->newLine();

        // Étape 3: Créer les Bail Mobilité
        $this->info('Étape 3: Création des Bail Mobilité...');
        $progressBar = $this->output->createProgressBar(count($missionGroups));
        $progressBar->start();

        foreach ($missionGroups as $group) {
            try {
                $this->processMissionGroup($group);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->stats['errors']++;
                Log::error('Error processing mission group', [
                    'group' => $group,
                    'exception' => $e
                ]);
            }
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    private function buildMissionsQuery()
    {
        $query = Mission::query()
            ->whereNotNull('address')
            ->whereNotNull('scheduled_date')
            ->where('status', '!=', 'cancelled');

        if ($startDate = $this->option('start-date')) {
            $query->where('scheduled_date', '>=', $startDate);
        }

        if ($endDate = $this->option('end-date')) {
            $query->where('scheduled_date', '<=', $endDate);
        }

        return $query->orderBy('scheduled_date');
    }

    private function groupMissionsByStay($missionsQuery)
    {
        $missions = $missionsQuery->get();
        $groups = [];

        foreach ($missions as $mission) {
            $this->stats['missions_analyzed']++;
            
            // Identifier le groupe basé sur l'adresse et la période
            $groupKey = $this->generateGroupKey($mission);
            
            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'address' => $mission->address,
                    'missions' => [],
                    'start_date' => null,
                    'end_date' => null,
                    'tenant_info' => $this->extractTenantInfo($mission)
                ];
            }

            $groups[$groupKey]['missions'][] = $mission;
            
            // Mettre à jour les dates de début et fin
            $missionDate = Carbon::parse($mission->scheduled_date);
            if (!$groups[$groupKey]['start_date'] || $missionDate->lt($groups[$groupKey]['start_date'])) {
                $groups[$groupKey]['start_date'] = $missionDate;
            }
            if (!$groups[$groupKey]['end_date'] || $missionDate->gt($groups[$groupKey]['end_date'])) {
                $groups[$groupKey]['end_date'] = $missionDate;
            }
        }

        // Filtrer les groupes valides (au moins une mission d'entrée et une de sortie)
        return array_filter($groups, [$this, 'isValidMissionGroup']);
    }

    private function generateGroupKey($mission)
    {
        // Normaliser l'adresse pour le groupement
        $normalizedAddress = strtolower(trim($mission->address));
        $normalizedAddress = preg_replace('/\s+/', ' ', $normalizedAddress);
        
        // Grouper par adresse et mois (pour éviter de mélanger des séjours différents)
        $month = Carbon::parse($mission->scheduled_date)->format('Y-m');
        
        return md5($normalizedAddress . '_' . $month);
    }

    private function extractTenantInfo($mission)
    {
        // Extraire les informations du locataire depuis les données existantes
        $tenantInfo = [
            'name' => 'Locataire migré',
            'phone' => null,
            'email' => null
        ];

        // Essayer d'extraire depuis les notes ou commentaires
        if ($mission->notes) {
            $this->parseTenantInfoFromNotes($mission->notes, $tenantInfo);
        }

        // Essayer d'extraire depuis la checklist si elle existe
        if ($mission->checklist && $mission->checklist->tenant_comment) {
            $this->parseTenantInfoFromNotes($mission->checklist->tenant_comment, $tenantInfo);
        }

        return $tenantInfo;
    }

    private function parseTenantInfoFromNotes($notes, &$tenantInfo)
    {
        // Rechercher un nom (première ligne ou après "Nom:")
        if (preg_match('/(?:Nom\s*:\s*)?([A-Za-zÀ-ÿ\s]{2,50})/i', $notes, $matches)) {
            $tenantInfo['name'] = trim($matches[1]);
        }

        // Rechercher un téléphone
        if (preg_match('/(?:Tel|Téléphone|Phone)\s*:\s*([+\d\s\-\.]{8,20})/i', $notes, $matches)) {
            $tenantInfo['phone'] = trim($matches[1]);
        }

        // Rechercher un email
        if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $notes, $matches)) {
            $tenantInfo['email'] = trim($matches[1]);
        }
    }

    private function isValidMissionGroup($group)
    {
        // Un groupe valide doit avoir au moins 2 missions (entrée + sortie)
        if (count($group['missions']) < 2) {
            $this->stats['skipped']++;
            return false;
        }

        // Vérifier qu'il y a une différence raisonnable entre start_date et end_date
        $daysDiff = $group['start_date']->diffInDays($group['end_date']);
        if ($daysDiff < 1 || $daysDiff > 365) {
            $this->stats['skipped']++;
            return false;
        }

        return true;
    }

    private function processMissionGroup($group)
    {
        if ($this->dryRun) {
            $this->simulateGroupProcessing($group);
            return;
        }

        DB::transaction(function () use ($group) {
            // Créer le Bail Mobilité
            $bailMobilite = $this->createBailMobilite($group);
            
            // Identifier et convertir les missions d'entrée et sortie
            $this->convertMissions($bailMobilite, $group['missions']);
            
            $this->stats['bm_created']++;
        });
    }

    private function simulateGroupProcessing($group)
    {
        $this->stats['bm_created']++;
        $this->stats['missions_converted'] += count($group['missions']);
        
        // Afficher les détails en mode verbose
        if ($this->output->isVerbose()) {
            $this->line("Simulation - BM créé:");
            $this->line("  Adresse: {$group['address']}");
            $this->line("  Période: {$group['start_date']->format('d/m/Y')} - {$group['end_date']->format('d/m/Y')}");
            $this->line("  Missions: " . count($group['missions']));
        }
    }

    private function createBailMobilite($group)
    {
        // Trouver un utilisateur Ops par défaut ou créer un utilisateur système
        $opsUser = User::role('ops')->first();
        if (!$opsUser) {
            $opsUser = $this->createDefaultOpsUser();
        }

        $bailMobilite = BailMobilite::create([
            'start_date' => $group['start_date']->format('Y-m-d'),
            'end_date' => $group['end_date']->format('Y-m-d'),
            'address' => $group['address'],
            'tenant_name' => $group['tenant_info']['name'],
            'tenant_phone' => $group['tenant_info']['phone'],
            'tenant_email' => $group['tenant_info']['email'],
            'notes' => 'Migré automatiquement depuis les missions existantes',
            'status' => $this->determineInitialStatus($group),
            'ops_user_id' => $opsUser->id,
        ]);

        return $bailMobilite;
    }

    private function createDefaultOpsUser()
    {
        $user = User::create([
            'name' => 'Système Migration',
            'email' => 'migration-system@' . config('app.domain', 'example.com'),
            'password' => bcrypt(str()->random(32)),
            'email_verified_at' => now(),
        ]);

        $user->assignRole('ops');
        
        return $user;
    }

    private function determineInitialStatus($group)
    {
        // Analyser les statuts des missions pour déterminer le statut initial du BM
        $completedMissions = collect($group['missions'])->where('status', 'completed')->count();
        $totalMissions = count($group['missions']);

        if ($completedMissions === $totalMissions) {
            return 'completed';
        } elseif ($completedMissions > 0) {
            return 'in_progress';
        } else {
            return 'assigned';
        }
    }

    private function convertMissions($bailMobilite, $missions)
    {
        // Trier les missions par date
        $sortedMissions = collect($missions)->sortBy('scheduled_date');
        
        // La première mission devient l'entrée, la dernière devient la sortie
        $entryMission = $sortedMissions->first();
        $exitMission = $sortedMissions->last();

        // Convertir la mission d'entrée
        $convertedEntry = $this->convertMission($entryMission, $bailMobilite, 'entry');
        
        // Convertir la mission de sortie (si différente de l'entrée)
        $convertedExit = null;
        if ($entryMission->id !== $exitMission->id) {
            $convertedExit = $this->convertMission($exitMission, $bailMobilite, 'exit');
        } else {
            // Si une seule mission, créer une mission de sortie basée sur la date de fin
            $convertedExit = $this->createExitMission($bailMobilite);
        }

        // Mettre à jour le BailMobilite avec les références des missions
        $bailMobilite->update([
            'entry_mission_id' => $convertedEntry->id,
            'exit_mission_id' => $convertedExit->id,
        ]);

        // Marquer les missions originales comme migrées
        foreach ($missions as $mission) {
            $mission->update([
                'migrated_to_bm_id' => $bailMobilite->id,
                'migration_date' => now(),
            ]);
        }

        $this->stats['missions_converted'] += count($missions);
    }

    private function convertMission($originalMission, $bailMobilite, $type)
    {
        $newMission = Mission::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => $type,
            'scheduled_date' => $type === 'entry' ? $bailMobilite->start_date : $bailMobilite->end_date,
            'scheduled_time' => $originalMission->scheduled_time,
            'address' => $bailMobilite->address,
            'status' => $originalMission->status,
            'assigned_agent_id' => $originalMission->assigned_agent_id,
            'ops_assigned_by' => $bailMobilite->ops_user_id,
            'notes' => $originalMission->notes . "\n\n[Migré depuis mission #{$originalMission->id}]",
            'created_at' => $originalMission->created_at,
            'updated_at' => now(),
        ]);

        // Copier la checklist si elle existe
        if ($originalMission->checklist) {
            $this->copyChecklist($originalMission->checklist, $newMission);
        }

        return $newMission;
    }

    private function createExitMission($bailMobilite)
    {
        return Mission::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'scheduled_date' => $bailMobilite->end_date,
            'address' => $bailMobilite->address,
            'status' => 'assigned',
            'ops_assigned_by' => $bailMobilite->ops_user_id,
            'notes' => 'Mission de sortie créée automatiquement lors de la migration',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function copyChecklist($originalChecklist, $newMission)
    {
        $newChecklist = $originalChecklist->replicate();
        $newChecklist->mission_id = $newMission->id;
        $newChecklist->created_at = now();
        $newChecklist->updated_at = now();
        $newChecklist->save();

        // Copier les items de checklist
        foreach ($originalChecklist->items as $item) {
            $newItem = $item->replicate();
            $newItem->checklist_id = $newChecklist->id;
            $newItem->save();

            // Copier les photos
            foreach ($item->photos as $photo) {
                $newPhoto = $photo->replicate();
                $newPhoto->checklist_item_id = $newItem->id;
                $newPhoto->save();
            }
        }
    }

    private function displayResults()
    {
        $this->newLine();
        $this->info('=== Résultats de la Migration ===');
        
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Missions analysées', $this->stats['missions_analyzed']],
                ['Bail Mobilité créés', $this->stats['bm_created']],
                ['Missions converties', $this->stats['missions_converted']],
                ['Missions ignorées', $this->stats['skipped']],
                ['Erreurs', $this->stats['errors']],
            ]
        );

        if ($this->dryRun) {
            $this->warn('Mode SIMULATION - Aucune modification n\'a été apportée à la base de données.');
        } else {
            $this->info('Migration terminée avec succès !');
        }

        if ($this->stats['errors'] > 0) {
            $this->warn("Attention: {$this->stats['errors']} erreurs détectées. Consultez les logs pour plus de détails.");
        }
    }
}