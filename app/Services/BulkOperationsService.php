<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\User;
use App\Models\Property;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BulkOperationsService extends BaseService
{
    protected NotificationService $notificationService;
    protected AuditLogger $auditLogger;

    public function __construct(NotificationService $notificationService, AuditLogger $auditLogger)
    {
        $this->notificationService = $notificationService;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Bulk update mission assignments
     */
    public function bulkAssignMissions(array $missionIds, array $assignments): array
    {
        $validator = Validator::make([
            'mission_ids' => $missionIds,
            'assignments' => $assignments
        ], [
            'mission_ids' => 'required|array|min:1',
            'mission_ids.*' => 'exists:missions,id',
            'assignments.checker_id' => 'nullable|exists:users,id',
            'assignments.ops_id' => 'nullable|exists:users,id',
            'assignments.status' => 'nullable|in:pending,in_progress,completed,cancelled'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        DB::transaction(function () use ($missionIds, $assignments, &$results) {
            foreach ($missionIds as $missionId) {
                try {
                    $mission = Mission::findOrFail($missionId);
                    $oldData = $mission->toArray();
                    
                    $mission->update(array_filter($assignments));
                    
                    // Log the change
                    $this->auditLogger->log(
                        auth()->user(),
                        'bulk_mission_assignment',
                        'Mission',
                        $missionId,
                        ['old' => $oldData, 'new' => $mission->fresh()->toArray()]
                    );

                    // Send notifications for assignment changes
                    if (isset($assignments['checker_id']) && $assignments['checker_id'] !== $oldData['checker_id']) {
                        $this->notificationService->sendMissionAssignedNotification($mission);
                    }

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Mission {$missionId}: " . $e->getMessage();
                }
            }
        });

        return $results;
    }

    /**
     * Bulk update mission status
     */
    public function bulkUpdateMissionStatus(array $missionIds, string $status): array
    {
        $validator = Validator::make([
            'mission_ids' => $missionIds,
            'status' => $status
        ], [
            'mission_ids' => 'required|array|min:1',
            'mission_ids.*' => 'exists:missions,id',
            'status' => 'required|in:pending,in_progress,completed,cancelled'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        DB::transaction(function () use ($missionIds, $status, &$results) {
            foreach ($missionIds as $missionId) {
                try {
                    $mission = Mission::findOrFail($missionId);
                    $oldStatus = $mission->status;
                    
                    $mission->update(['status' => $status]);
                    
                    // Log the change
                    $this->auditLogger->log(
                        auth()->user(),
                        'bulk_mission_status_update',
                        'Mission',
                        $missionId,
                        ['old_status' => $oldStatus, 'new_status' => $status]
                    );

                    // Send status change notifications
                    $this->notificationService->sendMissionStatusNotification($mission, $oldStatus);

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Mission {$missionId}: " . $e->getMessage();
                }
            }
        });

        return $results;
    }

    /**
     * Bulk update user roles and status
     */
    public function bulkUpdateUsers(array $userIds, array $updates): array
    {
        $validator = Validator::make([
            'user_ids' => $userIds,
            'updates' => $updates
        ], [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'updates.role' => 'nullable|in:admin,ops,checker',
            'updates.two_factor_enabled' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        DB::transaction(function () use ($userIds, $updates, &$results) {
            foreach ($userIds as $userId) {
                try {
                    $user = User::findOrFail($userId);
                    $oldData = $user->toArray();
                    
                    $user->update(array_filter($updates));
                    
                    // Log the change
                    $this->auditLogger->log(
                        auth()->user(),
                        'bulk_user_update',
                        'User',
                        $userId,
                        ['old' => $oldData, 'new' => $user->fresh()->toArray()]
                    );

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "User {$userId}: " . $e->getMessage();
                }
            }
        });

        return $results;
    }

    /**
     * Bulk import properties from CSV data
     */
    public function bulkImportProperties(array $propertiesData): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'duplicates' => 0
        ];

        DB::transaction(function () use ($propertiesData, &$results) {
            foreach ($propertiesData as $index => $propertyData) {
                try {
                    $validator = Validator::make($propertyData, [
                        'owner_name' => 'required|string|max:255',
                        'owner_address' => 'required|string|max:500',
                        'property_address' => 'required|string|max:500',
                        'property_type' => 'required|string|max:100',
                        'description' => 'nullable|string|max:1000'
                    ]);

                    if ($validator->fails()) {
                        $results['failed']++;
                        $results['errors'][] = "Row " . ($index + 1) . ": " . implode(', ', $validator->errors()->all());
                        continue;
                    }

                    // Check for duplicates
                    $existing = Property::where('property_address', $propertyData['property_address'])->first();
                    if ($existing) {
                        $results['duplicates']++;
                        continue;
                    }

                    $property = Property::create($propertyData);
                    
                    // Log the creation
                    $this->auditLogger->log(
                        auth()->user(),
                        'bulk_property_import',
                        'Property',
                        $property->id,
                        ['data' => $propertyData]
                    );

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }
        });

        return $results;
    }

    /**
     * Bulk delete properties
     */
    public function bulkDeleteProperties(array $propertyIds): array
    {
        $validator = Validator::make([
            'property_ids' => $propertyIds
        ], [
            'property_ids' => 'required|array|min:1',
            'property_ids.*' => 'exists:properties,id'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        DB::transaction(function () use ($propertyIds, &$results) {
            foreach ($propertyIds as $propertyId) {
                try {
                    $property = Property::findOrFail($propertyId);
                    
                    // Check if property has associated missions
                    $missionCount = $property->missions()->count();
                    if ($missionCount > 0) {
                        $results['failed']++;
                        $results['errors'][] = "Property {$propertyId}: Cannot delete property with {$missionCount} associated missions";
                        continue;
                    }

                    $propertyData = $property->toArray();
                    $property->delete();
                    
                    // Log the deletion
                    $this->auditLogger->log(
                        auth()->user(),
                        'bulk_property_delete',
                        'Property',
                        $propertyId,
                        ['deleted_data' => $propertyData]
                    );

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Property {$propertyId}: " . $e->getMessage();
                }
            }
        });

        return $results;
    }

    /**
     * Bulk send notifications
     */
    public function bulkSendNotifications(array $userIds, array $notificationData): array
    {
        $validator = Validator::make([
            'user_ids' => $userIds,
            'notification_data' => $notificationData
        ], [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'notification_data.title' => 'required|string|max:255',
            'notification_data.message' => 'required|string|max:1000',
            'notification_data.type' => 'required|string|in:info,warning,error,success',
            'notification_data.channels' => 'nullable|array',
            'notification_data.channels.*' => 'in:database,email,websocket'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $channels = $notificationData['channels'] ?? ['database'];

        foreach ($userIds as $userId) {
            try {
                $user = User::findOrFail($userId);
                
                $notification = Notification::create([
                    'user_id' => $userId,
                    'type' => $notificationData['type'],
                    'title' => $notificationData['title'],
                    'message' => $notificationData['message'],
                    'channels' => $channels,
                    'data' => $notificationData['data'] ?? []
                ]);

                // Send through specified channels
                $this->notificationService->sendNotification($user, $notification, $channels);
                
                // Log the notification
                $this->auditLogger->log(
                    auth()->user(),
                    'bulk_notification_send',
                    'Notification',
                    $notification->id,
                    ['recipient_id' => $userId, 'channels' => $channels]
                );

                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "User {$userId}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get bulk operation statistics
     */
    public function getBulkOperationStats(): array
    {
        return [
            'total_missions' => Mission::count(),
            'total_users' => User::count(),
            'total_properties' => Property::count(),
            'pending_missions' => Mission::where('status', 'pending')->count(),
            'in_progress_missions' => Mission::where('status', 'in_progress')->count(),
            'completed_missions' => Mission::where('status', 'completed')->count(),
            'users_by_role' => [
                'admin' => User::where('role', 'admin')->count(),
                'ops' => User::where('role', 'ops')->count(),
                'checker' => User::where('role', 'checker')->count()
            ]
        ];
    }
}