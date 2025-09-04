<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\User;
use App\Models\BailMobilite;
use App\Services\MissionService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BladeMissionController extends Controller
{
    public function __construct(
        private MissionService $missionService,
        private NotificationService $notificationService
    ) {
        $this->middleware('role:super-admin|ops|checker')->only(['index', 'show']);
        $this->middleware('role:super-admin|ops')->only(['create', 'store', 'edit', 'update', 'destroy']);
        $this->middleware('role:checker')->only(['getAssignedMissions', 'getCompletedMissions']);
        $this->middleware('role:ops')->only(['assignToChecker', 'validateChecklist']);
    }

    /**
     * Display a listing of missions
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $filters = $request->only(['status', 'agent', 'date_from', 'date_to', 'search']);

        // Get missions based on user role
        if ($user->hasRole('super-admin')) {
            $missions = Mission::with(['agent', 'bailMobilite', 'checklist'])
                ->when($filters['status'], fn($q) => $q->where('status', $filters['status']))
                ->when($filters['agent'], fn($q) => $q->where('agent_id', $filters['agent']))
                ->when($filters['date_from'], fn($q) => $q->where('scheduled_at', '>=', $filters['date_from']))
                ->when($filters['date_to'], fn($q) => $q->where('scheduled_at', '<=', $filters['date_to']))
                ->when($filters['search'], fn($q) => $q->where('address', 'like', '%' . $filters['search'] . '%'))
                ->latest()
                ->paginate(20);
        } elseif ($user->hasRole('ops')) {
            $missions = Mission::with(['agent', 'bailMobilite', 'checklist'])
                ->where('ops_assigned_by', $user->id)
                ->when($filters['status'], fn($q) => $q->where('status', $filters['status']))
                ->when($filters['agent'], fn($q) => $q->where('agent_id', $filters['agent']))
                ->when($filters['date_from'], fn($q) => $q->where('scheduled_at', '>=', $filters['date_from']))
                ->when($filters['date_to'], fn($q) => $q->where('scheduled_at', '<=', $filters['date_to']))
                ->when($filters['search'], fn($q) => $q->where('address', 'like', '%' . $filters['search'] . '%'))
                ->latest()
                ->paginate(20);
        } else {
            // Checker role
            $missions = Mission::with(['bailMobilite', 'checklist'])
                ->where('agent_id', $user->id)
                ->when($filters['status'], fn($q) => $q->where('status', $filters['status']))
                ->when($filters['date_from'], fn($q) => $q->where('scheduled_at', '>=', $filters['date_from']))
                ->when($filters['date_to'], fn($q) => $q->where('scheduled_at', '<=', $filters['date_to']))
                ->when($filters['search'], fn($q) => $q->where('address', 'like', '%' . $filters['search'] . '%'))
                ->latest()
                ->paginate(20);
        }

        // Get filter options
        $agents = User::role('checker')->get(['id', 'name']);
        $statuses = [
            'unassigned' => 'Non assigné',
            'assigned' => 'Assigné',
            'in_progress' => 'En cours',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé'
        ];

        return view('pages.missions.index', compact('missions', 'agents', 'statuses', 'filters'));
    }

    /**
     * Show the form for creating a new mission
     */
    public function create(): View
    {
        $agents = User::role('checker')->get(['id', 'name']);
        $bailMobilites = BailMobilite::where('status', 'in_progress')->get(['id', 'tenant_name', 'address']);
        
        $missionTypes = [
            'entry' => 'Entrée',
            'exit' => 'Sortie'
        ];

        return view('pages.missions.create', compact('agents', 'bailMobilites', 'missionTypes'));
    }

    /**
     * Store a newly created mission
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'property_address' => 'required|string|max:255',
            'mission_type' => 'required|in:entry,exit',
            'scheduled_at' => 'required|date|after:now',
            'scheduled_time' => 'nullable|date_format:H:i',
            'tenant_name' => 'required|string|max:255',
            'tenant_phone' => 'nullable|string|max:20',
            'tenant_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'agent_id' => 'nullable|exists:users,id',
            'bail_mobilite_id' => 'nullable|exists:bail_mobilites,id',
            'auto_assign' => 'nullable|boolean'
        ]);

        try {
            $mission = $this->missionService->createMission($validated, Auth::user());

            return redirect()
                ->route('missions.show', $mission)
                ->with('success', 'Mission créée avec succès.');

        } catch (\Exception $e) {
            Log::error('Failed to create mission', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'data' => $validated
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Erreur lors de la création de la mission. Veuillez réessayer.']);
        }
    }

    /**
     * Display the specified mission
     */
    public function show(Mission $mission): View
    {
        $user = Auth::user();

        // Check permissions
        if (!$user->hasRole('super-admin') && 
            !$user->hasRole('ops') && 
            $mission->agent_id !== $user->id) {
            abort(403, 'Accès non autorisé à cette mission.');
        }

        $mission->load([
            'agent', 
            'bailMobilite.signatures.contractTemplate',
            'checklist.items.photos',
            'opsAssignedBy'
        ]);

        // Get contract templates if this is a bail mobilité mission
        $contractTemplates = [];
        if ($mission->isBailMobiliteMission()) {
            $contractTemplates = \App\Models\ContractTemplate::active()
                ->where('type', $mission->mission_type)
                ->whereNotNull('admin_signature')
                ->get();
        }

        return view('pages.missions.show', compact('mission', 'contractTemplates'));
    }

    /**
     * Show the form for editing the specified mission
     */
    public function edit(Mission $mission): View
    {
        $agents = User::role('checker')->get(['id', 'name']);
        $bailMobilites = BailMobilite::where('status', 'in_progress')->get(['id', 'tenant_name', 'address']);
        
        $missionTypes = [
            'entry' => 'Entrée',
            'exit' => 'Sortie'
        ];

        return view('pages.missions.edit', compact('mission', 'agents', 'bailMobilites', 'missionTypes'));
    }

    /**
     * Update the specified mission
     */
    public function update(Request $request, Mission $mission): RedirectResponse
    {
        $validated = $request->validate([
            'property_address' => 'required|string|max:255',
            'mission_type' => 'required|in:entry,exit',
            'scheduled_at' => 'required|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'tenant_name' => 'required|string|max:255',
            'tenant_phone' => 'nullable|string|max:20',
            'tenant_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'agent_id' => 'nullable|exists:users,id',
            'bail_mobilite_id' => 'nullable|exists:bail_mobilites,id',
            'status' => 'required|in:unassigned,assigned,in_progress,completed,cancelled'
        ]);

        try {
            $this->missionService->updateMission($mission, $validated, Auth::user());

            return redirect()
                ->route('missions.show', $mission)
                ->with('success', 'Mission mise à jour avec succès.');

        } catch (\Exception $e) {
            Log::error('Failed to update mission', [
                'mission_id' => $mission->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Erreur lors de la mise à jour de la mission. Veuillez réessayer.']);
        }
    }

    /**
     * Remove the specified mission
     */
    public function destroy(Mission $mission): RedirectResponse
    {
        try {
            $this->missionService->deleteMission($mission, Auth::user());

            return redirect()
                ->route('missions.index')
                ->with('success', 'Mission supprimée avec succès.');

        } catch (\Exception $e) {
            Log::error('Failed to delete mission', [
                'mission_id' => $mission->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()
                ->withErrors(['error' => 'Erreur lors de la suppression de la mission. Veuillez réessayer.']);
        }
    }

    /**
     * Get assigned missions for checker
     */
    public function getAssignedMissions(Request $request): View
    {
        $user = Auth::user();
        $filters = $request->only(['status', 'date_from', 'date_to']);

        $missions = $this->missionService->getMissionsForUser($user, array_merge($filters, [
            'status' => ['assigned', 'in_progress']
        ]));

        return view('pages.missions.assigned', compact('missions', 'filters'));
    }

    /**
     * Get completed missions for checker
     */
    public function getCompletedMissions(Request $request): View
    {
        $user = Auth::user();
        $filters = $request->only(['date_from', 'date_to']);

        $missions = $this->missionService->getMissionsForUser($user, array_merge($filters, [
            'status' => 'completed'
        ]));

        return view('pages.missions.completed', compact('missions', 'filters'));
    }

    /**
     * Assign mission to checker
     */
    public function assignToChecker(Request $request, Mission $mission): RedirectResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id'
        ]);

        try {
            $checker = User::findOrFail($validated['agent_id']);
            $this->missionService->assignMissionToChecker($mission, $checker, Auth::user());

            return back()
                ->with('success', 'Mission assignée avec succès.');

        } catch (\Exception $e) {
            Log::error('Failed to assign mission', [
                'mission_id' => $mission->id,
                'agent_id' => $validated['agent_id'],
                'error' => $e->getMessage()
            ]);

            return back()
                ->withErrors(['error' => 'Erreur lors de l\'assignation de la mission. Veuillez réessayer.']);
        }
    }

    /**
     * Update mission status
     */
    public function updateStatus(Request $request, Mission $mission): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:assigned,in_progress,completed,cancelled'
        ]);

        try {
            $this->missionService->updateMissionStatus($mission, $validated['status'], Auth::user());

            return back()
                ->with('success', 'Statut de la mission mis à jour avec succès.');

        } catch (\Exception $e) {
            Log::error('Failed to update mission status', [
                'mission_id' => $mission->id,
                'status' => $validated['status'],
                'error' => $e->getMessage()
            ]);

            return back()
                ->withErrors(['error' => 'Erreur lors de la mise à jour du statut. Veuillez réessayer.']);
        }
    }

    /**
     * Display mission calendar view
     */
    public function calendar(Request $request): View
    {
        $user = Auth::user();
        $filters = $request->only(['agent', 'status', 'month', 'year']);

        // Default to current month if not specified
        $month = $filters['month'] ?? now()->month;
        $year = $filters['year'] ?? now()->year;

        // Get missions for the specified month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = Mission::with(['agent', 'bailMobilite'])
            ->whereBetween('scheduled_at', [$startDate, $endDate]);

        // Apply filters
        if (!empty($filters['agent'])) {
            $query->where('agent_id', $filters['agent']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Role-based filtering
        if ($user->hasRole('checker')) {
            $query->where('agent_id', $user->id);
        } elseif ($user->hasRole('ops')) {
            $query->where('ops_assigned_by', $user->id);
        }

        $missions = $query->get();

        // Get filter options
        $agents = User::role('checker')->get(['id', 'name']);
        $statuses = [
            'unassigned' => 'Non assigné',
            'assigned' => 'Assigné',
            'in_progress' => 'En cours',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé'
        ];

        // Prepare calendar data
        $calendarData = $this->prepareCalendarData($missions, $startDate, $endDate);

        return view('pages.missions.calendar', compact(
            'missions', 
            'agents', 
            'statuses', 
            'filters', 
            'calendarData',
            'month',
            'year'
        ));
    }

    /**
     * Get mission statistics for dashboard
     */
    public function getStatistics(): \Illuminate\Http\JsonResponse
    {
        try {
            $stats = $this->missionService->getMissionStatistics(Auth::user());

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Failed to get mission statistics', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json(['error' => 'Erreur lors du chargement des statistiques'], 500);
        }
    }

    /**
     * Bulk update missions
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mission_ids' => 'required|array|min:1',
            'mission_ids.*' => 'exists:missions,id',
            'action' => 'required|in:assign,status_update,delete',
            'agent_id' => 'required_if:action,assign|exists:users,id',
            'status' => 'required_if:action,status_update|in:assigned,in_progress,completed,cancelled'
        ]);

        try {
            $missionIds = $validated['mission_ids'];
            $updateData = [];

            switch ($validated['action']) {
                case 'assign':
                    $updateData = ['agent_id' => $validated['agent_id'], 'status' => 'assigned'];
                    break;
                case 'status_update':
                    $updateData = ['status' => $validated['status']];
                    break;
                case 'delete':
                    // Handle deletion separately
                    Mission::whereIn('id', $missionIds)->delete();
                    return back()->with('success', count($missionIds) . ' missions supprimées avec succès.');
            }

            if (!empty($updateData)) {
                $this->missionService->bulkUpdateMissions($missionIds, $updateData, Auth::user());
            }

            return back()
                ->with('success', count($missionIds) . ' missions mises à jour avec succès.');

        } catch (\Exception $e) {
            Log::error('Failed to bulk update missions', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'data' => $validated
            ]);

            return back()
                ->withErrors(['error' => 'Erreur lors de la mise à jour en lot. Veuillez réessayer.']);
        }
    }

    /**
     * Prepare calendar data for display
     */
    private function prepareCalendarData($missions, $startDate, $endDate): array
    {
        $calendarData = [];
        
        // Initialize calendar grid
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $day = $currentDate->format('Y-m-d');
            $calendarData[$day] = [
                'date' => $currentDate->copy(),
                'missions' => [],
                'isToday' => $currentDate->isToday(),
                'isWeekend' => $currentDate->isWeekend(),
                'isPast' => $currentDate->isPast()
            ];
            $currentDate->addDay();
        }

        // Add missions to calendar
        foreach ($missions as $mission) {
            if ($mission->scheduled_at) {
                $day = $mission->scheduled_at->format('Y-m-d');
                if (isset($calendarData[$day])) {
                    $calendarData[$day]['missions'][] = [
                        'id' => $mission->id,
                        'address' => $mission->address,
                        'tenant_name' => $mission->tenant_name,
                        'agent_name' => $mission->agent?->name ?? 'Non assigné',
                        'status' => $mission->status,
                        'mission_type' => $mission->mission_type,
                        'scheduled_time' => $mission->scheduled_at->format('H:i'),
                        'is_bail_mobilite' => $mission->isBailMobiliteMission(),
                        'url' => route('blade-missions.show', $mission)
                    ];
                }
            }
        }

        return $calendarData;
    }

    /**
     * Update mission schedule via AJAX
     */
    public function updateSchedule(Request $request, Mission $mission): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'scheduled_time' => 'nullable|date_format:H:i'
        ]);

        try {
            $scheduledAt = Carbon::parse($validated['scheduled_at']);
            if ($validated['scheduled_time']) {
                $time = Carbon::parse($validated['scheduled_time']);
                $scheduledAt->setTime($time->hour, $time->minute);
            }

            // Check for conflicts
            $conflicts = $this->checkScheduleConflicts($mission, $scheduledAt);
            if (!empty($conflicts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conflit de planning détecté',
                    'conflicts' => $conflicts
                ], 422);
            }

            $mission->update(['scheduled_at' => $scheduledAt]);

            return response()->json([
                'success' => true,
                'message' => 'Planning mis à jour avec succès',
                'mission' => [
                    'id' => $mission->id,
                    'scheduled_at' => $mission->scheduled_at->format('Y-m-d H:i'),
                    'formatted_date' => $mission->scheduled_at->format('d/m/Y H:i')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update mission schedule', [
                'mission_id' => $mission->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du planning'
            ], 500);
        }
    }

    /**
     * Check for schedule conflicts
     */
    private function checkScheduleConflicts(Mission $mission, Carbon $newScheduledAt): array
    {
        $conflicts = [];

        // Check for other missions at the same time for the same agent
        if ($mission->agent_id) {
            $existingMissions = Mission::where('agent_id', $mission->agent_id)
                ->where('id', '!=', $mission->id)
                ->where('status', '!=', 'cancelled')
                ->whereDate('scheduled_at', $newScheduledAt->format('Y-m-d'))
                ->whereTime('scheduled_at', '>=', $newScheduledAt->subHour()->format('H:i:s'))
                ->whereTime('scheduled_at', '<=', $newScheduledAt->addHour()->format('H:i:s'))
                ->get();

            foreach ($existingMissions as $existingMission) {
                $conflicts[] = [
                    'type' => 'agent_conflict',
                    'message' => "Conflit avec la mission #{$existingMission->id} de {$existingMission->agent->name}",
                    'mission_id' => $existingMission->id,
                    'scheduled_at' => $existingMission->scheduled_at->format('d/m/Y H:i')
                ];
            }
        }

        // Check for same property conflicts
        $propertyConflicts = Mission::where('address', $mission->address)
            ->where('id', '!=', $mission->id)
            ->where('status', '!=', 'cancelled')
            ->whereDate('scheduled_at', $newScheduledAt->format('Y-m-d'))
            ->whereTime('scheduled_at', '>=', $newScheduledAt->subHour()->format('H:i:s'))
            ->whereTime('scheduled_at', '<=', $newScheduledAt->addHour()->format('H:i:s'))
            ->get();

        foreach ($propertyConflicts as $propertyConflict) {
            $conflicts[] = [
                'type' => 'property_conflict',
                'message' => "Conflit avec la mission #{$propertyConflict->id} au même bien",
                'mission_id' => $propertyConflict->id,
                'scheduled_at' => $propertyConflict->scheduled_at->format('d/m/Y H:i')
            ];
        }

        return $conflicts;
    }
}
