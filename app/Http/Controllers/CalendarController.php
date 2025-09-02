<?php

namespace App\Http\Controllers;

use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\User;
use App\Services\CalendarService;
use App\Services\NotificationService;
use App\Services\CalendarEventService;
use App\Http\Resources\MissionCalendarResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class CalendarController extends Controller
{
    protected CalendarService $calendarService;
    protected NotificationService $notificationService;
    protected CalendarEventService $calendarEventService;

    public function __construct(CalendarService $calendarService, NotificationService $notificationService, CalendarEventService $calendarEventService)
    {
        $this->calendarService = $calendarService;
        $this->notificationService = $notificationService;
        $this->calendarEventService = $calendarEventService;
        // Middleware is handled at route level via ops.access
        
        // Additional permission checks for specific calendar operations
        $this->middleware('can:view_calendar')->only(['index', 'getMissions', 'getMissionDetails']);
        $this->middleware('can:create_missions')->only(['createMission']);
        $this->middleware('can:assign_missions_to_checkers')->only(['assignMissionToChecker', 'bulkUpdateMissions']);
        $this->middleware('can:manage_calendar_events')->only(['updateMission', 'updateMissionStatus']);
        $this->middleware('can:delete_missions')->only(['deleteMission']);
    }

    /**
     * Display the calendar page with initial data.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'view' => 'nullable|string|in:month,week,day',
            'status' => 'nullable|string|in:unassigned,assigned,in_progress,completed,cancelled',
            'checker_id' => 'nullable|integer|exists:users,id',
            'mission_type' => 'nullable|string|in:entry,exit',
            'date_range' => 'nullable|string|in:today,tomorrow,this_week,next_week,this_month,overdue',
            'search' => 'nullable|string|max:255',
        ]);

        $currentDate = $validated['date'] ?? now()->format('Y-m-d');
        $viewMode = $validated['view'] ?? 'month';
        $parsedDate = Carbon::parse($currentDate);

        // Calculate date range based on view mode
        switch ($viewMode) {
            case 'month':
                $startDate = $parsedDate->copy()->startOfMonth()->startOfWeek();
                $endDate = $parsedDate->copy()->endOfMonth()->endOfWeek();
                break;
            case 'week':
                $startDate = $parsedDate->copy()->startOfWeek();
                $endDate = $parsedDate->copy()->endOfWeek();
                break;
            case 'day':
                $startDate = $parsedDate->copy()->startOfDay();
                $endDate = $parsedDate->copy()->endOfDay();
                break;
            default:
                $startDate = $parsedDate->copy()->startOfMonth();
                $endDate = $parsedDate->copy()->endOfMonth();
        }

        // Get filters from request
        $filters = $request->only(['status', 'checker_id', 'mission_type', 'date_range', 'search']);

        // Get missions for the calculated date range with filters
        $missions = $this->calendarService->getMissionsForDateRange($startDate, $endDate, $filters);
        $formattedMissions = $this->calendarService->formatMissionsForCalendar($missions);

        // Get available checkers for assignment
        $checkers = User::role('checker')
            ->with('agent')
            ->select('id', 'name', 'email')
            ->get();

        return Inertia::render('Ops/Calendar', [
            'missions' => $formattedMissions,
            'currentDate' => $currentDate,
            'viewMode' => $viewMode,
            'checkers' => $checkers,
            'initialFilters' => $filters,
            'dateRange' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Get missions for a specific date range with filtering.
     */
    public function getMissions(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'status' => 'nullable|string|in:unassigned,assigned,in_progress,completed,cancelled',
                'checker_id' => 'nullable|integer|exists:users,id',
                'mission_type' => 'nullable|string|in:entry,exit',
                'date_range' => 'nullable|string|in:today,tomorrow,this_week,next_week,this_month,overdue',
                'search' => 'nullable|string|max:255',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $filters = $request->only(['status', 'checker_id', 'mission_type', 'date_range', 'search']);

            $missions = $this->calendarService->getMissionsForDateRange($startDate, $endDate, $filters);
            $formattedMissions = $this->calendarService->formatMissionsForCalendar($missions);

            return response()->json([
                'success' => true,
                'missions' => $formattedMissions,
                'total' => $missions->count(),
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
                'applied_filters' => array_filter($filters, function($value) {
                    return $value !== null && $value !== '';
                }),
                'cache_key' => md5($startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '_' . serialize($filters)),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Calendar getMissions error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load missions',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Create a new BM mission from the calendar.
     */
    public function createMission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'address' => 'required|string|max:255',
            'tenant_name' => 'required|string|max:255',
            'tenant_phone' => 'nullable|string|max:20',
            'tenant_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'entry_scheduled_time' => 'nullable|date_format:H:i',
            'exit_scheduled_time' => 'nullable|date_format:H:i',
            'entry_checker_id' => 'nullable|integer|exists:users,id',
            'exit_checker_id' => 'nullable|integer|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $bailMobilite = $this->calendarService->createBailMobiliteMission($validated);

            // Handle mission creation events
            if ($bailMobilite->entryMission) {
                $this->calendarEventService->handleMissionCreated($bailMobilite->entryMission);
            }
            if ($bailMobilite->exitMission) {
                $this->calendarEventService->handleMissionCreated($bailMobilite->exitMission);
            }

            DB::commit();

            // Return the created missions formatted for calendar
            $missions = collect([$bailMobilite->entryMission, $bailMobilite->exitMission])
                ->filter();
            
            // Load relationships for each mission
            foreach ($missions as $mission) {
                $mission->load(['agent', 'bailMobilite', 'checklist']);
            }
            
            $formattedMissions = $this->calendarService->formatMissionsForCalendar($missions);

            return response()->json([
                'success' => true,
                'message' => 'Bail Mobilité créé avec succès',
                'bail_mobilite' => $bailMobilite->load(['entryMission', 'exitMission']),
                'missions' => $formattedMissions,
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du Bail Mobilité',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update mission details from calendar.
     */
    public function updateMission(Request $request, $mission): JsonResponse
    {
        // Handle both route model binding and manual ID passing
        if (!$mission instanceof Mission) {
            $mission = Mission::findOrFail($mission);
        }
        
        $validated = $request->validate([
            'scheduled_at' => 'nullable|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'agent_id' => 'nullable|integer|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        // Convert time format to include seconds for database storage
        if (isset($validated['scheduled_time'])) {
            $validated['scheduled_time'] = $validated['scheduled_time'] . ':00';
        }

        try {
            // Store original values for change tracking
            $originalAgentId = $mission->agent_id;
            $originalStatus = $mission->status;
            
            // If agent is assigned, add status to validated data
            if (isset($validated['agent_id']) && $validated['agent_id']) {
                $validated['status'] = 'assigned';
            }
            
            // Update mission with all validated data at once
            $updateResult = $mission->update($validated);
            \Log::info('CalendarController updateMission - Update result:', ['result' => $updateResult]);

            // Handle mission lifecycle events
            if (isset($validated['agent_id']) && $validated['agent_id'] !== $originalAgentId) {
                $previousAgent = $originalAgentId ? User::find($originalAgentId) : null;
                $this->calendarEventService->handleMissionAssigned($mission, $previousAgent);
            } elseif (isset($validated['status']) && $validated['status'] !== $originalStatus) {
                $this->calendarEventService->handleMissionStatusChanged($mission, $originalStatus, $validated['status']);
            } elseif (isset($validated['scheduled_at']) && $validated['scheduled_at'] !== $mission->getOriginal('scheduled_at')) {
                $oldDate = Carbon::parse($mission->getOriginal('scheduled_at'));
                $newDate = Carbon::parse($validated['scheduled_at']);
                $this->calendarEventService->handleMissionRescheduled($mission, $oldDate, $newDate);
            }

            // Reload the mission with relationships
            $mission->refresh();
            $mission->load(['agent', 'bailMobilite', 'checklist', 'opsAssignedBy']);
            
            $formattedMissions = $this->calendarService->formatMissionsForCalendar(collect([$mission]));
            
            if (empty($formattedMissions)) {
                throw new \Exception('Failed to format mission for calendar display');
            }
            
            $formattedMission = $formattedMissions[0];

            return response()->json([
                'success' => true,
                'message' => 'Mission mise à jour avec succès',
                'mission' => $formattedMission,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la mission',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get detailed mission information for modal display.
     */
    public function getMissionDetails(Mission $mission): JsonResponse
    {
        $mission->load([
            'agent',
            'bailMobilite.opsUser',
            'checklist.items.photos',
            'bailMobilite.signatures.contractTemplate',
            'bailMobilite.notifications' => function ($query) {
                $query->latest()->limit(5);
            }
        ]);

        return response()->json([
            'mission' => MissionCalendarResource::make($mission),
        ]);
    }

    /**
     * Get available time slots for a specific date.
     */
    public function getAvailableTimeSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'checker_id' => 'nullable|integer|exists:users,id',
        ]);

        $date = Carbon::parse($validated['date']);
        $checkerId = $validated['checker_id'] ?? null;

        $availableSlots = $this->calendarService->getAvailableTimeSlots($date, $checkerId);

        return response()->json([
            'date' => $date->format('Y-m-d'),
            'available_slots' => $availableSlots,
        ]);
    }

    /**
     * Detect scheduling conflicts for a mission.
     */
    public function detectConflicts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'checker_id' => 'nullable|integer|exists:users,id',
            'mission_id' => 'nullable|integer|exists:missions,id',
        ]);

        $date = Carbon::parse($validated['date']);
        $time = $validated['time'];
        $checkerId = $validated['checker_id'] ?? null;
        $excludeMissionId = $validated['mission_id'] ?? null;

        $conflicts = $this->calendarService->detectSchedulingConflicts($date, $time, $checkerId, $excludeMissionId);

        return response()->json([
            'has_conflicts' => !empty($conflicts),
            'conflicts' => $conflicts,
        ]);
    }

    /**
     * Update mission status with validation.
     */
    public function updateMissionStatus(Request $request, Mission $mission): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:unassigned,assigned,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        try {
            // Validate status transition
            $this->validateStatusTransition($mission, $validated['status']);

            $oldStatus = $mission->status;
            $mission->update($validated);
            
            // Handle status change event
            $this->calendarEventService->handleMissionStatusChanged($mission, $oldStatus, $validated['status']);
            
            $mission->refresh();
            $mission->load(['agent', 'bailMobilite', 'checklist', 'opsAssignedBy']);

            $formattedMissions = $this->calendarService->formatMissionsForCalendar(collect([$mission]));
            $formattedMission = $formattedMissions[0] ?? null;

            return response()->json([
                'success' => true,
                'message' => 'Mission status updated successfully',
                'mission' => $formattedMission,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Assign mission to checker.
     */
    public function assignMissionToChecker(Request $request, Mission $mission): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|integer|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        try {
            // Verify the user is a checker
            $checker = User::findOrFail($validated['agent_id']);
            if (!$checker->hasRole('checker')) {
                throw new \Exception('Selected user is not a checker');
            }

            $previousAgent = $mission->agent;
            
            $mission->update([
                'agent_id' => $validated['agent_id'],
                'status' => 'assigned',
                'ops_assigned_by' => Auth::id(),
                'notes' => $validated['notes'] ?? $mission->notes,
            ]);

            // Handle assignment event
            $this->calendarEventService->handleMissionAssigned($mission, $previousAgent);

            $mission->refresh();
            $mission->load(['agent', 'bailMobilite', 'checklist', 'opsAssignedBy']);

            $formattedMissions = $this->calendarService->formatMissionsForCalendar(collect([$mission]));
            $formattedMission = $formattedMissions[0] ?? null;

            return response()->json([
                'success' => true,
                'message' => 'Mission assigned successfully',
                'mission' => $formattedMission,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete mission with validation.
     */
    public function deleteMission(Mission $mission): JsonResponse
    {
        try {
            // Check if mission can be deleted
            if ($mission->status === 'in_progress') {
                throw new \Exception('Cannot delete mission that is in progress');
            }

            if ($mission->status === 'completed') {
                throw new \Exception('Cannot delete completed mission');
            }

            $missionId = $mission->id;
            
            // Handle deletion event before deleting
            $this->calendarEventService->handleMissionDeleted($mission);
            
            $mission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mission deleted successfully',
                'deleted_mission_id' => $missionId,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Bulk update missions.
     */
    public function bulkUpdateMissions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mission_ids' => 'required|array|min:1',
            'mission_ids.*' => 'integer|exists:missions,id',
            'action' => 'required|string|in:assign,update_status,delete',
            'agent_id' => 'nullable|integer|exists:users,id',
            'status' => 'nullable|string|in:unassigned,assigned,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $missions = Mission::whereIn('id', $validated['mission_ids'])->get();
            $updatedMissions = collect();
            $errors = collect();

            foreach ($missions as $mission) {
                try {
                    switch ($validated['action']) {
                        case 'assign':
                            if (!$validated['agent_id']) {
                                throw new \Exception('Agent ID is required for assignment');
                            }
                            
                            $checker = User::findOrFail($validated['agent_id']);
                            if (!$checker->hasRole('checker')) {
                                throw new \Exception('Selected user is not a checker');
                            }

                            $mission->update([
                                'agent_id' => $validated['agent_id'],
                                'status' => 'assigned',
                                'ops_assigned_by' => Auth::id(),
                                'notes' => $validated['notes'] ?? $mission->notes,
                            ]);
                            break;

                        case 'update_status':
                            if (!$validated['status']) {
                                throw new \Exception('Status is required for status update');
                            }
                            
                            $this->validateStatusTransition($mission, $validated['status']);
                            $mission->update([
                                'status' => $validated['status'],
                                'notes' => $validated['notes'] ?? $mission->notes,
                            ]);
                            break;

                        case 'delete':
                            if ($mission->status === 'in_progress') {
                                throw new \Exception('Cannot delete mission that is in progress');
                            }
                            if ($mission->status === 'completed') {
                                throw new \Exception('Cannot delete completed mission');
                            }
                            $mission->delete();
                            continue 2; // Skip adding to updated missions
                    }

                    $mission->refresh();
                    $mission->load(['agent', 'bailMobilite', 'checklist', 'opsAssignedBy']);
                    $updatedMissions->push($mission);

                } catch (\Exception $e) {
                    $errors->push([
                        'mission_id' => $mission->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            $formattedMissions = $this->calendarService->formatMissionsForCalendar($updatedMissions);

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    'Bulk operation completed. %d missions updated, %d errors.',
                    $updatedMissions->count(),
                    $errors->count()
                ),
                'missions' => $formattedMissions,
                'errors' => $errors->toArray(),
                'deleted_mission_ids' => $validated['action'] === 'delete' ? $validated['mission_ids'] : [],
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Health check endpoint for connectivity testing.
     */
    public function health(): JsonResponse
    {
        try {
            // Simple database connectivity test
            \DB::connection()->getPdo();
            
            return response()->json([
                'status' => 'ok',
                'timestamp' => now()->toISOString(),
                'service' => 'calendar'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service unavailable',
                'timestamp' => now()->toISOString(),
                'service' => 'calendar'
            ], 503);
        }
    }

    /**
     * Validate status transition.
     */
    private function validateStatusTransition(Mission $mission, string $newStatus): void
    {
        $currentStatus = $mission->status;
        
        // Define valid transitions
        $validTransitions = [
            'unassigned' => ['assigned', 'cancelled'],
            'assigned' => ['in_progress', 'unassigned', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [], // Completed missions cannot change status
            'cancelled' => ['unassigned'], // Cancelled missions can be reactivated
        ];

        if (!isset($validTransitions[$currentStatus])) {
            throw new \Exception("Invalid current status: {$currentStatus}");
        }

        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            throw new \Exception("Cannot transition from {$currentStatus} to {$newStatus}");
        }

        // Additional validation for specific transitions
        if ($newStatus === 'assigned' && !$mission->agent_id) {
            throw new \Exception('Cannot set status to assigned without assigning a checker');
        }

        if ($newStatus === 'in_progress' && !$mission->agent_id) {
            throw new \Exception('Cannot start mission without assigned checker');
        }
    }
}