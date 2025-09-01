<?php

namespace App\Http\Controllers;

use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class BailMobiliteController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:ops|admin');
    }

    /**
     * Display a listing of bail mobilités with kanban view.
     */
    public function index(Request $request)
    {
        $query = BailMobilite::with(['opsUser', 'entryMission.agent', 'exitMission.agent']);

        // Filter by status if provided
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by checker if provided
        if ($request->has('checker_id') && $request->checker_id) {
            $query->whereHas('entryMission', function ($q) use ($request) {
                $q->where('agent_id', $request->checker_id);
            })->orWhereHas('exitMission', function ($q) use ($request) {
                $q->where('agent_id', $request->checker_id);
            });
        }

        // Filter by date range if provided
        if ($request->has('date_from') && $request->date_from) {
            $query->where('start_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->where('end_date', '<=', $request->date_to);
        }

        $bailMobilites = $query->latest()->get();

        // Group by status for kanban view
        $kanbanData = [
            'assigned' => $bailMobilites->where('status', 'assigned')->values(),
            'in_progress' => $bailMobilites->where('status', 'in_progress')->values(),
            'completed' => $bailMobilites->where('status', 'completed')->values(),
            'incident' => $bailMobilites->where('status', 'incident')->values(),
        ];

        // Get available checkers for assignment
        $checkers = User::role('checker')->get();

        return Inertia::render('BailMobilites/Index', [
            'kanbanData' => $kanbanData,
            'checkers' => $checkers,
            'filters' => $request->only(['status', 'checker_id', 'date_from', 'date_to'])
        ]);
    }

    /**
     * Show the form for creating a new bail mobilité.
     */
    public function create()
    {
        return Inertia::render('BailMobilites/Create');
    }

    /**
     * Store a newly created bail mobilité with automatic mission generation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'address' => 'required|string|max:255',
            'tenant_name' => 'required|string|max:255',
            'tenant_phone' => 'nullable|string|max:20',
            'tenant_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Create the bail mobilité
            $bailMobilite = BailMobilite::create([
                ...$validated,
                'status' => 'assigned',
                'ops_user_id' => Auth::id(),
            ]);

            // Create entry mission
            $entryMission = Mission::create([
                'type' => 'checkin',
                'mission_type' => 'entry',
                'scheduled_at' => $validated['start_date'],
                'address' => $validated['address'],
                'tenant_name' => $validated['tenant_name'],
                'tenant_phone' => $validated['tenant_phone'],
                'tenant_email' => $validated['tenant_email'],
                'notes' => $validated['notes'] . ' - Mission d\'entrée pour Bail Mobilité',
                'status' => 'unassigned',
                'bail_mobilite_id' => $bailMobilite->id,
                'ops_assigned_by' => Auth::id(),
            ]);

            // Create exit mission
            $exitMission = Mission::create([
                'type' => 'checkout',
                'mission_type' => 'exit',
                'scheduled_at' => $validated['end_date'],
                'address' => $validated['address'],
                'tenant_name' => $validated['tenant_name'],
                'tenant_phone' => $validated['tenant_phone'],
                'tenant_email' => $validated['tenant_email'],
                'notes' => $validated['notes'] . ' - Mission de sortie pour Bail Mobilité',
                'status' => 'unassigned',
                'bail_mobilite_id' => $bailMobilite->id,
                'ops_assigned_by' => Auth::id(),
            ]);

            // Update bail mobilité with mission IDs
            $bailMobilite->update([
                'entry_mission_id' => $entryMission->id,
                'exit_mission_id' => $exitMission->id,
            ]);

            DB::commit();

            return redirect()->route('bail-mobilites.index')
                ->with('success', 'Bail Mobilité créé avec succès avec les missions d\'entrée et de sortie.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur lors de la création du Bail Mobilité: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified bail mobilité with full details and history.
     */
    public function show(BailMobilite $bailMobilite)
    {
        $bailMobilite->load([
            'opsUser',
            'entryMission.agent',
            'exitMission.agent',
            'entryMission.checklist.items.photos',
            'exitMission.checklist.items.photos',
            'signatures.contractTemplate',
            'notifications'
        ]);

        return Inertia::render('BailMobilites/Show', [
            'bailMobilite' => $bailMobilite
        ]);
    }

    /**
     * Show the form for editing the specified bail mobilité.
     */
    public function edit(BailMobilite $bailMobilite)
    {
        return Inertia::render('BailMobilites/Edit', [
            'bailMobilite' => $bailMobilite
        ]);
    }

    /**
     * Update the specified bail mobilité and adjust missions accordingly.
     */
    public function update(Request $request, BailMobilite $bailMobilite)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'address' => 'required|string|max:255',
            'tenant_name' => 'required|string|max:255',
            'tenant_phone' => 'nullable|string|max:20',
            'tenant_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $originalEndDate = $bailMobilite->end_date;
            
            // Update bail mobilité
            $bailMobilite->update($validated);

            // Update entry mission
            if ($bailMobilite->entryMission) {
                $bailMobilite->entryMission->update([
                    'scheduled_at' => $validated['start_date'],
                    'address' => $validated['address'],
                    'tenant_name' => $validated['tenant_name'],
                    'tenant_phone' => $validated['tenant_phone'],
                    'tenant_email' => $validated['tenant_email'],
                    'notes' => $validated['notes'] . ' - Mission d\'entrée pour Bail Mobilité',
                ]);
            }

            // Update exit mission
            if ($bailMobilite->exitMission) {
                $bailMobilite->exitMission->update([
                    'scheduled_at' => $validated['end_date'],
                    'address' => $validated['address'],
                    'tenant_name' => $validated['tenant_name'],
                    'tenant_phone' => $validated['tenant_phone'],
                    'tenant_email' => $validated['tenant_email'],
                    'notes' => $validated['notes'] . ' - Mission de sortie pour Bail Mobilité',
                ]);
            }

            // If end date changed, cancel existing notifications and reschedule
            if ($originalEndDate->format('Y-m-d') !== $bailMobilite->end_date->format('Y-m-d')) {
                $this->rescheduleExitNotifications($bailMobilite);
            }

            DB::commit();

            return redirect()->route('bail-mobilites.index')
                ->with('success', 'Bail Mobilité mis à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
        }
    }

    /**
     * Assign a checker to the entry mission.
     */
    public function assignEntry(Request $request, BailMobilite $bailMobilite)
    {
        $validated = $request->validate([
            'checker_id' => 'required|exists:users,id',
            'scheduled_time' => 'nullable|date_format:H:i',
        ]);

        if (!$bailMobilite->entryMission) {
            return back()->withErrors(['error' => 'Mission d\'entrée non trouvée.']);
        }

        $bailMobilite->entryMission->update([
            'agent_id' => $validated['checker_id'],
            'status' => 'assigned',
            'scheduled_time' => $validated['scheduled_time'] ?? null,
        ]);

        return back()->with('success', 'Checker assigné à la mission d\'entrée avec succès.');
    }

    /**
     * Assign a checker to the exit mission.
     */
    public function assignExit(Request $request, BailMobilite $bailMobilite)
    {
        $validated = $request->validate([
            'checker_id' => 'required|exists:users,id',
            'scheduled_time' => 'required|date_format:H:i',
        ]);

        if (!$bailMobilite->exitMission) {
            return back()->withErrors(['error' => 'Mission de sortie non trouvée.']);
        }

        $bailMobilite->exitMission->update([
            'agent_id' => $validated['checker_id'],
            'status' => 'assigned',
            'scheduled_time' => $validated['scheduled_time'],
        ]);

        return back()->with('success', 'Checker assigné à la mission de sortie avec succès.');
    }

    /**
     * Validate entry checklist and transition to in_progress status.
     */
    public function validateEntry(Request $request, BailMobilite $bailMobilite)
    {
        $validated = $request->validate([
            'approved' => 'required|boolean',
            'comments' => 'nullable|string',
        ]);

        if (!$bailMobilite->entryMission || !$bailMobilite->entryMission->checklist) {
            return back()->withErrors(['error' => 'Checklist d\'entrée non trouvée.']);
        }

        DB::beginTransaction();
        try {
            if ($validated['approved']) {
                // Approve entry and transition to in_progress
                $bailMobilite->update(['status' => 'in_progress']);
                
                // Schedule exit reminder notification (10 days before end)
                $this->scheduleExitReminder($bailMobilite);
                
                $message = 'Entrée validée avec succès. Bail Mobilité passé en cours.';
            } else {
                // Reject entry - send back to checker
                $bailMobilite->entryMission->update(['status' => 'assigned']);
                
                // Add rejection comment to mission notes
                if ($validated['comments']) {
                    $bailMobilite->entryMission->update([
                        'notes' => $bailMobilite->entryMission->notes . "\n\nCommentaires de validation: " . $validated['comments']
                    ]);
                }
                
                $message = 'Entrée rejetée. Mission renvoyée au checker pour correction.';
            }

            DB::commit();
            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur lors de la validation: ' . $e->getMessage()]);
        }
    }

    /**
     * Validate exit checklist and transition to completed or incident status.
     */
    public function validateExit(Request $request, BailMobilite $bailMobilite)
    {
        $validated = $request->validate([
            'approved' => 'required|boolean',
            'keys_returned' => 'required|boolean',
            'comments' => 'nullable|string',
        ]);

        if (!$bailMobilite->exitMission || !$bailMobilite->exitMission->checklist) {
            return back()->withErrors(['error' => 'Checklist de sortie non trouvée.']);
        }

        DB::beginTransaction();
        try {
            $hasSignature = $bailMobilite->exitSignature && $bailMobilite->exitSignature->tenant_signature;
            
            if ($validated['approved'] && $validated['keys_returned'] && $hasSignature) {
                // All conditions met - mark as completed
                $bailMobilite->update(['status' => 'completed']);
                $message = 'Sortie validée avec succès. Bail Mobilité terminé.';
            } else {
                // Issues detected - mark as incident
                $bailMobilite->update(['status' => 'incident']);
                
                $issues = [];
                if (!$validated['approved']) $issues[] = 'Checklist non approuvée';
                if (!$validated['keys_returned']) $issues[] = 'Clés non remises';
                if (!$hasSignature) $issues[] = 'Signature manquante';
                
                $incidentNote = 'Incident détecté: ' . implode(', ', $issues);
                if ($validated['comments']) {
                    $incidentNote .= "\nCommentaires: " . $validated['comments'];
                }
                
                // Create incident notification
                Notification::create([
                    'type' => 'INCIDENT_ALERT',
                    'recipient_id' => Auth::id(),
                    'bail_mobilite_id' => $bailMobilite->id,
                    'scheduled_at' => now(),
                    'data' => json_encode([
                        'message' => $incidentNote,
                        'issues' => $issues
                    ])
                ]);
                
                $message = 'Incident détecté. Bail Mobilité marqué en incident.';
            }

            DB::commit();
            return back()->with($validated['approved'] && $validated['keys_returned'] && $hasSignature ? 'success' : 'warning', $message);

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur lors de la validation: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle incident resolution and corrective actions.
     */
    public function handleIncident(Request $request, BailMobilite $bailMobilite)
    {
        $validated = $request->validate([
            'action' => 'required|in:resolve,create_task,reassign',
            'description' => 'required|string',
            'new_checker_id' => 'nullable|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            switch ($validated['action']) {
                case 'resolve':
                    $bailMobilite->update(['status' => 'completed']);
                    $message = 'Incident résolu. Bail Mobilité marqué comme terminé.';
                    break;
                    
                case 'create_task':
                    // Create corrective task notification
                    Notification::create([
                        'type' => 'CORRECTIVE_TASK',
                        'recipient_id' => Auth::id(),
                        'bail_mobilite_id' => $bailMobilite->id,
                        'scheduled_at' => now(),
                        'data' => json_encode([
                            'task_description' => $validated['description'],
                            'created_by' => Auth::user()->name
                        ])
                    ]);
                    $message = 'Tâche corrective créée avec succès.';
                    break;
                    
                case 'reassign':
                    if (!$validated['new_checker_id']) {
                        return back()->withErrors(['new_checker_id' => 'Checker requis pour la réassignation.']);
                    }
                    
                    // Reassign the problematic mission
                    if ($bailMobilite->exitMission && $bailMobilite->exitMission->status !== 'completed') {
                        $bailMobilite->exitMission->update([
                            'agent_id' => $validated['new_checker_id'],
                            'status' => 'assigned'
                        ]);
                    }
                    
                    $bailMobilite->update(['status' => 'in_progress']);
                    $message = 'Mission réassignée avec succès.';
                    break;
            }

            DB::commit();
            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur lors du traitement de l\'incident: ' . $e->getMessage()]);
        }
    }

    /**
     * Get available checkers for assignment.
     */
    public function getAvailableCheckers(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $time = $request->get('time');

        $query = User::role('checker')->with('agent');

        // Filter out checkers who already have missions at the same time
        if ($time) {
            $query->whereDoesntHave('assignedMissions', function ($q) use ($date, $time) {
                $q->whereDate('scheduled_at', $date)
                  ->whereTime('scheduled_time', $time);
            });
        }

        $checkers = $query->get()->map(function ($checker) {
            return [
                'id' => $checker->id,
                'name' => $checker->name,
                'email' => $checker->email,
                'is_available' => true, // Could add more complex availability logic
                'current_missions_count' => $checker->assignedMissions()
                    ->whereIn('status', ['assigned', 'in_progress'])
                    ->count()
            ];
        });

        return response()->json($checkers);
    }

    /**
     * Schedule exit reminder notification 10 days before end date.
     */
    private function scheduleExitReminder(BailMobilite $bailMobilite)
    {
        $reminderDate = $bailMobilite->end_date->subDays(10);
        
        // Only schedule if reminder date is in the future
        if ($reminderDate->isFuture()) {
            Notification::create([
                'type' => 'EXIT_REMINDER',
                'recipient_id' => $bailMobilite->ops_user_id,
                'bail_mobilite_id' => $bailMobilite->id,
                'scheduled_at' => $reminderDate->startOfDay(),
                'status' => 'pending',
                'data' => json_encode([
                    'message' => 'Rappel: Bail Mobilité se termine dans 10 jours',
                    'end_date' => $bailMobilite->end_date->toDateString(),
                    'tenant_name' => $bailMobilite->tenant_name,
                    'address' => $bailMobilite->address
                ])
            ]);
        }
    }

    /**
     * Reschedule exit notifications when end date changes.
     */
    private function rescheduleExitNotifications(BailMobilite $bailMobilite)
    {
        // Cancel existing pending notifications
        $bailMobilite->notifications()
            ->where('type', 'EXIT_REMINDER')
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        // Schedule new notification if bail mobilité is in progress
        if ($bailMobilite->status === 'in_progress') {
            $this->scheduleExitReminder($bailMobilite);
        }
    }
}