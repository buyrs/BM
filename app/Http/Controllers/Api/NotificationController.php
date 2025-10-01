<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseApiController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    /**
     * Get user's notifications with pagination and filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            // Get pagination and sorting parameters
            $paginationParams = $this->getPaginationParams($request);
            $sortingParams = $this->getSortingParams($request, ['created_at', 'read_at', 'priority']);
            $filters = $this->getFilterParams($request, ['read', 'type', 'priority', 'requires_action']);

            // Build query for user's notifications
            $query = Notification::where('user_id', $user->id);

            // Apply filters
            if (isset($filters['read'])) {
                $isRead = filter_var($filters['read'], FILTER_VALIDATE_BOOLEAN);
                if ($isRead) {
                    $query->whereNotNull('read_at');
                } else {
                    $query->whereNull('read_at');
                }
            }

            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (!empty($filters['priority'])) {
                $query->where('priority', $filters['priority']);
            }

            if (isset($filters['requires_action'])) {
                $requiresAction = filter_var($filters['requires_action'], FILTER_VALIDATE_BOOLEAN);
                $query->where('requires_action', $requiresAction);
                
                if ($requiresAction) {
                    $query->whereNull('action_taken_at');
                }
            }

            // Apply sorting
            $query->orderBy($sortingParams['sort_by'], $sortingParams['sort_order']);

            // Get paginated results
            $notifications = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedNotifications = $notifications->getCollection()->map(function ($notification) {
                return $this->transformNotification($notification);
            });

            $notifications->setCollection($transformedNotifications);

            return $this->paginated($notifications, 'Notifications retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve notifications');
        }
    }

    /**
     * Get a specific notification by ID
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            $notification = Notification::where('user_id', $user->id)->findOrFail($id);

            return $this->success([
                'notification' => $this->transformNotification($notification)
            ], 'Notification retrieved successfully');

        } catch (\Exception $e) {
            return $this->notFound('Notification not found');
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            $notification = Notification::where('user_id', $user->id)->findOrFail($id);

            if ($notification->read_at) {
                return $this->error('Notification is already marked as read', 409);
            }

            $notification->update(['read_at' => now()]);

            // Log the action
            $this->auditLogger->log('notification_read', $user, [
                'notification_id' => $notification->id,
                'notification_type' => $notification->type,
            ]);

            return $this->success([
                'notification' => $this->transformNotification($notification)
            ], 'Notification marked as read');

        } catch (\Exception $e) {
            return $this->notFound('Notification not found');
        }
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            $notification = Notification::where('user_id', $user->id)->findOrFail($id);

            if (!$notification->read_at) {
                return $this->error('Notification is already unread', 409);
            }

            $notification->update(['read_at' => null]);

            return $this->success([
                'notification' => $this->transformNotification($notification)
            ], 'Notification marked as unread');

        } catch (\Exception $e) {
            return $this->notFound('Notification not found');
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            $updatedCount = Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            // Log the action
            $this->auditLogger->log('notifications_mark_all_read', $user, [
                'updated_count' => $updatedCount,
            ]);

            return $this->success([
                'updated_count' => $updatedCount
            ], 'All notifications marked as read');

        } catch (\Exception $e) {
            return $this->serverError('Failed to mark all notifications as read');
        }
    }

    /**
     * Take action on a notification
     */
    public function takeAction(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            $notification = Notification::where('user_id', $user->id)->findOrFail($id);

            if (!$notification->requires_action) {
                return $this->error('This notification does not require action', 409);
            }

            if ($notification->action_taken_at) {
                return $this->error('Action has already been taken on this notification', 409);
            }

            // Validate request
            $validated = $request->validate([
                'action' => ['required', 'string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ]);

            // Update notification
            $notification->update([
                'action_taken_at' => now(),
                'action_taken_by' => $user->id,
                'action_notes' => $validated['notes'] ?? null,
                'read_at' => $notification->read_at ?? now(), // Mark as read if not already
            ]);

            // Log the action
            $this->auditLogger->log('notification_action_taken', $user, [
                'notification_id' => $notification->id,
                'notification_type' => $notification->type,
                'action' => $validated['action'],
            ]);

            return $this->success([
                'notification' => $this->transformNotification($notification)
            ], 'Action taken on notification');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->notFound('Notification not found');
        }
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            $notification = Notification::where('user_id', $user->id)->findOrFail($id);

            // Store notification data for audit
            $notificationData = $notification->toArray();

            // Delete notification
            $notification->delete();

            // Log the action
            $this->auditLogger->log('notification_deleted', $user, [
                'notification_id' => $id,
                'notification_type' => $notificationData['type'],
            ]);

            return $this->success(null, 'Notification deleted successfully');

        } catch (\Exception $e) {
            return $this->notFound('Notification not found');
        }
    }

    /**
     * Get notification statistics for the user
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            $stats = [
                'total_notifications' => Notification::where('user_id', $user->id)->count(),
                'unread_notifications' => Notification::where('user_id', $user->id)
                    ->whereNull('read_at')->count(),
                'action_required_notifications' => Notification::where('user_id', $user->id)
                    ->where('requires_action', true)
                    ->whereNull('action_taken_at')->count(),
                'notifications_by_type' => Notification::where('user_id', $user->id)
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'notifications_by_priority' => Notification::where('user_id', $user->id)
                    ->selectRaw('priority, COUNT(*) as count')
                    ->groupBy('priority')
                    ->pluck('count', 'priority'),
            ];

            return $this->success($stats, 'Notification statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve notification statistics');
        }
    }

    /**
     * Get unread notification count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            $count = Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();

            return $this->success([
                'unread_count' => $count
            ], 'Unread notification count retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve unread notification count');
        }
    }

    /**
     * Transform notification for API response
     */
    private function transformNotification(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'data' => $notification->data,
            'priority' => $notification->priority,
            'requires_action' => $notification->requires_action,
            'channels' => $notification->channels,
            'read_at' => $notification->read_at,
            'action_taken_at' => $notification->action_taken_at,
            'action_taken_by' => $notification->action_taken_by,
            'action_notes' => $notification->action_notes,
            'mission_id' => $notification->mission_id,
            'checklist_id' => $notification->checklist_id,
            'created_at' => $notification->created_at,
            'updated_at' => $notification->updated_at,
        ];
    }
}