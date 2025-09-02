<?php

namespace App\Http\Controllers;

use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\User;
use App\Services\CalendarService;
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

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
        // Middleware is handled at route level via ops.access
    }

    /**
     * Display the calendar page with initial data.
     */
    public function index(Request $request): Response
    {
        $currentDate = $request->get('date', now()->format('Y-m-d'));
        $startDate = Carbon::parse($currentDate)->startOfMonth();
        $endDate = Carbon::parse($currentDate)->endOfMonth();

        // Get filters from request
        $filters = $request->only(['status', 'checker_id', 'mission_type', 'date_range', 'search']);

        // Get missions for the current month with filters
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
            'checkers' => $checkers,
            'initialFilters' => $filters,
        ]);
    }

    /**
     * Get missions for a specific date range with filtering.
     */
    public function getMissions(Request $request): JsonResponse
    {
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
            'missions' => $formattedMissions,
            'total' => $missions->count(),
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'applied_filters' => array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            }),
        ]);
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
            
            // If agent is assigned, add status to validated data
            if (isset($validated['agent_id']) && $validated['agent_id']) {
                $validated['status'] = 'assigned';
            }
            
            // Update mission with all validated data at once
            $updateResult = $mission->update($validated);
            \Log::info('CalendarController updateMission - Update result:', ['result' => $updateResult]);

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
            'agent.user',
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
}