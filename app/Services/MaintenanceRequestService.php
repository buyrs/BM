<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\User;
use Illuminate\Support\Collection;

class MaintenanceRequestService extends BaseService
{
    public function __construct(
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    /**
     * Create a maintenance request from a checklist item
     */
    public function createFromChecklistItem(
        ChecklistItem $checklistItem,
        User $reportedBy,
        string $description,
        string $priority = 'medium',
        ?float $estimatedCost = null,
        array $attachments = []
    ): MaintenanceRequest {
        $maintenanceRequest = MaintenanceRequest::create([
            'mission_id' => $checklistItem->checklist->mission_id,
            'checklist_id' => $checklistItem->checklist_id,
            'checklist_item_id' => $checklistItem->id,
            'reported_by' => $reportedBy->id,
            'priority' => $priority,
            'description' => $description,
            'estimated_cost' => $estimatedCost,
            'attachments' => $attachments,
        ]);

        // Notify ops users about the new maintenance request
        $this->notifyOpsUsers($maintenanceRequest, 'created');

        return $maintenanceRequest;
    }

    /**
     * Create a general maintenance request for a mission
     */
    public function createForMission(
        Mission $mission,
        User $reportedBy,
        string $description,
        string $priority = 'medium',
        ?float $estimatedCost = null,
        array $attachments = []
    ): MaintenanceRequest {
        $maintenanceRequest = MaintenanceRequest::create([
            'mission_id' => $mission->id,
            'reported_by' => $reportedBy->id,
            'priority' => $priority,
            'description' => $description,
            'estimated_cost' => $estimatedCost,
            'attachments' => $attachments,
        ]);

        // Notify ops users about the new maintenance request
        $this->notifyOpsUsers($maintenanceRequest, 'created');

        return $maintenanceRequest;
    }

    /**
     * Approve a maintenance request
     */
    public function approve(MaintenanceRequest $request, User $approver, ?User $assignTo = null): bool
    {
        if (!$request->isPending()) {
            return false;
        }

        $request->approve($assignTo ?? $approver);

        // Notify the assigned user
        if ($request->assignedTo) {
            $this->notificationService->create(
                user: $request->assignedTo,
                type: 'maintenance_approved',
                title: 'Maintenance Request Approved',
                message: "A maintenance request has been approved and assigned to you for {$request->mission->property->name}",
                data: [
                    'maintenance_request_id' => $request->id,
                    'property_name' => $request->mission->property->name,
                    'priority' => $request->priority,
                    'estimated_cost' => $request->estimated_cost,
                ],
                channels: ['database', 'email'],
                priority: $request->priority,
                requiresAction: true,
                mission: $request->mission
            );
        }

        // Notify the reporter
        $this->notificationService->create(
            user: $request->reportedBy,
            type: 'maintenance_approved',
            title: 'Your Maintenance Request Was Approved',
            message: "Your maintenance request for {$request->mission->property->name} has been approved",
            data: [
                'maintenance_request_id' => $request->id,
                'property_name' => $request->mission->property->name,
                'approved_by' => $approver->name,
            ],
            channels: ['database', 'email'],
            mission: $request->mission
        );

        return true;
    }

    /**
     * Reject a maintenance request
     */
    public function reject(MaintenanceRequest $request, User $rejector, string $reason): bool
    {
        if (!$request->isPending()) {
            return false;
        }

        $request->reject($reason);

        // Notify the reporter
        $this->notificationService->create(
            user: $request->reportedBy,
            type: 'maintenance_rejected',
            title: 'Maintenance Request Rejected',
            message: "Your maintenance request for {$request->mission->property->name} has been rejected",
            data: [
                'maintenance_request_id' => $request->id,
                'property_name' => $request->mission->property->name,
                'rejected_by' => $rejector->name,
                'reason' => $reason,
            ],
            channels: ['database', 'email'],
            mission: $request->mission
        );

        return true;
    }

    /**
     * Start work on a maintenance request
     */
    public function startWork(MaintenanceRequest $request): bool
    {
        if (!$request->isApproved()) {
            return false;
        }

        $request->startWork();

        // Notify relevant users
        $this->notificationService->create(
            user: $request->reportedBy,
            type: 'maintenance_started',
            title: 'Maintenance Work Started',
            message: "Work has started on your maintenance request for {$request->mission->property->name}",
            data: [
                'maintenance_request_id' => $request->id,
                'property_name' => $request->mission->property->name,
                'started_by' => $request->assignedTo->name,
            ],
            channels: ['database'],
            mission: $request->mission
        );

        return true;
    }

    /**
     * Complete a maintenance request
     */
    public function complete(MaintenanceRequest $request, string $notes = null): bool
    {
        if (!$request->isInProgress()) {
            return false;
        }

        $request->complete($notes);

        // Notify the reporter
        $this->notificationService->create(
            user: $request->reportedBy,
            type: 'maintenance_completed',
            title: 'Maintenance Request Completed',
            message: "Your maintenance request for {$request->mission->property->name} has been completed",
            data: [
                'maintenance_request_id' => $request->id,
                'property_name' => $request->mission->property->name,
                'completed_by' => $request->assignedTo->name,
                'completion_notes' => $notes,
            ],
            channels: ['database', 'email'],
            mission: $request->mission
        );

        // Notify ops users
        $this->notifyOpsUsers($request, 'completed');

        return true;
    }

    /**
     * Get pending maintenance requests
     */
    public function getPendingRequests(): Collection
    {
        return MaintenanceRequest::pending()
            ->with(['mission.property', 'reportedBy', 'checklist', 'checklistItem'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get maintenance requests assigned to a user
     */
    public function getAssignedToUser(User $user): Collection
    {
        return MaintenanceRequest::assignedTo($user)
            ->with(['mission.property', 'reportedBy', 'checklist', 'checklistItem'])
            ->whereIn('status', ['approved', 'in_progress'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get maintenance requests by status
     */
    public function getByStatus(string $status): Collection
    {
        return MaintenanceRequest::where('status', $status)
            ->with(['mission.property', 'reportedBy', 'assignedTo', 'checklist', 'checklistItem'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Notify ops users about maintenance request events
     */
    private function notifyOpsUsers(MaintenanceRequest $request, string $event): void
    {
        $opsUsers = User::where('role', 'ops')->get();

        $title = match($event) {
            'created' => 'New Maintenance Request',
            'completed' => 'Maintenance Request Completed',
            default => 'Maintenance Request Update'
        };

        $message = match($event) {
            'created' => "A new maintenance request has been submitted for {$request->mission->property->name}",
            'completed' => "A maintenance request has been completed for {$request->mission->property->name}",
            default => "A maintenance request has been updated for {$request->mission->property->name}"
        };

        foreach ($opsUsers as $user) {
            $this->notificationService->create(
                user: $user,
                type: 'maintenance_request',
                title: $title,
                message: $message,
                data: [
                    'maintenance_request_id' => $request->id,
                    'property_name' => $request->mission->property->name,
                    'priority' => $request->priority,
                    'status' => $request->status,
                    'reported_by' => $request->reportedBy->name,
                ],
                channels: ['database', 'email'],
                priority: $request->priority,
                requiresAction: $event === 'created',
                mission: $request->mission
            );
        }
    }
}