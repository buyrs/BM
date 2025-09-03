<?php

namespace App\Http\Controllers;

use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\Notification;
use App\Models\User;
use App\Models\IncidentReport;
use App\Services\NotificationService;
use App\Services\IncidentDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OpsController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('role:ops|admin');
        $this->notificationService = $notificationService;
    }

    /**
     * Display the ops dashboard with metrics and notifications.
     */
    public function dashboard(Request $request)
    {
        $opsUserId = Auth::id();

        // Get comprehensive metrics
        $metrics = $this->getDashboardMetrics();

        // Get kanban data
        $kanbanData = $this->getKanbanData($request);

        // Get pending notifications
        $pendingNotifications = Notification::forRecipient($opsUserId)
            ->pending()
            ->with('bailMobilite')
            ->latest()
            ->limit(10)
            ->get();

        // Get missions requiring validation
        $missionsForValidation = Mission::whereHas('bailMobilite')
            ->where('ops_assigned_by', $opsUserId)
            ->where('status', 'completed')
            ->whereDoesntHave('checklist', function ($query) {
                $query->where('status', 'validated');
            })
            ->with(['bailMobilite', 'agent', 'checklist'])
            ->get();

        // Get bail mobilités ending soon (within 10 days)
        $endingSoon = BailMobilite::inProgress()
            ->endingWithinDays(10)
            ->with(['opsUser', 'exitMission.agent'])
            ->get();

        // Get notification statistics
        $notificationStats = $this->notificationService->getNotificationStats(Auth::user());

        // Get performance trends
        $performanceTrends = $this->getPerformanceTrends();

        // Get today's missions for calendar quick view
        $todayMissions = Mission::whereDate('scheduled_at', today())
            ->with(['bailMobilite', 'agent'])
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get()
            ->map(function ($mission) {
                return [
                    'id' => $mission->id,
                    'tenant_name' => $mission->bailMobilite->tenant_name ?? 'Unknown',
                    'type' => $mission->mission_type,
                    'status' => $mission->status,
                    'scheduled_date' => $mission->scheduled_at->format('Y-m-d'),
                    'scheduled_time' => $mission->scheduled_at->format('H:i'),
                    'agent_name' => $mission->agent->name ?? null,
                ];
            });

        return Inertia::render('Ops/Dashboard', [
            'metrics' => $metrics,
            'kanbanData' => $kanbanData,
            'pendingNotifications' => $pendingNotifications,
            'missionsForValidation' => $missionsForValidation,
            'endingSoon' => $endingSoon,
            'notificationStats' => $notificationStats,
            'performanceTrends' => $performanceTrends,
            'todayMissions' => $todayMissions,
        ]);
    }

    /**
     * Get all notifications for the current ops user.
     */
    public function notifications(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 20);
        $status = $request->get('status');
        $type = $request->get('type');

        $query = Notification::forRecipient($user->id)
            ->with(['bailMobilite'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->paginate($perPage);

        return Inertia::render('Ops/Notifications', [
            'notifications' => $notifications,
            'filters' => [
                'status' => $status,
                'type' => $type,
            ],
            'stats' => $this->notificationService->getNotificationStats($user),
        ]);
    }

    /**
     * Mark a notification as handled.
     */
    public function markNotificationAsHandled(Notification $notification)
    {
        // Ensure the notification belongs to the current user
        if ($notification->recipient_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $this->notificationService->markNotificationAsHandled($notification);

        return response()->json(['message' => 'Notification marked as handled']);
    }

    /**
     * Get pending notifications for the current user (API endpoint).
     */
    public function getPendingNotifications(Request $request)
    {
        $user = Auth::user();
        $since = $request->get('since');
        
        $query = Notification::forRecipient($user->id)
            ->pending()
            ->with(['bailMobilite'])
            ->orderBy('created_at', 'desc');
        
        // If 'since' parameter is provided, only get notifications created after that time
        if ($since) {
            $query->where('created_at', '>', $since);
        }
        
        $notifications = $query->get();
        
        // Add computed properties for frontend
        $notifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'message' => $notification->getMessage(),
                'data' => $notification->data,
                'status' => $notification->status,
                'created_at' => $notification->created_at->toISOString(),
                'bail_mobilite' => $notification->bailMobilite ? [
                    'id' => $notification->bailMobilite->id,
                    'tenant_name' => $notification->bailMobilite->tenant_name,
                    'address' => $notification->bailMobilite->address,
                    'status' => $notification->bailMobilite->status,
                ] : null,
                'priority' => $this->getNotificationPriority($notification->type),
                'requires_action' => $this->notificationRequiresAction($notification->type),
            ];
        });

        return response()->json([
            'notifications' => $notifications,
            'count' => $notifications->count(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Handle quick actions from notifications.
     */
    public function handleNotificationAction(Request $request, Notification $notification)
    {
        // Ensure the notification belongs to the current user
        if ($notification->recipient_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $action = $request->get('action');
        $result = ['success' => false, 'message' => 'Action non reconnue'];

        try {
            switch ($action) {
                case 'assign_exit':
                    $result = $this->handleAssignExitAction($notification);
                    break;
                    
                case 'validate_checklist':
                    $result = $this->handleValidateChecklistAction($notification);
                    break;
                    
                case 'handle_incident':
                    $result = $this->handleIncidentAction($notification);
                    break;
                    
                case 'view_bail_mobilite':
                    $result = $this->handleViewBailMobiliteAction($notification);
                    break;
                    
                case 'view_mission':
                    $result = $this->handleViewMissionAction($notification);
                    break;
                    
                default:
                    $result = ['success' => false, 'message' => 'Action non supportée'];
            }
            
            // Mark notification as handled if action was successful
            if ($result['success'] && !isset($result['keep_notification'])) {
                $this->notificationService->markNotificationAsHandled($notification);
                $result['remove_from_list'] = true;
            }
            
        } catch (\Exception $e) {
            Log::error("Failed to handle notification action", [
                'notification_id' => $notification->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            
            $result = [
                'success' => false,
                'message' => 'Erreur lors de l\'exécution de l\'action'
            ];
        }

        return response()->json($result);
    }

    /**
     * Handle assign exit action.
     */
    protected function handleAssignExitAction(Notification $notification): array
    {
        $bailMobilite = $notification->bailMobilite;
        if (!$bailMobilite) {
            return ['success' => false, 'message' => 'Bail Mobilité non trouvé'];
        }

        // Redirect to bail mobilité page to assign exit mission
        return [
            'success' => true,
            'message' => 'Redirection vers l\'assignation de sortie',
            'redirect_url' => route('ops.bail-mobilites.show', $bailMobilite->id) . '?action=assign_exit',
            'keep_notification' => true
        ];
    }

    /**
     * Handle validate checklist action.
     */
    protected function handleValidateChecklistAction(Notification $notification): array
    {
        $missionId = $notification->data['mission_id'] ?? null;
        if (!$missionId) {
            return ['success' => false, 'message' => 'Mission non trouvée'];
        }

        $mission = Mission::find($missionId);
        if (!$mission || !$mission->checklist) {
            return ['success' => false, 'message' => 'Checklist non trouvée'];
        }

        // Redirect to checklist validation page
        return [
            'success' => true,
            'message' => 'Redirection vers la validation de checklist',
            'redirect_url' => route('checklists.review', $mission->checklist->id),
            'keep_notification' => true
        ];
    }

    /**
     * Handle incident action.
     */
    protected function handleIncidentAction(Notification $notification): array
    {
        $bailMobilite = $notification->bailMobilite;
        if (!$bailMobilite) {
            return ['success' => false, 'message' => 'Bail Mobilité non trouvé'];
        }

        // Redirect to bail mobilité page with incident tab
        return [
            'success' => true,
            'message' => 'Redirection vers la gestion d\'incident',
            'redirect_url' => route('ops.bail-mobilites.show', $bailMobilite->id) . '?tab=incidents',
            'keep_notification' => true
        ];
    }

    /**
     * Handle view bail mobilité action.
     */
    protected function handleViewBailMobiliteAction(Notification $notification): array
    {
        $bailMobilite = $notification->bailMobilite;
        if (!$bailMobilite) {
            return ['success' => false, 'message' => 'Bail Mobilité non trouvé'];
        }

        return [
            'success' => true,
            'message' => 'Redirection vers le Bail Mobilité',
            'redirect_url' => route('ops.bail-mobilites.show', $bailMobilite->id),
            'keep_notification' => true
        ];
    }

    /**
     * Handle view mission action.
     */
    protected function handleViewMissionAction(Notification $notification): array
    {
        $missionId = $notification->data['mission_id'] ?? null;
        if (!$missionId) {
            return ['success' => false, 'message' => 'Mission non trouvée'];
        }

        $mission = Mission::find($missionId);
        if (!$mission) {
            return ['success' => false, 'message' => 'Mission non trouvée'];
        }

        return [
            'success' => true,
            'message' => 'Redirection vers la mission',
            'redirect_url' => route('ops.missions.show', $mission->id),
            'keep_notification' => true
        ];
    }

    /**
     * Get notification priority level.
     */
    protected function getNotificationPriority(string $type): string
    {
        $priorities = [
            'incident_alert' => 'critical',
            'exit_reminder' => 'high',
            'checklist_validation' => 'medium',
            'mission_completed' => 'medium',
            'mission_assigned' => 'low',
            'calendar_update' => 'low'
        ];
        
        return $priorities[$type] ?? 'low';
    }

    /**
     * Check if notification requires immediate action.
     */
    protected function notificationRequiresAction(string $type): bool
    {
        $actionRequired = [
            'incident_alert' => true,
            'exit_reminder' => true,
            'checklist_validation' => true,
            'mission_completed' => true,
            'mission_assigned' => false,
            'calendar_update' => false
        ];
        
        return $actionRequired[$type] ?? false;
    }
    {
        // Ensure the notification belongs to the current user
        if ($notification->recipient_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $action = $request->get('action');

        switch ($action) {
            case 'view_bail_mobilite':
                $this->notificationService->markNotificationAsHandled($notification);
                return redirect()->route('ops.bail-mobilites.show', $notification->bail_mobilite_id);

            case 'validate_checklist':
                $missionId = $notification->data['mission_id'] ?? null;
                if ($missionId) {
                    $this->notificationService->markNotificationAsHandled($notification);
                    return redirect()->route('ops.missions.validate', $missionId);
                }
                break;

            case 'assign_exit':
                $this->notificationService->markNotificationAsHandled($notification);
                return redirect()->route('ops.bail-mobilites.show', $notification->bail_mobilite_id)
                    ->with('action', 'assign_exit');

            case 'handle_incident':
                $this->notificationService->markNotificationAsHandled($notification);
                return redirect()->route('ops.bail-mobilites.show', $notification->bail_mobilite_id)
                    ->with('action', 'handle_incident');

            default:
                return response()->json(['error' => 'Unknown action'], 400);
        }

        return response()->json(['error' => 'Invalid action or missing data'], 400);
    }

    /**
     * Get comprehensive dashboard metrics.
     */
    private function getDashboardMetrics(): array
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        // Basic statistics
        $stats = [
            'assigned' => BailMobilite::assigned()->count(),
            'in_progress' => BailMobilite::inProgress()->count(),
            'completed' => BailMobilite::completed()->count(),
            'incident' => BailMobilite::incident()->count(),
            'total' => BailMobilite::count(),
        ];

        // Monthly comparisons
        $currentMonthStats = [
            'created' => BailMobilite::where('created_at', '>=', $currentMonth)->count(),
            'completed' => BailMobilite::completed()->where('updated_at', '>=', $currentMonth)->count(),
            'incidents' => BailMobilite::incident()->where('updated_at', '>=', $currentMonth)->count(),
        ];

        $lastMonthStats = [
            'created' => BailMobilite::whereBetween('created_at', [$lastMonth, $currentMonth])->count(),
            'completed' => BailMobilite::completed()->whereBetween('updated_at', [$lastMonth, $currentMonth])->count(),
            'incidents' => BailMobilite::incident()->whereBetween('updated_at', [$lastMonth, $currentMonth])->count(),
        ];

        // Performance metrics - Calculate average duration in days
        $completedBailMobilites = BailMobilite::completed()
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->get(['start_date', 'end_date']);
        
        $averageDuration = 0;
        if ($completedBailMobilites->count() > 0) {
            $totalDays = $completedBailMobilites->sum(function ($bm) {
                return Carbon::parse($bm->start_date)->diffInDays(Carbon::parse($bm->end_date));
            });
            $averageDuration = $totalDays / $completedBailMobilites->count();
        }

        $incidentRate = $stats['total'] > 0 ? ($stats['incident'] / $stats['total']) * 100 : 0;

        // Checker performance
        $checkerPerformance = DB::table('missions')
            ->join('agents', 'missions.agent_id', '=', 'agents.id')
            ->join('users', 'agents.user_id', '=', 'users.id')
            ->whereNotNull('missions.bail_mobilite_id')
            ->where('missions.status', 'completed')
            ->where('missions.created_at', '>=', $currentMonth)
            ->select('users.name', DB::raw('COUNT(*) as missions_completed'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('missions_completed')
            ->limit(5)
            ->get();

        // Incident statistics
        $incidentStats = [
            'total_open' => IncidentReport::open()->count(),
            'critical_open' => IncidentReport::critical()->open()->count(),
            'high_open' => IncidentReport::high()->open()->count(),
            'detected_today' => IncidentReport::detectedToday()->count(),
            'detected_this_week' => IncidentReport::detectedThisWeek()->count(),
            'resolved_this_month' => IncidentReport::resolved()->where('resolved_at', '>=', $currentMonth)->count(),
        ];

        return [
            'basic' => $stats,
            'current_month' => $currentMonthStats,
            'last_month' => $lastMonthStats,
            'average_duration' => round($averageDuration, 1),
            'incident_rate' => round($incidentRate, 2),
            'checker_performance' => $checkerPerformance,
            'incidents' => $incidentStats,
        ];
    }

    /**
     * Get kanban data for bail mobilités.
     */
    public function getKanbanData(Request $request)
    {
        $filters = $this->getFilters($request);
        
        $query = BailMobilite::with([
            'opsUser:id,name',
            'entryMission.agent.user:id,name',
            'exitMission.agent.user:id,name',
            'entryMission.checklist:id,mission_id,status',
            'exitMission.checklist:id,mission_id,status'
        ]);

        // Apply filters
        $query = $this->applyFilters($query, $filters);

        $bailMobilites = $query->get();

        // Group by status for kanban columns
        $kanbanData = [
            'assigned' => $bailMobilites->where('status', 'assigned')->values(),
            'in_progress' => $bailMobilites->where('status', 'in_progress')->values(),
            'completed' => $bailMobilites->where('status', 'completed')->values(),
            'incident' => $bailMobilites->where('status', 'incident')->values(),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'kanbanData' => $kanbanData,
                'filters' => $filters,
                'total' => $bailMobilites->count(),
            ]);
        }

        return Inertia::render('Ops/Dashboard', [
            'kanbanData' => $kanbanData,
            'filters' => $filters,
        ]);
    }

    /**
     * Get filtered bail mobilités with search and filters.
     */
    public function getBailMobilites(Request $request)
    {
        $filters = $this->getFilters($request);
        $perPage = $request->get('per_page', 20);

        $query = BailMobilite::with([
            'opsUser:id,name',
            'entryMission.agent.user:id,name',
            'exitMission.agent.user:id,name',
            'entryMission.checklist:id,mission_id,status',
            'exitMission.checklist:id,mission_id,status'
        ]);

        // Apply filters
        $query = $this->applyFilters($query, $filters);

        // Apply search
        if ($filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('tenant_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('address', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('tenant_email', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Apply sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $bailMobilites = $query->paginate($perPage);

        return response()->json([
            'bail_mobilites' => $bailMobilites,
            'filters' => $filters,
        ]);
    }

    /**
     * Export bail mobilités data.
     */
    public function exportData(Request $request)
    {
        $format = $request->get('format', 'csv');
        $filters = $this->getFilters($request);

        $query = BailMobilite::with([
            'opsUser:id,name',
            'entryMission.agent.user:id,name',
            'exitMission.agent.user:id,name',
            'entryMission.checklist:id,mission_id,status',
            'exitMission.checklist:id,mission_id,status'
        ]);

        $query = $this->applyFilters($query, $filters);
        $bailMobilites = $query->get();

        if ($format === 'csv') {
            return $this->exportToCsv($bailMobilites);
        } elseif ($format === 'json') {
            return response()->json([
                'data' => $bailMobilites,
                'exported_at' => now()->toISOString(),
                'filters_applied' => $filters,
            ]);
        }

        return response()->json(['error' => 'Unsupported format'], 400);
    }

    /**
     * Get performance trends data.
     */
    private function getPerformanceTrends(): array
    {
        $last6Months = collect();
        
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $monthData = [
                'month' => $month->format('M Y'),
                'created' => BailMobilite::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
                'completed' => BailMobilite::completed()->whereBetween('updated_at', [$startOfMonth, $endOfMonth])->count(),
                'incidents' => BailMobilite::incident()->whereBetween('updated_at', [$startOfMonth, $endOfMonth])->count(),
            ];

            $last6Months->push($monthData);
        }

        // Weekly trends for current month
        $weeklyTrends = collect();
        $currentMonth = now()->startOfMonth();
        
        for ($week = 0; $week < 4; $week++) {
            $startOfWeek = $currentMonth->copy()->addWeeks($week);
            $endOfWeek = $startOfWeek->copy()->addWeek();

            if ($endOfWeek->gt(now())) {
                $endOfWeek = now();
            }

            $weekData = [
                'week' => 'Week ' . ($week + 1),
                'created' => BailMobilite::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count(),
                'completed' => BailMobilite::completed()->whereBetween('updated_at', [$startOfWeek, $endOfWeek])->count(),
                'incidents' => BailMobilite::incident()->whereBetween('updated_at', [$startOfWeek, $endOfWeek])->count(),
            ];

            $weeklyTrends->push($weekData);
        }

        return [
            'monthly' => $last6Months,
            'weekly' => $weeklyTrends,
        ];
    }

    /**
     * Get filters from request.
     */
    private function getFilters(Request $request): array
    {
        return [
            'status' => $request->get('status'),
            'ops_user_id' => $request->get('ops_user_id'),
            'checker_id' => $request->get('checker_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
            'incident_only' => $request->boolean('incident_only'),
            'ending_soon' => $request->boolean('ending_soon'),
        ];
    }

    /**
     * Apply filters to query.
     */
    private function applyFilters($query, array $filters)
    {
        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['ops_user_id']) {
            $query->where('ops_user_id', $filters['ops_user_id']);
        }

        if ($filters['checker_id']) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('entryMission', function ($subQ) use ($filters) {
                    $subQ->where('agent_id', $filters['checker_id']);
                })->orWhereHas('exitMission', function ($subQ) use ($filters) {
                    $subQ->where('agent_id', $filters['checker_id']);
                });
            });
        }

        if ($filters['date_from']) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->where('end_date', '<=', $filters['date_to']);
        }

        if ($filters['incident_only']) {
            $query->where('status', 'incident');
        }

        if ($filters['ending_soon']) {
            $query->inProgress()->endingWithinDays(10);
        }

        return $query;
    }

    /**
     * Export data to CSV format.
     */
    private function exportToCsv($bailMobilites): StreamedResponse
    {
        $filename = 'bail_mobilites_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return new StreamedResponse(function () use ($bailMobilites) {
            $handle = fopen('php://output', 'w');

            // CSV headers
            fputcsv($handle, [
                'ID',
                'Tenant Name',
                'Address',
                'Start Date',
                'End Date',
                'Status',
                'Ops User',
                'Entry Checker',
                'Exit Checker',
                'Entry Status',
                'Exit Status',
                'Created At',
                'Updated At',
            ]);

            // CSV data
            foreach ($bailMobilites as $bm) {
                fputcsv($handle, [
                    $bm->id,
                    $bm->tenant_name,
                    $bm->address,
                    $bm->start_date->format('Y-m-d'),
                    $bm->end_date->format('Y-m-d'),
                    $bm->status,
                    $bm->opsUser->name ?? '',
                    $bm->entryMission->agent->user->name ?? '',
                    $bm->exitMission->agent->user->name ?? '',
                    $bm->entryMission->checklist->status ?? 'pending',
                    $bm->exitMission->checklist->status ?? 'pending',
                    $bm->created_at->format('Y-m-d H:i:s'),
                    $bm->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get available ops users for filters.
     */
    public function getOpsUsers()
    {
        $opsUsers = User::role('ops')->select('id', 'name')->get();
        return response()->json($opsUsers);
    }

    /**
     * Get available checkers for filters.
     */
    public function getCheckers()
    {
        $checkers = User::role('checker')
            ->join('agents', 'users.id', '=', 'agents.user_id')
            ->select('agents.id', 'users.name')
            ->get();
        return response()->json($checkers);
    }
}