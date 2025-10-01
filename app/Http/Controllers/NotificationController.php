<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Display notifications for the authenticated user
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $filter = $request->get('filter', 'all');

        $query = $user->notifications()->with(['mission.property', 'checklist']);

        switch ($filter) {
            case 'unread':
                $query->unread();
                break;
            case 'action_required':
                $query->requiringAction();
                break;
            case 'read':
                $query->whereNotNull('read_at');
                break;
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        $counts = [
            'all' => $user->notifications()->count(),
            'unread' => $user->unreadNotifications()->count(),
            'action_required' => $user->actionRequiredNotifications()->count(),
        ];

        return view('notifications.index', compact('notifications', 'counts', 'filter'));
    }

    /**
     * Get notifications for API/AJAX requests
     */
    public function getNotifications(Request $request): JsonResponse
    {
        $user = auth()->user();
        $limit = $request->get('limit', 10);
        $unreadOnly = $request->boolean('unread_only', false);

        $query = $user->notifications()->with(['mission.property', 'checklist']);

        if ($unreadOnly) {
            $query->unread();
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'priority' => $notification->priority,
                    'requires_action' => $notification->requires_action,
                    'is_read' => $notification->isRead(),
                    'created_at' => $notification->created_at->diffForHumans(),
                    'data' => $notification->data,
                ];
            }),
            'unread_count' => $user->unreadNotifications()->count(),
            'action_required_count' => $user->actionRequiredNotifications()->count(),
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->notificationService->markAsRead($notification);

        return response()->json(['success' => true]);
    }

    /**
     * Mark multiple notifications as read
     */
    public function markMultipleAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications,id',
        ]);

        $notifications = Notification::whereIn('id', $request->notification_ids)
            ->where('user_id', auth()->id())
            ->get();

        $this->notificationService->markMultipleAsRead($notifications);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read for the user
     */
    public function markAllAsRead(): JsonResponse
    {
        $notifications = auth()->user()->unreadNotifications()->get();
        $this->notificationService->markMultipleAsRead($notifications);

        return response()->json(['success' => true]);
    }

    /**
     * Mark action as taken on a notification
     */
    public function markActionTaken(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$notification->requires_action) {
            return response()->json(['error' => 'This notification does not require action'], 400);
        }

        $notification->markActionTaken(auth()->user());

        return response()->json(['success' => true]);
    }

    /**
     * Get notification counts for the user
     */
    public function getCounts(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'action_required' => $user->actionRequiredNotifications()->count(),
            'total' => $user->notifications()->count(),
        ]);
    }
}
