<?php

namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\MaintenanceRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        private MaintenanceRequestService $maintenanceRequestService
    ) {}

    /**
     * Display a listing of maintenance requests
     */
    public function index(Request $request): View
    {
        $status = $request->get('status', 'pending');
        $priority = $request->get('priority');

        $query = MaintenanceRequest::with(['mission.property', 'reportedBy', 'assignedTo', 'checklist', 'checklistItem']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        $maintenanceRequests = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $statusCounts = [
            'pending' => MaintenanceRequest::pending()->count(),
            'approved' => MaintenanceRequest::approved()->count(),
            'in_progress' => MaintenanceRequest::inProgress()->count(),
            'completed' => MaintenanceRequest::completed()->count(),
        ];

        return view('ops.maintenance-requests.index', compact(
            'maintenanceRequests',
            'statusCounts',
            'status',
            'priority'
        ));
    }

    /**
     * Display the specified maintenance request
     */
    public function show(MaintenanceRequest $maintenanceRequest): View
    {
        $maintenanceRequest->load([
            'mission.property',
            'reportedBy',
            'assignedTo',
            'checklist',
            'checklistItem'
        ]);

        $opsUsers = User::where('role', 'ops')->get();

        return view('ops.maintenance-requests.show', compact('maintenanceRequest', 'opsUsers'));
    }

    /**
     * Approve a maintenance request
     */
    public function approve(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $assignedUser = $request->assigned_to ? User::find($request->assigned_to) : null;
        
        $success = $this->maintenanceRequestService->approve(
            $maintenanceRequest,
            auth()->user(),
            $assignedUser
        );

        if ($success) {
            return redirect()->back()->with('success', 'Maintenance request approved successfully.');
        }

        return redirect()->back()->with('error', 'Unable to approve maintenance request.');
    }

    /**
     * Reject a maintenance request
     */
    public function reject(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $success = $this->maintenanceRequestService->reject(
            $maintenanceRequest,
            auth()->user(),
            $request->reason
        );

        if ($success) {
            return redirect()->back()->with('success', 'Maintenance request rejected.');
        }

        return redirect()->back()->with('error', 'Unable to reject maintenance request.');
    }

    /**
     * Start work on a maintenance request
     */
    public function startWork(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $success = $this->maintenanceRequestService->startWork($maintenanceRequest);

        if ($success) {
            return redirect()->back()->with('success', 'Work started on maintenance request.');
        }

        return redirect()->back()->with('error', 'Unable to start work on maintenance request.');
    }

    /**
     * Complete a maintenance request
     */
    public function complete(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        $success = $this->maintenanceRequestService->complete(
            $maintenanceRequest,
            $request->notes
        );

        if ($success) {
            return redirect()->back()->with('success', 'Maintenance request completed.');
        }

        return redirect()->back()->with('error', 'Unable to complete maintenance request.');
    }

    /**
     * Update assignment of a maintenance request
     */
    public function updateAssignment(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $maintenanceRequest->update([
            'assigned_to' => $request->assigned_to,
        ]);

        return redirect()->back()->with('success', 'Assignment updated successfully.');
    }
}
