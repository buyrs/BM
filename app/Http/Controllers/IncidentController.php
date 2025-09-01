<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use App\Models\CorrectiveAction;
use App\Models\BailMobilite;
use App\Models\User;
use App\Services\IncidentDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class IncidentController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:ops|admin');
    }

    /**
     * Display a listing of incident reports.
     */
    public function index(Request $request)
    {
        $query = IncidentReport::with([
            'bailMobilite',
            'mission',
            'checklist',
            'createdBy',
            'resolvedBy',
            'correctiveActions'
        ]);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by severity
        if ($request->has('severity') && $request->severity !== 'all') {
            $query->where('severity', $request->severity);
        }

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by bail mobilité
        if ($request->has('bail_mobilite_id') && $request->bail_mobilite_id) {
            $query->where('bail_mobilite_id', $request->bail_mobilite_id);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('detected_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('detected_at', '<=', $request->date_to);
        }

        $incidents = $query->orderBy('detected_at', 'desc')->paginate(20);

        // Get filter options
        $bailMobilites = BailMobilite::select('id', 'tenant_name', 'address')->get();
        $users = User::role(['ops', 'admin'])->select('id', 'name')->get();

        return Inertia::render('Incidents/Index', [
            'incidents' => $incidents,
            'bailMobilites' => $bailMobilites,
            'users' => $users,
            'filters' => $request->only(['status', 'severity', 'type', 'bail_mobilite_id', 'date_from', 'date_to']),
            'incidentTypes' => $this->getIncidentTypes(),
            'severityLevels' => $this->getSeverityLevels(),
            'statusOptions' => $this->getStatusOptions()
        ]);
    }

    /**
     * Display the specified incident report with full details.
     */
    public function show(IncidentReport $incident)
    {
        $incident->load([
            'bailMobilite.opsUser',
            'bailMobilite.entryMission.agent',
            'bailMobilite.exitMission.agent',
            'mission.agent',
            'checklist.items.photos',
            'createdBy',
            'resolvedBy',
            'correctiveActions.assignedTo',
            'correctiveActions.createdBy'
        ]);

        // Get available users for assignment
        $users = User::role(['ops', 'admin', 'checker'])->select('id', 'name', 'email')->get();

        return Inertia::render('Incidents/Show', [
            'incident' => $incident,
            'users' => $users
        ]);
    }

    /**
     * Update the status of an incident report.
     */
    public function updateStatus(Request $request, IncidentReport $incident)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
            'resolution_notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $previousStatus = $incident->status;

            if ($validated['status'] === 'resolved' || $validated['status'] === 'closed') {
                $incident->markAsResolved(Auth::user(), $validated['resolution_notes']);
            } elseif ($validated['status'] === 'in_progress') {
                $incident->markAsInProgress(Auth::user());
            } else {
                $incident->update(['status' => $validated['status']]);
            }

            // If incident is resolved and it was the only open incident for the bail mobilité,
            // check if we can change the bail mobilité status back to normal
            if ($validated['status'] === 'resolved' && $incident->bailMobilite) {
                $openIncidentsCount = $incident->bailMobilite->openIncidentReports()->count();
                if ($openIncidentsCount === 0 && $incident->bailMobilite->status === 'incident') {
                    // Determine appropriate status based on mission completion
                    $newStatus = $this->determineBailMobiliteStatus($incident->bailMobilite);
                    $incident->bailMobilite->update(['status' => $newStatus]);
                }
            }

            DB::commit();

            return back()->with('success', "Statut de l'incident mis à jour de '{$previousStatus}' vers '{$validated['status']}'.");

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
        }
    }

    /**
     * Create a corrective action for an incident.
     */
    public function createCorrectiveAction(Request $request, IncidentReport $incident)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date|after:now'
        ]);

        $correctiveAction = CorrectiveAction::create([
            'incident_report_id' => $incident->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'assigned_to' => $validated['assigned_to'],
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'],
            'status' => 'pending',
            'created_by' => Auth::id()
        ]);

        // Mark incident as in progress if it was open
        if ($incident->status === 'open') {
            $incident->markAsInProgress(Auth::user());
        }

        return back()->with('success', 'Tâche corrective créée avec succès.');
    }

    /**
     * Update a corrective action.
     */
    public function updateCorrectiveAction(Request $request, CorrectiveAction $correctiveAction)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'completion_notes' => 'nullable|string'
        ]);

        if ($validated['status'] === 'completed') {
            $correctiveAction->markAsCompleted($validated['completion_notes']);
        } elseif ($validated['status'] === 'cancelled') {
            $correctiveAction->markAsCancelled();
        } else {
            $correctiveAction->update(['status' => $validated['status']]);
        }

        return back()->with('success', 'Tâche corrective mise à jour avec succès.');
    }

    /**
     * Get incident statistics for dashboard.
     */
    public function getStats(IncidentDetectionService $incidentDetectionService)
    {
        $stats = $incidentDetectionService->getIncidentStats();
        
        // Add additional stats
        $stats['corrective_actions_pending'] = CorrectiveAction::pending()->count();
        $stats['corrective_actions_overdue'] = CorrectiveAction::overdue()->count();
        
        return response()->json($stats);
    }

    /**
     * Get incidents for a specific bail mobilité.
     */
    public function getIncidentsForBailMobilite(BailMobilite $bailMobilite)
    {
        $incidents = $bailMobilite->incidentReports()
            ->with(['mission', 'checklist', 'createdBy', 'resolvedBy'])
            ->orderBy('detected_at', 'desc')
            ->get();

        return response()->json($incidents);
    }

    /**
     * Bulk update incident statuses.
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'incident_ids' => 'required|array',
            'incident_ids.*' => 'exists:incident_reports,id',
            'action' => 'required|in:mark_in_progress,mark_resolved,mark_closed',
            'resolution_notes' => 'nullable|string'
        ]);

        $incidents = IncidentReport::whereIn('id', $validated['incident_ids'])->get();
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($incidents as $incident) {
                switch ($validated['action']) {
                    case 'mark_in_progress':
                        if ($incident->status === 'open') {
                            $incident->markAsInProgress(Auth::user());
                            $updatedCount++;
                        }
                        break;
                    case 'mark_resolved':
                        if (in_array($incident->status, ['open', 'in_progress'])) {
                            $incident->markAsResolved(Auth::user(), $validated['resolution_notes']);
                            $updatedCount++;
                        }
                        break;
                    case 'mark_closed':
                        if ($incident->status !== 'closed') {
                            $incident->markAsClosed(Auth::user(), $validated['resolution_notes']);
                            $updatedCount++;
                        }
                        break;
                }
            }

            DB::commit();

            return back()->with('success', "{$updatedCount} incident(s) mis à jour avec succès.");

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour en lot: ' . $e->getMessage()]);
        }
    }

    /**
     * Get incident types for filters.
     */
    private function getIncidentTypes(): array
    {
        return [
            IncidentReport::TYPE_MISSING_CHECKLIST => 'Checklist manquante',
            IncidentReport::TYPE_INCOMPLETE_CHECKLIST => 'Checklist incomplète',
            IncidentReport::TYPE_MISSING_TENANT_SIGNATURE => 'Signature locataire manquante',
            IncidentReport::TYPE_MISSING_REQUIRED_PHOTOS => 'Photos obligatoires manquantes',
            IncidentReport::TYPE_MISSING_CONTRACT_SIGNATURE => 'Signature contrat manquante',
            IncidentReport::TYPE_KEYS_NOT_RETURNED => 'Clés non remises',
            IncidentReport::TYPE_OVERDUE_MISSION => 'Mission en retard',
            IncidentReport::TYPE_VALIDATION_TIMEOUT => 'Délai de validation dépassé'
        ];
    }

    /**
     * Get severity levels for filters.
     */
    private function getSeverityLevels(): array
    {
        return [
            IncidentReport::SEVERITY_LOW => 'Faible',
            IncidentReport::SEVERITY_MEDIUM => 'Moyen',
            IncidentReport::SEVERITY_HIGH => 'Élevé',
            IncidentReport::SEVERITY_CRITICAL => 'Critique'
        ];
    }

    /**
     * Get status options for filters.
     */
    private function getStatusOptions(): array
    {
        return [
            IncidentReport::STATUS_OPEN => 'Ouvert',
            IncidentReport::STATUS_IN_PROGRESS => 'En cours',
            IncidentReport::STATUS_RESOLVED => 'Résolu',
            IncidentReport::STATUS_CLOSED => 'Fermé'
        ];
    }

    /**
     * Determine appropriate bail mobilité status based on mission completion.
     */
    private function determineBailMobiliteStatus(BailMobilite $bailMobilite): string
    {
        $entryCompleted = $bailMobilite->entryMission && $bailMobilite->entryMission->status === 'completed';
        $exitCompleted = $bailMobilite->exitMission && $bailMobilite->exitMission->status === 'completed';

        if ($exitCompleted) {
            return 'completed';
        } elseif ($entryCompleted) {
            return 'in_progress';
        } else {
            return 'assigned';
        }
    }
}
