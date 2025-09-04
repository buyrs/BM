<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\User;
use App\Services\MissionService;
use App\Services\NotificationService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MissionStatusController extends Controller
{
    public function __construct(
        private MissionService $missionService,
        private NotificationService $notificationService,
        private AuditService $auditService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display status tracking dashboard
     */
    public function dashboard(Request $request): View
    {
        $user = Auth::user();
        $filters = $request->only(['status', 'agent', 'date_from', 'date_to', 'priority']);

        // Get missions based on user role
        if ($user->hasRole('super-admin')) {
            $missions = Mission::with(['agent', 'bailMobilite', 'checklist'])
                ->when($filters['status'], fn($q) => $q->where('status', $filters['status']))
                ->when($filters['agent'], fn($q) => $q->where('agent_id', $filters['agent']))
                ->when($filters['date_from'], fn($q) => $q->where('scheduled_at', '>=', $filters['date_from']))
                ->when($filters['date_to'], fn($q) => $q->where('scheduled_at', '<=', $filters['date_to']))
                ->when($filters['priority'], fn($q) => $q->where('priority', $filters['priority']))
                ->latest()
                ->paginate(20);
        } elseif ($user->hasRole('ops')) {
            $missions = Mission::with(['agent', 'bailMobilite', 'checklist'])
                ->where('ops_assigned_by', $user->id)
                ->when($filters['status'], fn($q) => $q->where('status', $filters['status']))
                ->when($filters['agent'], fn($q) => $q->where('agent_id', $filters['agent']))
                ->when($filters['date_from'], fn($q) => $q->where('scheduled_at', '>=', $filters['date_from']))
                ->when($filters['date_to'], fn($q) => $q->where('scheduled_at', '<=', $filters['date_to']))
                ->when($filters['priority'], fn($q) => $q->where('priority', $filters['priority']))
                ->latest()
                ->paginate(20);
        } else {
            // Checker role
            $missions = Mission::with(['bailMobilite', 'checklist'])
                ->where('agent_id', $user->id)
                ->when($filters['status'], fn($q) => $q->where('status', $filters['status']))
                ->when($filters['date_from'], fn($q) => $q->where('scheduled_at', '>=', $filters['date_from']))
                ->when($filters['date_to'], fn($q) => $q->where('scheduled_at', '<=', $filters['date_to']))
                ->when($filters['priority'], fn($q) => $q->where('priority', $filters['priority']))
                ->latest()
                ->paginate(20);
        }

        // Get status statistics
        $statusStats = $this->getStatusStatistics($user);
        
        // Get progress metrics
        $progressMetrics = $this->getProgressMetrics($user);
        
        // Get recent status changes
        $recentChanges = $this->getRecentStatusChanges($user);

        // Get filter options
        $agents = User::role('checker')->get(['id', 'name']);
        $statuses = [
            'unassigned' => 'Non assigné',
            'assigned' => 'Assigné',
            'in_progress' => 'En cours',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé'
        ];
        $priorities = [
            'low' => 'Faible',
            'normal' => 'Normale',
            'high' => 'Élevée',
            'urgent' => 'Urgente'
        ];

        return view('pages.missions.status-dashboard', compact(
            'missions',
            'statusStats',
            'progressMetrics',
            'recentChanges',
            'agents',
            'statuses',
            'priorities',
            'filters'
        ));
    }

    /**
     * Update mission status with validation
     */
    public function updateStatus(Request $request, Mission $mission): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:assigned,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:500',
            'completion_notes' => 'nullable|string|max:1000',
            'completion_photos' => 'nullable|array|max:5',
            'completion_photos.*' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $mission->status;
            $user = Auth::user();

            // Validate status transition
            $validationResult = $this->validateStatusTransition($mission, $validated['status'], $user);
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message']
                ], 422);
            }

            // Update mission status
            $updateData = [
                'status' => $validated['status'],
                'status_updated_at' => now(),
                'status_updated_by' => $user->id
            ];

            // Add completion data if mission is completed
            if ($validated['status'] === 'completed') {
                $updateData['completed_at'] = now();
                $updateData['completion_notes'] = $validated['completion_notes'];
                $updateData['completion_photos'] = $this->handleCompletionPhotos($validated['completion_photos'] ?? []);
            }

            // Add cancellation data if mission is cancelled
            if ($validated['status'] === 'cancelled') {
                $updateData['cancelled_at'] = now();
                $updateData['cancellation_reason'] = $validated['notes'];
            }

            $mission->update($updateData);

            // Log status change
            $this->auditService->logStatusChange($mission, $oldStatus, $validated['status'], $user, $validated['notes']);

            // Send notifications
            $this->sendStatusChangeNotifications($mission, $oldStatus, $validated['status'], $user);

            // Update related entities
            $this->updateRelatedEntities($mission, $validated['status']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'mission' => [
                    'id' => $mission->id,
                    'status' => $mission->status,
                    'status_updated_at' => $mission->status_updated_at->format('d/m/Y H:i'),
                    'updated_by' => $user->name
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update mission status', [
                'mission_id' => $mission->id,
                'new_status' => $validated['status'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut'
            ], 500);
        }
    }

    /**
     * Get real-time status updates
     */
    public function getStatusUpdates(Request $request): JsonResponse
    {
        $user = Auth::user();
        $lastUpdate = $request->get('last_update', now()->subMinutes(5));

        try {
            // Get missions with recent status changes
            $query = Mission::with(['agent', 'bailMobilite'])
                ->where('status_updated_at', '>', $lastUpdate);

            // Apply role-based filtering
            if ($user->hasRole('checker')) {
                $query->where('agent_id', $user->id);
            } elseif ($user->hasRole('ops')) {
                $query->where('ops_assigned_by', $user->id);
            }

            $updatedMissions = $query->get();

            // Get status statistics
            $statusStats = $this->getStatusStatistics($user);

            return response()->json([
                'success' => true,
                'updated_missions' => $updatedMissions->map(function($mission) {
                    return [
                        'id' => $mission->id,
                        'status' => $mission->status,
                        'status_updated_at' => $mission->status_updated_at->format('d/m/Y H:i'),
                        'address' => $mission->address,
                        'agent_name' => $mission->agent?->name,
                        'is_bail_mobilite' => $mission->isBailMobiliteMission()
                    ];
                }),
                'status_stats' => $statusStats,
                'last_update' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get status updates', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des mises à jour'
            ], 500);
        }
    }

    /**
     * Get mission progress metrics
     */
    public function getProgressMetrics(User $user = null): array
    {
        $user = $user ?? Auth::user();
        $query = Mission::query();

        // Apply role-based filtering
        if ($user->hasRole('checker')) {
            $query->where('agent_id', $user->id);
        } elseif ($user->hasRole('ops')) {
            $query->where('ops_assigned_by', $user->id);
        }

        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        return [
            'today' => [
                'total' => (clone $query)->whereDate('scheduled_at', $today)->count(),
                'completed' => (clone $query)->whereDate('scheduled_at', $today)->where('status', 'completed')->count(),
                'in_progress' => (clone $query)->whereDate('scheduled_at', $today)->where('status', 'in_progress')->count(),
                'assigned' => (clone $query)->whereDate('scheduled_at', $today)->where('status', 'assigned')->count(),
                'unassigned' => (clone $query)->whereDate('scheduled_at', $today)->where('status', 'unassigned')->count(),
                'cancelled' => (clone $query)->whereDate('scheduled_at', $today)->where('status', 'cancelled')->count()
            ],
            'this_week' => [
                'total' => (clone $query)->where('scheduled_at', '>=', $thisWeek)->count(),
                'completed' => (clone $query)->where('scheduled_at', '>=', $thisWeek)->where('status', 'completed')->count(),
                'completion_rate' => $this->calculateCompletionRate($query, $thisWeek)
            ],
            'this_month' => [
                'total' => (clone $query)->where('scheduled_at', '>=', $thisMonth)->count(),
                'completed' => (clone $query)->where('scheduled_at', '>=', $thisMonth)->where('status', 'completed')->count(),
                'completion_rate' => $this->calculateCompletionRate($query, $thisMonth)
            ],
            'average_completion_time' => $this->getAverageCompletionTime($query),
            'overdue_missions' => $this->getOverdueMissions($query),
            'upcoming_deadlines' => $this->getUpcomingDeadlines($query)
        ];
    }

    /**
     * Get status transition history for a mission
     */
    public function getStatusHistory(Mission $mission): JsonResponse
    {
        try {
            $history = $this->auditService->getStatusHistory($mission);

            return response()->json([
                'success' => true,
                'history' => $history
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get status history', [
                'mission_id' => $mission->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique'
            ], 500);
        }
    }

    /**
     * Validate status transition
     */
    private function validateStatusTransition(Mission $mission, string $newStatus, User $user): array
    {
        $currentStatus = $mission->status;
        $userRole = $user->getRoleNames()->first();

        // Define valid transitions
        $validTransitions = [
            'unassigned' => ['assigned', 'cancelled'],
            'assigned' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [], // No transitions from completed
            'cancelled' => ['assigned'] // Can reassign cancelled missions
        ];

        // Check if transition is valid
        if (!in_array($newStatus, $validTransitions[$currentStatus] ?? [])) {
            return [
                'valid' => false,
                'message' => "Transition de '{$currentStatus}' vers '{$newStatus}' non autorisée"
            ];
        }

        // Role-based validation
        if ($userRole === 'checker' && $mission->agent_id !== $user->id) {
            return [
                'valid' => false,
                'message' => 'Vous ne pouvez modifier que vos propres missions'
            ];
        }

        // Additional validations
        if ($newStatus === 'completed' && !$mission->checklist) {
            return [
                'valid' => false,
                'message' => 'Une checklist doit être créée avant de marquer la mission comme terminée'
            ];
        }

        if ($newStatus === 'in_progress' && $mission->status !== 'assigned') {
            return [
                'valid' => false,
                'message' => 'Seules les missions assignées peuvent être mises en cours'
            ];
        }

        return ['valid' => true, 'message' => 'Transition valide'];
    }

    /**
     * Send status change notifications
     */
    private function sendStatusChangeNotifications(Mission $mission, string $oldStatus, string $newStatus, User $user): void
    {
        try {
            // Notify agent if status changed by ops
            if ($mission->agent && $user->hasRole('ops')) {
                $this->notificationService->sendStatusChangeNotification($mission, $mission->agent, $oldStatus, $newStatus);
            }

            // Notify ops if status changed by agent
            if ($user->hasRole('checker') && $mission->opsAssignedBy) {
                $this->notificationService->sendStatusChangeNotification($mission, $mission->opsAssignedBy, $oldStatus, $newStatus);
            }

            // Notify super-admin for critical status changes
            if (in_array($newStatus, ['cancelled', 'completed'])) {
                $superAdmins = User::role('super-admin')->get();
                foreach ($superAdmins as $superAdmin) {
                    $this->notificationService->sendStatusChangeNotification($mission, $superAdmin, $oldStatus, $newStatus);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to send status change notifications', [
                'mission_id' => $mission->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update related entities based on status change
     */
    private function updateRelatedEntities(Mission $mission, string $newStatus): void
    {
        if ($newStatus === 'completed' && $mission->bailMobilite) {
            // Update Bail Mobilité status if mission is completed
            $mission->bailMobilite->update([
                'last_mission_completed_at' => now(),
                'status' => $mission->mission_type === 'exit' ? 'completed' : 'in_progress'
            ]);
        }

        if ($newStatus === 'cancelled' && $mission->checklist) {
            // Mark checklist as cancelled if mission is cancelled
            $mission->checklist->update(['status' => 'cancelled']);
        }
    }

    /**
     * Handle completion photos upload
     */
    private function handleCompletionPhotos(array $photos): array
    {
        $uploadedPhotos = [];
        
        foreach ($photos as $photo) {
            $path = $photo->store('mission-completion-photos', 'public');
            $uploadedPhotos[] = [
                'path' => $path,
                'original_name' => $photo->getClientOriginalName(),
                'size' => $photo->getSize(),
                'uploaded_at' => now()
            ];
        }

        return $uploadedPhotos;
    }

    /**
     * Get status statistics
     */
    private function getStatusStatistics(User $user): array
    {
        $query = Mission::query();

        // Apply role-based filtering
        if ($user->hasRole('checker')) {
            $query->where('agent_id', $user->id);
        } elseif ($user->hasRole('ops')) {
            $query->where('ops_assigned_by', $user->id);
        }

        $total = $query->count();
        
        if ($total === 0) {
            return [
                'unassigned' => 0,
                'assigned' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'completion_rate' => 0
            ];
        }

        $statusCounts = $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $completed = $statusCounts['completed'] ?? 0;
        $completionRate = round(($completed / $total) * 100, 1);

        return [
            'unassigned' => $statusCounts['unassigned'] ?? 0,
            'assigned' => $statusCounts['assigned'] ?? 0,
            'in_progress' => $statusCounts['in_progress'] ?? 0,
            'completed' => $completed,
            'cancelled' => $statusCounts['cancelled'] ?? 0,
            'completion_rate' => $completionRate
        ];
    }

    /**
     * Get recent status changes
     */
    private function getRecentStatusChanges(User $user): array
    {
        $query = Mission::with(['agent'])
            ->whereNotNull('status_updated_at')
            ->orderBy('status_updated_at', 'desc')
            ->limit(10);

        // Apply role-based filtering
        if ($user->hasRole('checker')) {
            $query->where('agent_id', $user->id);
        } elseif ($user->hasRole('ops')) {
            $query->where('ops_assigned_by', $user->id);
        }

        return $query->get()->map(function($mission) {
            return [
                'id' => $mission->id,
                'address' => $mission->address,
                'status' => $mission->status,
                'agent_name' => $mission->agent?->name,
                'updated_at' => $mission->status_updated_at->format('d/m/Y H:i'),
                'updated_by' => $mission->statusUpdatedBy?->name ?? 'System'
            ];
        })->toArray();
    }

    /**
     * Calculate completion rate
     */
    private function calculateCompletionRate($query, $startDate): float
    {
        $total = (clone $query)->where('scheduled_at', '>=', $startDate)->count();
        $completed = (clone $query)->where('scheduled_at', '>=', $startDate)->where('status', 'completed')->count();
        
        return $total > 0 ? round(($completed / $total) * 100, 1) : 0;
    }

    /**
     * Get average completion time
     */
    private function getAverageCompletionTime($query): float
    {
        $avgMinutes = (clone $query)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('scheduled_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, scheduled_at, completed_at)) as avg_minutes')
            ->value('avg_minutes');

        return round($avgMinutes ?? 0, 1);
    }

    /**
     * Get overdue missions
     */
    private function getOverdueMissions($query): int
    {
        return (clone $query)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->where('scheduled_at', '<', now())
            ->count();
    }

    /**
     * Get upcoming deadlines
     */
    private function getUpcomingDeadlines($query): int
    {
        return (clone $query)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', now()->addHours(2))
            ->count();
    }
}
