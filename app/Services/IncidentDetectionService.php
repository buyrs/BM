<?php

namespace App\Services;

use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\BailMobiliteSignature;
use App\Models\IncidentReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IncidentDetectionService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Detect incidents for a specific bail mobilité.
     */
    public function detectIncidents(BailMobilite $bailMobilite): array
    {
        $incidents = [];

        // Check for entry mission incidents
        if ($bailMobilite->entryMission) {
            $entryIncidents = $this->detectEntryIncidents($bailMobilite->entryMission);
            $incidents = array_merge($incidents, $entryIncidents);
        }

        // Check for exit mission incidents
        if ($bailMobilite->exitMission) {
            $exitIncidents = $this->detectExitIncidents($bailMobilite->exitMission);
            $incidents = array_merge($incidents, $exitIncidents);
        }

        // Check for general bail mobilité incidents
        $generalIncidents = $this->detectGeneralIncidents($bailMobilite);
        $incidents = array_merge($incidents, $generalIncidents);

        return $incidents;
    }

    /**
     * Detect incidents for entry missions.
     */
    protected function detectEntryIncidents(Mission $mission): array
    {
        $incidents = [];
        $checklist = $mission->checklist;

        if (!$checklist) {
            $incidents[] = [
                'type' => 'missing_checklist',
                'severity' => 'high',
                'message' => 'Checklist d\'entrée manquante',
                'mission_id' => $mission->id,
                'mission_type' => 'entry'
            ];
            return $incidents;
        }

        // Check if checklist is incomplete
        if ($checklist->status !== 'completed') {
            $incidents[] = [
                'type' => 'incomplete_checklist',
                'severity' => 'high',
                'message' => 'Checklist d\'entrée incomplète',
                'mission_id' => $mission->id,
                'mission_type' => 'entry',
                'checklist_id' => $checklist->id
            ];
        }

        // Check for missing tenant signature
        if (empty($checklist->tenant_signature)) {
            $incidents[] = [
                'type' => 'missing_tenant_signature',
                'severity' => 'high',
                'message' => 'Signature du locataire manquante sur la checklist d\'entrée',
                'mission_id' => $mission->id,
                'mission_type' => 'entry',
                'checklist_id' => $checklist->id
            ];
        }

        // Check for missing required photos
        $missingPhotos = $this->checkRequiredPhotos($checklist);
        if (!empty($missingPhotos)) {
            $incidents[] = [
                'type' => 'missing_required_photos',
                'severity' => 'medium',
                'message' => 'Photos obligatoires manquantes: ' . implode(', ', $missingPhotos),
                'mission_id' => $mission->id,
                'mission_type' => 'entry',
                'checklist_id' => $checklist->id,
                'missing_photos' => $missingPhotos
            ];
        }

        // Check for missing contract signature
        $bailMobilite = $mission->bailMobilite;
        if ($bailMobilite) {
            $entrySignature = $bailMobilite->entrySignature;
            if (!$entrySignature || !$entrySignature->isTenantSigned()) {
                $incidents[] = [
                    'type' => 'missing_contract_signature',
                    'severity' => 'critical',
                    'message' => 'Signature du contrat d\'entrée manquante',
                    'mission_id' => $mission->id,
                    'mission_type' => 'entry',
                    'bail_mobilite_id' => $bailMobilite->id
                ];
            }
        }

        return $incidents;
    }

    /**
     * Detect incidents for exit missions.
     */
    protected function detectExitIncidents(Mission $mission): array
    {
        $incidents = [];
        $checklist = $mission->checklist;

        if (!$checklist) {
            $incidents[] = [
                'type' => 'missing_checklist',
                'severity' => 'high',
                'message' => 'Checklist de sortie manquante',
                'mission_id' => $mission->id,
                'mission_type' => 'exit'
            ];
            return $incidents;
        }

        // Check if checklist is incomplete
        if ($checklist->status !== 'completed') {
            $incidents[] = [
                'type' => 'incomplete_checklist',
                'severity' => 'high',
                'message' => 'Checklist de sortie incomplète',
                'mission_id' => $mission->id,
                'mission_type' => 'exit',
                'checklist_id' => $checklist->id
            ];
        }

        // Check for missing tenant signature
        if (empty($checklist->tenant_signature)) {
            $incidents[] = [
                'type' => 'missing_tenant_signature',
                'severity' => 'high',
                'message' => 'Signature du locataire manquante sur la checklist de sortie',
                'mission_id' => $mission->id,
                'mission_type' => 'exit',
                'checklist_id' => $checklist->id
            ];
        }

        // Check for keys not returned
        $keysReturned = $this->checkKeysReturned($checklist);
        if (!$keysReturned) {
            $incidents[] = [
                'type' => 'keys_not_returned',
                'severity' => 'critical',
                'message' => 'Clés non remises par le locataire',
                'mission_id' => $mission->id,
                'mission_type' => 'exit',
                'checklist_id' => $checklist->id
            ];
        }

        // Check for missing required photos
        $missingPhotos = $this->checkRequiredPhotos($checklist);
        if (!empty($missingPhotos)) {
            $incidents[] = [
                'type' => 'missing_required_photos',
                'severity' => 'medium',
                'message' => 'Photos obligatoires manquantes: ' . implode(', ', $missingPhotos),
                'mission_id' => $mission->id,
                'mission_type' => 'exit',
                'checklist_id' => $checklist->id,
                'missing_photos' => $missingPhotos
            ];
        }

        // Check for missing exit contract signature
        $bailMobilite = $mission->bailMobilite;
        if ($bailMobilite) {
            $exitSignature = $bailMobilite->exitSignature;
            if (!$exitSignature || !$exitSignature->isTenantSigned()) {
                $incidents[] = [
                    'type' => 'missing_contract_signature',
                    'severity' => 'critical',
                    'message' => 'Signature du contrat de sortie manquante',
                    'mission_id' => $mission->id,
                    'mission_type' => 'exit',
                    'bail_mobilite_id' => $bailMobilite->id
                ];
            }
        }

        return $incidents;
    }

    /**
     * Detect general incidents for bail mobilité.
     */
    protected function detectGeneralIncidents(BailMobilite $bailMobilite): array
    {
        $incidents = [];

        // Check for overdue missions
        $overdueIncidents = $this->checkOverdueMissions($bailMobilite);
        $incidents = array_merge($incidents, $overdueIncidents);

        // Check for validation timeouts
        $validationTimeouts = $this->checkValidationTimeouts($bailMobilite);
        $incidents = array_merge($incidents, $validationTimeouts);

        return $incidents;
    }

    /**
     * Check if required photos are missing from checklist.
     */
    protected function checkRequiredPhotos(Checklist $checklist): array
    {
        $missingPhotos = [];
        $requiredPhotoItems = [
            'entrance_door',
            'living_room_general',
            'kitchen_general',
            'bathroom_general',
            'bedroom_general'
        ];

        foreach ($requiredPhotoItems as $itemName) {
            $item = $checklist->items()->where('name', $itemName)->first();
            if (!$item || $item->photos()->count() === 0) {
                $missingPhotos[] = $itemName;
            }
        }

        return $missingPhotos;
    }

    /**
     * Check if keys have been returned (for exit missions).
     */
    protected function checkKeysReturned(Checklist $checklist): bool
    {
        $generalInfo = $checklist->general_info ?? [];
        $keysInfo = $generalInfo['keys'] ?? [];
        
        // Check if keys are marked as returned
        return isset($keysInfo['returned']) && $keysInfo['returned'] === true;
    }

    /**
     * Check for overdue missions.
     */
    protected function checkOverdueMissions(BailMobilite $bailMobilite): array
    {
        $incidents = [];
        $now = Carbon::now();

        // Check entry mission
        if ($bailMobilite->entryMission) {
            $entryMission = $bailMobilite->entryMission;
            $scheduledDateTime = $entryMission->getFullScheduledDateTime();
            
            if ($scheduledDateTime && $scheduledDateTime->addHours(2)->isPast() && $entryMission->status !== 'completed') {
                $incidents[] = [
                    'type' => 'overdue_mission',
                    'severity' => 'high',
                    'message' => 'Mission d\'entrée en retard (plus de 2h)',
                    'mission_id' => $entryMission->id,
                    'mission_type' => 'entry',
                    'scheduled_at' => $scheduledDateTime->toDateTimeString(),
                    'overdue_hours' => $now->diffInHours($scheduledDateTime)
                ];
            }
        }

        // Check exit mission
        if ($bailMobilite->exitMission) {
            $exitMission = $bailMobilite->exitMission;
            $scheduledDateTime = $exitMission->getFullScheduledDateTime();
            
            if ($scheduledDateTime && $scheduledDateTime->addHours(2)->isPast() && $exitMission->status !== 'completed') {
                $incidents[] = [
                    'type' => 'overdue_mission',
                    'severity' => 'high',
                    'message' => 'Mission de sortie en retard (plus de 2h)',
                    'mission_id' => $exitMission->id,
                    'mission_type' => 'exit',
                    'scheduled_at' => $scheduledDateTime->toDateTimeString(),
                    'overdue_hours' => $now->diffInHours($scheduledDateTime)
                ];
            }
        }

        return $incidents;
    }

    /**
     * Check for validation timeouts.
     */
    protected function checkValidationTimeouts(BailMobilite $bailMobilite): array
    {
        $incidents = [];
        $now = Carbon::now();

        // Check if entry checklist is waiting for validation too long
        if ($bailMobilite->entryMission && $bailMobilite->entryMission->checklist) {
            $checklist = $bailMobilite->entryMission->checklist;
            if ($checklist->status === 'pending_validation' && 
                $checklist->updated_at->addHours(24)->isPast()) {
                $incidents[] = [
                    'type' => 'validation_timeout',
                    'severity' => 'medium',
                    'message' => 'Checklist d\'entrée en attente de validation depuis plus de 24h',
                    'mission_id' => $bailMobilite->entryMission->id,
                    'mission_type' => 'entry',
                    'checklist_id' => $checklist->id,
                    'waiting_hours' => $now->diffInHours($checklist->updated_at)
                ];
            }
        }

        // Check if exit checklist is waiting for validation too long
        if ($bailMobilite->exitMission && $bailMobilite->exitMission->checklist) {
            $checklist = $bailMobilite->exitMission->checklist;
            if ($checklist->status === 'pending_validation' && 
                $checklist->updated_at->addHours(24)->isPast()) {
                $incidents[] = [
                    'type' => 'validation_timeout',
                    'severity' => 'medium',
                    'message' => 'Checklist de sortie en attente de validation depuis plus de 24h',
                    'mission_id' => $bailMobilite->exitMission->id,
                    'mission_type' => 'exit',
                    'checklist_id' => $checklist->id,
                    'waiting_hours' => $now->diffInHours($checklist->updated_at)
                ];
            }
        }

        return $incidents;
    }

    /**
     * Process incidents and trigger appropriate actions.
     */
    public function processIncidents(BailMobilite $bailMobilite, array $incidents): void
    {
        if (empty($incidents)) {
            return;
        }

        // Determine if bail mobilité should be marked as incident
        $criticalIncidents = array_filter($incidents, fn($incident) => $incident['severity'] === 'critical');
        $highIncidents = array_filter($incidents, fn($incident) => $incident['severity'] === 'high');

        $shouldMarkAsIncident = !empty($criticalIncidents) || count($highIncidents) >= 2;

        if ($shouldMarkAsIncident && $bailMobilite->status !== 'incident') {
            $this->markBailMobiliteAsIncident($bailMobilite, $incidents);
        }

        // Create incident reports
        foreach ($incidents as $incident) {
            $this->createIncidentReport($bailMobilite, $incident);
        }

        // Send notifications
        $this->sendIncidentNotifications($bailMobilite, $incidents);
    }

    /**
     * Mark bail mobilité as incident.
     */
    protected function markBailMobiliteAsIncident(BailMobilite $bailMobilite, array $incidents): void
    {
        $previousStatus = $bailMobilite->status;
        $bailMobilite->update(['status' => 'incident']);

        Log::info("BailMobilite {$bailMobilite->id} marked as incident. Previous status: {$previousStatus}");

        // Create a summary of incidents
        $incidentSummary = collect($incidents)->pluck('message')->implode('; ');
        
        // Send incident alert
        $this->notificationService->sendIncidentAlert($bailMobilite, $incidentSummary);
    }

    /**
     * Create incident report.
     */
    protected function createIncidentReport(BailMobilite $bailMobilite, array $incident): IncidentReport
    {
        return IncidentReport::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_id' => $incident['mission_id'] ?? null,
            'checklist_id' => $incident['checklist_id'] ?? null,
            'type' => $incident['type'],
            'severity' => $incident['severity'],
            'title' => $incident['message'],
            'description' => $this->generateIncidentDescription($incident),
            'metadata' => $incident,
            'status' => 'open',
            'detected_at' => now(),
            'created_by' => null // System detected
        ]);
    }

    /**
     * Generate detailed incident description.
     */
    protected function generateIncidentDescription(array $incident): string
    {
        $description = $incident['message'];

        if (isset($incident['mission_type'])) {
            $description .= "\nType de mission: " . ucfirst($incident['mission_type']);
        }

        if (isset($incident['overdue_hours'])) {
            $description .= "\nHeures de retard: " . $incident['overdue_hours'];
        }

        if (isset($incident['waiting_hours'])) {
            $description .= "\nHeures d'attente: " . $incident['waiting_hours'];
        }

        if (isset($incident['missing_photos'])) {
            $description .= "\nPhotos manquantes: " . implode(', ', $incident['missing_photos']);
        }

        $description .= "\nDétecté automatiquement le: " . now()->format('d/m/Y à H:i');

        return $description;
    }

    /**
     * Send incident notifications.
     */
    protected function sendIncidentNotifications(BailMobilite $bailMobilite, array $incidents): void
    {
        // Group incidents by severity
        $criticalIncidents = array_filter($incidents, fn($incident) => $incident['severity'] === 'critical');
        $highIncidents = array_filter($incidents, fn($incident) => $incident['severity'] === 'high');
        $mediumIncidents = array_filter($incidents, fn($incident) => $incident['severity'] === 'medium');

        // Send immediate alerts for critical incidents
        if (!empty($criticalIncidents)) {
            $criticalMessages = collect($criticalIncidents)->pluck('message')->implode('; ');
            $this->notificationService->sendIncidentAlert($bailMobilite, "CRITIQUE: " . $criticalMessages);
            
            // Broadcast real-time alert for critical incidents
            $this->notificationService->broadcastToOpsUsers([
                'type' => 'critical_incident',
                'bail_mobilite_id' => $bailMobilite->id,
                'tenant_name' => $bailMobilite->tenant_name,
                'message' => $criticalMessages,
                'severity' => 'critical',
                'requires_immediate_action' => true
            ]);
        }

        // Send alerts for high severity incidents
        if (!empty($highIncidents)) {
            $highMessages = collect($highIncidents)->pluck('message')->implode('; ');
            $this->notificationService->sendIncidentAlert($bailMobilite, "URGENT: " . $highMessages);
            
            // Broadcast real-time alert for high severity incidents
            $this->notificationService->broadcastToOpsUsers([
                'type' => 'high_incident',
                'bail_mobilite_id' => $bailMobilite->id,
                'tenant_name' => $bailMobilite->tenant_name,
                'message' => $highMessages,
                'severity' => 'high',
                'requires_action' => true
            ]);
        }

        // Send notifications for medium severity incidents (less urgent)
        if (!empty($mediumIncidents)) {
            $mediumMessages = collect($mediumIncidents)->pluck('message')->implode('; ');
            $this->notificationService->sendIncidentAlert($bailMobilite, "ATTENTION: " . $mediumMessages);
        }
    }

    /**
     * Run incident detection for all active bail mobilités.
     */
    public function runIncidentDetection(): array
    {
        $bailMobilites = BailMobilite::whereIn('status', ['assigned', 'in_progress'])
                                   ->with(['entryMission.checklist', 'exitMission.checklist', 'entrySignature', 'exitSignature'])
                                   ->get();

        $results = [
            'processed' => 0,
            'incidents_found' => 0,
            'bail_mobilites_marked_as_incident' => 0
        ];

        foreach ($bailMobilites as $bailMobilite) {
            $incidents = $this->detectIncidents($bailMobilite);
            
            if (!empty($incidents)) {
                $this->processIncidents($bailMobilite, $incidents);
                $results['incidents_found'] += count($incidents);
                
                if ($bailMobilite->fresh()->status === 'incident') {
                    $results['bail_mobilites_marked_as_incident']++;
                }
            }
            
            $results['processed']++;
        }

        Log::info("Incident detection completed", $results);

        return $results;
    }

    /**
     * Get incident statistics.
     */
    public function getIncidentStats(): array
    {
        return [
            'total_incidents' => IncidentReport::count(),
            'open_incidents' => IncidentReport::where('status', 'open')->count(),
            'critical_incidents' => IncidentReport::where('severity', 'critical')->where('status', 'open')->count(),
            'high_incidents' => IncidentReport::where('severity', 'high')->where('status', 'open')->count(),
            'bail_mobilites_with_incidents' => BailMobilite::where('status', 'incident')->count(),
            'incidents_today' => IncidentReport::whereDate('detected_at', today())->count(),
            'incidents_this_week' => IncidentReport::whereBetween('detected_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];
    }
}