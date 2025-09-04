<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\User;
use App\Models\Agent;
use App\Models\BailMobilite;
use App\Services\NotificationService;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MissionService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Create a new mission with proper validation and notifications
     */
    public function createMission(array $data, ?User $user = null): Mission
    {
        $user = $user ?? auth()->user();

        DB::beginTransaction();
        try {
            // Auto-assign logic if requested
            if (!empty($data['auto_assign'])) {
                $checker = $this->getAutoAssignedChecker();
                if ($checker) {
                    $data['agent_id'] = $checker->user_id;
                    $data['status'] = 'assigned';
                }
            }

            // Set status based on assignment
            if (!empty($data['agent_id'])) {
                $data['status'] = 'assigned';
            }

            $mission = Mission::create($data);

            // Send notification if assigned
            if ($mission->agent_id) {
                $this->notificationService->sendMissionAssignedNotification($mission);
            }

            // Log mission creation
            AuditService::logCreated($mission, $user, [
                'auto_assigned' => !empty($data['auto_assign']),
                'assigned_to' => $mission->agent?->name
            ]);

            DB::commit();

            Log::info("Mission created successfully", [
                'mission_id' => $mission->id,
                'assigned_to' => $mission->agent?->name,
                'created_by' => $user->name
            ]);

            return $mission;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create mission", [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update mission with proper validation and notifications
     */
    public function updateMission(Mission $mission, array $data, ?User $user = null): Mission
    {
        $user = $user ?? auth()->user();
        $oldValues = $mission->getAttributes();

        DB::beginTransaction();
        try {
            $mission->update($data);

            // Send notifications for status changes
            if (isset($data['status']) && $data['status'] !== $oldValues['status']) {
                $this->notificationService->sendCalendarMissionStatusNotification(
                    $mission, 
                    $oldValues['status'], 
                    $data['status']
                );
            }

            // Send notification for new assignments
            if (isset($data['agent_id']) && $data['agent_id'] !== $oldValues['agent_id']) {
                $this->notificationService->sendMissionAssignedNotification($mission);
            }

            // Log mission update
            AuditService::logUpdated($mission, $oldValues, $user, [
                'status_changed' => isset($data['status']) && $data['status'] !== $oldValues['status'],
                'agent_changed' => isset($data['agent_id']) && $data['agent_id'] !== $oldValues['agent_id']
            ]);

            DB::commit();

            Log::info("Mission updated successfully", [
                'mission_id' => $mission->id,
                'updated_by' => $user->name
            ]);

            return $mission;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update mission", [
                'mission_id' => $mission->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Delete mission with proper cleanup
     */
    public function deleteMission(Mission $mission, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        DB::beginTransaction();
        try {
            // Cancel related notifications
            $this->notificationService->cancelScheduledNotifications(
                $mission->bailMobilite ?? new BailMobilite(),
                ['mission_assigned', 'mission_reminder']
            );

            // Log mission deletion
            AuditService::logDeleted($mission, $user, [
                'bail_mobilite_id' => $mission->bail_mobilite_id,
                'was_assigned' => !is_null($mission->agent_id)
            ]);

            $mission->delete();

            DB::commit();

            Log::info("Mission deleted successfully", [
                'mission_id' => $mission->id,
                'deleted_by' => $user->name
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete mission", [
                'mission_id' => $mission->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Assign mission to a checker
     */
    public function assignMissionToChecker(Mission $mission, User $checker, ?User $assignedBy = null): Mission
    {
        $assignedBy = $assignedBy ?? auth()->user();

        DB::beginTransaction();
        try {
            $oldValues = $mission->getAttributes();

            $mission->update([
                'agent_id' => $checker->id,
                'status' => 'assigned',
                'ops_assigned_by' => $assignedBy->id
            ]);

            // Send notification to checker
            $this->notificationService->sendMissionAssignedNotification($mission);

            // Log assignment
            AuditService::logUpdated($mission, $oldValues, $assignedBy, [
                'assigned_to' => $checker->name,
                'assigned_by' => $assignedBy->name
            ]);

            DB::commit();

            Log::info("Mission assigned to checker", [
                'mission_id' => $mission->id,
                'checker_id' => $checker->id,
                'assigned_by' => $assignedBy->name
            ]);

            return $mission;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to assign mission", [
                'mission_id' => $mission->id,
                'checker_id' => $checker->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update mission status with proper workflow validation
     */
    public function updateMissionStatus(Mission $mission, string $status, ?User $user = null): Mission
    {
        $user = $user ?? auth()->user();
        $oldStatus = $mission->status;

        // Validate status transition
        if (!$this->isValidStatusTransition($oldStatus, $status)) {
            throw new \InvalidArgumentException("Invalid status transition from {$oldStatus} to {$status}");
        }

        DB::beginTransaction();
        try {
            $mission->update(['status' => $status]);

            // Send notifications based on status change
            $this->notificationService->sendCalendarMissionStatusNotification($mission, $oldStatus, $status);

            // If mission is completed, send completion notification
            if ($status === 'completed') {
                $this->notificationService->sendMissionCompletionNotification($mission);
            }

            // Log status change
            AuditService::logUpdated($mission, ['status' => $oldStatus], $user, [
                'status_changed_from' => $oldStatus,
                'status_changed_to' => $status
            ]);

            DB::commit();

            Log::info("Mission status updated", [
                'mission_id' => $mission->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'updated_by' => $user->name
            ]);

            return $mission;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update mission status", [
                'mission_id' => $mission->id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get the next checker for auto-assignment
     */
    protected function getAutoAssignedChecker(): ?Agent
    {
        // Get all active agents (checkers)
        $agents = Agent::where('status', 'active')->get();
        
        // Filter out downgraded agents, but include them if all are downgraded
        $nonDowngraded = $agents->where('is_downgraded', false);
        $eligible = $nonDowngraded->count() ? $nonDowngraded : $agents;
        
        // Sort by number of assigned missions (least first)
        $eligible = $eligible->sortBy(function($agent) {
            return Mission::where('agent_id', $agent->user_id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->count();
        });
        
        return $eligible->first();
    }

    /**
     * Validate status transition
     */
    protected function isValidStatusTransition(string $from, string $to): bool
    {
        $validTransitions = [
            'unassigned' => ['assigned', 'cancelled'],
            'assigned' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [], // No transitions from completed
            'cancelled' => [] // No transitions from cancelled
        ];

        return in_array($to, $validTransitions[$from] ?? []);
    }

    /**
     * Get missions for a specific user based on their role
     */
    public function getMissionsForUser(User $user, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Mission::query();

        if ($user->hasRole('checker')) {
            $query->where('agent_id', $user->id);
        } elseif ($user->hasRole('ops')) {
            $query->where('ops_assigned_by', $user->id);
        }

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('scheduled_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('scheduled_at', '<=', $filters['date_to']);
        }

        if (isset($filters['bail_mobilite_id'])) {
            $query->where('bail_mobilite_id', $filters['bail_mobilite_id']);
        }

        return $query->withRelations()
                    ->orderBy('scheduled_at', 'desc')
                    ->get();
    }

    /**
     * Get upcoming missions for a user
     */
    public function getUpcomingMissions(User $user, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getMissionsForUser($user, [
            'date_from' => now(),
            'date_to' => now()->addDays($days),
            'status' => ['assigned', 'in_progress']
        ]);
    }

    /**
     * Get overdue missions
     */
    public function getOverdueMissions(User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Mission::where('scheduled_at', '<', now())
                      ->whereIn('status', ['assigned', 'in_progress']);

        if ($user) {
            if ($user->hasRole('checker')) {
                $query->where('agent_id', $user->id);
            } elseif ($user->hasRole('ops')) {
                $query->where('ops_assigned_by', $user->id);
            }
        }

        return $query->withRelations()->get();
    }

    /**
     * Get mission statistics for dashboard
     */
    public function getMissionStatistics(User $user = null): array
    {
        $query = Mission::query();

        if ($user) {
            if ($user->hasRole('checker')) {
                $query->where('agent_id', $user->id);
            } elseif ($user->hasRole('ops')) {
                $query->where('ops_assigned_by', $user->id);
            }
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_missions,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_missions,
            COUNT(CASE WHEN status = "in_progress" THEN 1 END) as in_progress_missions,
            COUNT(CASE WHEN status = "assigned" THEN 1 END) as assigned_missions,
            COUNT(CASE WHEN scheduled_at < NOW() AND status != "completed" THEN 1 END) as overdue_missions
        ')->first();

        return [
            'total' => $stats->total_missions ?? 0,
            'completed' => $stats->completed_missions ?? 0,
            'in_progress' => $stats->in_progress_missions ?? 0,
            'assigned' => $stats->assigned_missions ?? 0,
            'overdue' => $stats->overdue_missions ?? 0,
            'completion_rate' => $stats->total_missions > 0 
                ? round(($stats->completed_missions / $stats->total_missions) * 100, 2) 
                : 0
        ];
    }

    /**
     * Bulk update missions
     */
    public function bulkUpdateMissions(array $missionIds, array $data, ?User $user = null): int
    {
        $user = $user ?? auth()->user();
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($missionIds as $missionId) {
                $mission = Mission::find($missionId);
                if ($mission) {
                    $this->updateMission($mission, $data, $user);
                    $updatedCount++;
                }
            }

            // Log bulk operation
            AuditService::logBulkOperation('update', 'Mission', $updatedCount, $user, [
                'mission_ids' => $missionIds,
                'update_data' => $data
            ]);

            DB::commit();

            Log::info("Bulk mission update completed", [
                'updated_count' => $updatedCount,
                'updated_by' => $user->name
            ]);

            return $updatedCount;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to bulk update missions", [
                'mission_ids' => $missionIds,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
