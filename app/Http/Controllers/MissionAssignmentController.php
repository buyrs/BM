<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\User;
use App\Services\MissionService;
use App\Services\NotificationService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MissionAssignmentController extends Controller
{
    public function __construct(
        private MissionService $missionService,
        private NotificationService $notificationService,
        private AuditService $auditService
    ) {
        $this->middleware('role:super-admin|ops');
    }

    /**
     * Display assignment dashboard for Ops staff
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $filters = $request->only(['status', 'agent', 'date_from', 'date_to', 'priority']);

        // Get unassigned missions
        $unassignedMissions = Mission::with(['bailMobilite'])
            ->where('status', 'unassigned')
            ->when($filters['date_from'], fn($q) => $q->where('scheduled_at', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn($q) => $q->where('scheduled_at', '<=', $filters['date_to']))
            ->when($filters['priority'], fn($q) => $q->where('priority', $filters['priority']))
            ->orderBy('scheduled_at')
            ->get();

        // Get assigned missions
        $assignedMissions = Mission::with(['agent', 'bailMobilite'])
            ->where('status', 'assigned')
            ->when($filters['agent'], fn($q) => $q->where('agent_id', $filters['agent']))
            ->when($filters['date_from'], fn($q) => $q->where('scheduled_at', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn($q) => $q->where('scheduled_at', '<=', $filters['date_to']))
            ->when($filters['priority'], fn($q) => $q->where('priority', $filters['priority']))
            ->orderBy('scheduled_at')
            ->get();

        // Get available agents
        $agents = User::role('checker')
            ->with(['missions' => function($query) {
                $query->where('status', 'in_progress')
                      ->where('scheduled_at', '>=', now()->startOfDay())
                      ->where('scheduled_at', '<=', now()->endOfDay());
            }])
            ->get()
            ->map(function($agent) {
                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'email' => $agent->email,
                    'current_missions' => $agent->missions->count(),
                    'is_available' => $agent->missions->count() < 3, // Max 3 missions per day
                    'last_mission' => $agent->missions->max('scheduled_at')
                ];
            });

        // Get assignment statistics
        $stats = $this->getAssignmentStatistics();

        return view('pages.missions.assignment', compact(
            'unassignedMissions',
            'assignedMissions', 
            'agents',
            'filters',
            'stats'
        ));
    }

    /**
     * Assign single mission to agent
     */
    public function assignSingle(Request $request, Mission $mission): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500',
            'priority' => 'nullable|in:low,normal,high,urgent'
        ]);

        try {
            DB::beginTransaction();

            $agent = User::findOrFail($validated['agent_id']);
            
            // Check agent availability
            $availability = $this->checkAgentAvailability($agent, $mission->scheduled_at);
            if (!$availability['available']) {
                return response()->json([
                    'success' => false,
                    'message' => $availability['reason']
                ], 422);
            }

            // Update mission
            $mission->update([
                'agent_id' => $agent->id,
                'status' => 'assigned',
                'ops_assigned_by' => Auth::id(),
                'assignment_notes' => $validated['notes'],
                'priority' => $validated['priority'] ?? 'normal',
                'assigned_at' => now()
            ]);

            // Send notification to agent
            $this->notificationService->sendMissionAssignmentNotification($mission, $agent);

            // Log assignment
            $this->auditService->logAssignment($mission, $agent, Auth::user());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mission assignée avec succès',
                'mission' => [
                    'id' => $mission->id,
                    'agent_name' => $agent->name,
                    'status' => $mission->status,
                    'assigned_at' => $mission->assigned_at->format('d/m/Y H:i')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign mission', [
                'mission_id' => $mission->id,
                'agent_id' => $validated['agent_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation de la mission'
            ], 500);
        }
    }

    /**
     * Bulk assign missions to agents
     */
    public function bulkAssign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mission_ids' => 'required|array|min:1',
            'mission_ids.*' => 'exists:missions,id',
            'assignment_strategy' => 'required|in:auto,manual,round_robin',
            'agent_id' => 'required_if:assignment_strategy,manual|exists:users,id',
            'max_missions_per_agent' => 'nullable|integer|min:1|max:10',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $missions = Mission::whereIn('id', $validated['mission_ids'])
                ->where('status', 'unassigned')
                ->get();

            $results = [];
            $assignedCount = 0;

            switch ($validated['assignment_strategy']) {
                case 'auto':
                    $results = $this->autoAssignMissions($missions, $validated);
                    break;
                case 'manual':
                    $results = $this->manualAssignMissions($missions, $validated);
                    break;
                case 'round_robin':
                    $results = $this->roundRobinAssignMissions($missions, $validated);
                    break;
            }

            $assignedCount = collect($results)->where('success', true)->count();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$assignedCount} missions assignées avec succès",
                'results' => $results,
                'assigned_count' => $assignedCount,
                'total_count' => count($validated['mission_ids'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk assign missions', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'data' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation en lot'
            ], 500);
        }
    }

    /**
     * Reassign mission to different agent
     */
    public function reassign(Request $request, Mission $mission): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:500',
            'notify_previous_agent' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            $oldAgent = $mission->agent;
            $newAgent = User::findOrFail($validated['agent_id']);

            // Check new agent availability
            $availability = $this->checkAgentAvailability($newAgent, $mission->scheduled_at);
            if (!$availability['available']) {
                return response()->json([
                    'success' => false,
                    'message' => $availability['reason']
                ], 422);
            }

            // Update mission
            $mission->update([
                'agent_id' => $newAgent->id,
                'previous_agent_id' => $oldAgent?->id,
                'reassignment_reason' => $validated['reason'],
                'reassigned_at' => now(),
                'reassigned_by' => Auth::id()
            ]);

            // Send notifications
            $this->notificationService->sendMissionReassignmentNotification($mission, $newAgent, $oldAgent);
            
            if ($validated['notify_previous_agent'] && $oldAgent) {
                $this->notificationService->sendMissionUnassignmentNotification($mission, $oldAgent);
            }

            // Log reassignment
            $this->auditService->logReassignment($mission, $oldAgent, $newAgent, Auth::user());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mission réassignée avec succès',
                'mission' => [
                    'id' => $mission->id,
                    'agent_name' => $newAgent->name,
                    'previous_agent_name' => $oldAgent?->name
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reassign mission', [
                'mission_id' => $mission->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réassignation'
            ], 500);
        }
    }

    /**
     * Get agent availability and workload
     */
    public function getAgentAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'exclude_mission_id' => 'nullable|exists:missions,id'
        ]);

        try {
            $agent = User::findOrFail($validated['agent_id']);
            $date = Carbon::parse($validated['date']);
            $excludeMissionId = $validated['exclude_mission_id'] ?? null;

            $availability = $this->checkAgentAvailability($agent, $date, $excludeMissionId);

            return response()->json([
                'success' => true,
                'availability' => $availability
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get agent availability', [
                'agent_id' => $validated['agent_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification de disponibilité'
            ], 500);
        }
    }

    /**
     * Auto-assign missions based on agent availability and workload
     */
    private function autoAssignMissions($missions, $data): array
    {
        $results = [];
        $agents = User::role('checker')->get();
        $maxMissionsPerAgent = $data['max_missions_per_agent'] ?? 3;

        foreach ($missions as $mission) {
            $bestAgent = $this->findBestAgentForMission($mission, $agents, $maxMissionsPerAgent);
            
            if ($bestAgent) {
                try {
                    $mission->update([
                        'agent_id' => $bestAgent->id,
                        'status' => 'assigned',
                        'ops_assigned_by' => Auth::id(),
                        'assignment_notes' => $data['notes'] ?? 'Assignation automatique',
                        'priority' => 'normal',
                        'assigned_at' => now()
                    ]);

                    $this->notificationService->sendMissionAssignmentNotification($mission, $bestAgent);
                    $this->auditService->logAssignment($mission, $bestAgent, Auth::user());

                    $results[] = [
                        'mission_id' => $mission->id,
                        'success' => true,
                        'agent_name' => $bestAgent->name,
                        'message' => 'Assignée à ' . $bestAgent->name
                    ];

                } catch (\Exception $e) {
                    $results[] = [
                        'mission_id' => $mission->id,
                        'success' => false,
                        'message' => 'Erreur lors de l\'assignation: ' . $e->getMessage()
                    ];
                }
            } else {
                $results[] = [
                    'mission_id' => $mission->id,
                    'success' => false,
                    'message' => 'Aucun agent disponible'
                ];
            }
        }

        return $results;
    }

    /**
     * Manual assignment of missions to specific agent
     */
    private function manualAssignMissions($missions, $data): array
    {
        $results = [];
        $agent = User::findOrFail($data['agent_id']);

        foreach ($missions as $mission) {
            $availability = $this->checkAgentAvailability($agent, $mission->scheduled_at);
            
            if ($availability['available']) {
                try {
                    $mission->update([
                        'agent_id' => $agent->id,
                        'status' => 'assigned',
                        'ops_assigned_by' => Auth::id(),
                        'assignment_notes' => $data['notes'] ?? 'Assignation manuelle',
                        'priority' => 'normal',
                        'assigned_at' => now()
                    ]);

                    $this->notificationService->sendMissionAssignmentNotification($mission, $agent);
                    $this->auditService->logAssignment($mission, $agent, Auth::user());

                    $results[] = [
                        'mission_id' => $mission->id,
                        'success' => true,
                        'agent_name' => $agent->name,
                        'message' => 'Assignée à ' . $agent->name
                    ];

                } catch (\Exception $e) {
                    $results[] = [
                        'mission_id' => $mission->id,
                        'success' => false,
                        'message' => 'Erreur lors de l\'assignation: ' . $e->getMessage()
                    ];
                }
            } else {
                $results[] = [
                    'mission_id' => $mission->id,
                    'success' => false,
                    'message' => $availability['reason']
                ];
            }
        }

        return $results;
    }

    /**
     * Round-robin assignment of missions
     */
    private function roundRobinAssignMissions($missions, $data): array
    {
        $results = [];
        $agents = User::role('checker')->get();
        $maxMissionsPerAgent = $data['max_missions_per_agent'] ?? 3;
        $agentIndex = 0;

        foreach ($missions as $mission) {
            $assigned = false;
            $attempts = 0;

            while (!$assigned && $attempts < count($agents)) {
                $agent = $agents[$agentIndex % count($agents)];
                $availability = $this->checkAgentAvailability($agent, $mission->scheduled_at);

                if ($availability['available']) {
                    try {
                        $mission->update([
                            'agent_id' => $agent->id,
                            'status' => 'assigned',
                            'ops_assigned_by' => Auth::id(),
                            'assignment_notes' => $data['notes'] ?? 'Assignation round-robin',
                            'priority' => 'normal',
                            'assigned_at' => now()
                        ]);

                        $this->notificationService->sendMissionAssignmentNotification($mission, $agent);
                        $this->auditService->logAssignment($mission, $agent, Auth::user());

                        $results[] = [
                            'mission_id' => $mission->id,
                            'success' => true,
                            'agent_name' => $agent->name,
                            'message' => 'Assignée à ' . $agent->name
                        ];

                        $assigned = true;

                    } catch (\Exception $e) {
                        $results[] = [
                            'mission_id' => $mission->id,
                            'success' => false,
                            'message' => 'Erreur lors de l\'assignation: ' . $e->getMessage()
                        ];
                        $assigned = true; // Stop trying for this mission
                    }
                }

                $agentIndex++;
                $attempts++;
            }

            if (!$assigned) {
                $results[] = [
                    'mission_id' => $mission->id,
                    'success' => false,
                    'message' => 'Aucun agent disponible'
                ];
            }
        }

        return $results;
    }

    /**
     * Find the best agent for a mission based on availability and workload
     */
    private function findBestAgentForMission($mission, $agents, $maxMissionsPerAgent): ?User
    {
        $bestAgent = null;
        $bestScore = -1;

        foreach ($agents as $agent) {
            $availability = $this->checkAgentAvailability($agent, $mission->scheduled_at);
            
            if ($availability['available']) {
                // Calculate score based on workload and other factors
                $score = $this->calculateAgentScore($agent, $mission, $maxMissionsPerAgent);
                
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestAgent = $agent;
                }
            }
        }

        return $bestAgent;
    }

    /**
     * Calculate agent score for mission assignment
     */
    private function calculateAgentScore($agent, $mission, $maxMissionsPerAgent): int
    {
        $score = 100; // Base score

        // Reduce score based on current workload
        $currentMissions = $agent->missions()
            ->where('status', 'in_progress')
            ->whereDate('scheduled_at', $mission->scheduled_at)
            ->count();

        $workloadRatio = $currentMissions / $maxMissionsPerAgent;
        $score -= ($workloadRatio * 50); // Reduce score based on workload

        // Bonus for agents with fewer total missions today
        $todayMissions = $agent->missions()
            ->whereDate('scheduled_at', $mission->scheduled_at)
            ->count();

        if ($todayMissions === 0) {
            $score += 20; // Bonus for agents with no missions today
        }

        // Bonus for agents who have completed similar missions recently
        $similarMissions = $agent->missions()
            ->where('mission_type', $mission->mission_type)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(30))
            ->count();

        $score += ($similarMissions * 5); // Small bonus for experience

        return max(0, $score); // Ensure non-negative score
    }

    /**
     * Check agent availability for a specific date/time
     */
    private function checkAgentAvailability($agent, $scheduledAt, $excludeMissionId = null): array
    {
        $date = Carbon::parse($scheduledAt);
        
        // Check if agent has too many missions on this date
        $query = $agent->missions()
            ->whereDate('scheduled_at', $date)
            ->where('status', '!=', 'cancelled');

        if ($excludeMissionId) {
            $query->where('id', '!=', $excludeMissionId);
        }

        $missionsCount = $query->count();

        if ($missionsCount >= 3) {
            return [
                'available' => false,
                'reason' => 'Agent a déjà 3 missions ce jour-là',
                'current_missions' => $missionsCount
            ];
        }

        // Check for time conflicts
        $conflictingMissions = $agent->missions()
            ->whereDate('scheduled_at', $date)
            ->where('status', '!=', 'cancelled')
            ->where(function($query) use ($scheduledAt) {
                $startTime = Carbon::parse($scheduledAt)->subHour();
                $endTime = Carbon::parse($scheduledAt)->addHour();
                $query->whereTime('scheduled_at', '>=', $startTime->format('H:i:s'))
                      ->whereTime('scheduled_at', '<=', $endTime->format('H:i:s'));
            });

        if ($excludeMissionId) {
            $conflictingMissions->where('id', '!=', $excludeMissionId);
        }

        if ($conflictingMissions->exists()) {
            return [
                'available' => false,
                'reason' => 'Conflit d\'horaire avec une autre mission',
                'current_missions' => $missionsCount
            ];
        }

        return [
            'available' => true,
            'reason' => 'Agent disponible',
            'current_missions' => $missionsCount
        ];
    }

    /**
     * Get assignment statistics
     */
    private function getAssignmentStatistics(): array
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();

        return [
            'unassigned_today' => Mission::where('status', 'unassigned')
                ->whereDate('scheduled_at', $today)
                ->count(),
            'assigned_today' => Mission::where('status', 'assigned')
                ->whereDate('scheduled_at', $today)
                ->count(),
            'completed_today' => Mission::where('status', 'completed')
                ->whereDate('completed_at', $today)
                ->count(),
            'total_this_week' => Mission::where('scheduled_at', '>=', $thisWeek)
                ->count(),
            'avg_assignment_time' => $this->getAverageAssignmentTime(),
            'agent_workload' => $this->getAgentWorkloadDistribution()
        ];
    }

    /**
     * Get average assignment time
     */
    private function getAverageAssignmentTime(): float
    {
        $avgMinutes = Mission::whereNotNull('assigned_at')
            ->whereNotNull('created_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, assigned_at)) as avg_minutes')
            ->value('avg_minutes');

        return round($avgMinutes ?? 0, 1);
    }

    /**
     * Get agent workload distribution
     */
    private function getAgentWorkloadDistribution(): array
    {
        return User::role('checker')
            ->withCount(['missions' => function($query) {
                $query->where('status', 'in_progress')
                      ->whereDate('scheduled_at', now()->startOfDay());
            }])
            ->get()
            ->map(function($agent) {
                return [
                    'name' => $agent->name,
                    'missions_count' => $agent->missions_count,
                    'workload_percentage' => min(100, ($agent->missions_count / 3) * 100)
                ];
            })
            ->toArray();
    }
}
