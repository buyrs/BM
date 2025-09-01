<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AccessLogService
{
    /**
     * Log unauthorized access attempt
     */
    public static function logUnauthorizedAccess(
        Request $request, 
        string $reason, 
        ?User $user = null,
        array $additionalData = []
    ): void {
        $logData = [
            'event' => 'unauthorized_access_attempt',
            'reason' => $reason,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->session()->getId(),
        ];

        if ($user) {
            $logData['user_id'] = $user->id;
            $logData['user_email'] = $user->email;
            $logData['user_roles'] = $user->getRoleNames()->toArray();
            $logData['user_permissions'] = $user->getAllPermissions()->pluck('name')->toArray();
        }

        // Add any additional context data
        $logData = array_merge($logData, $additionalData);

        // Log to security channel
        Log::channel('security')->warning('Unauthorized access attempt', $logData);
        
        // Also log to default channel for immediate visibility
        Log::warning('Security: Unauthorized access attempt', [
            'user' => $user?->email ?? 'anonymous',
            'route' => $request->route()?->getName(),
            'reason' => $reason,
            'ip' => $request->ip()
        ]);
    }

    /**
     * Log successful access to sensitive operations
     */
    public static function logSensitiveAccess(
        Request $request,
        User $user,
        string $operation,
        array $additionalData = []
    ): void {
        $logData = [
            'event' => 'sensitive_operation_access',
            'operation' => $operation,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_roles' => $user->getRoleNames()->toArray(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->session()->getId(),
        ];

        $logData = array_merge($logData, $additionalData);

        Log::channel('security')->info('Sensitive operation accessed', $logData);
    }

    /**
     * Log permission checks
     */
    public static function logPermissionCheck(
        User $user,
        string $permission,
        bool $granted,
        string $context = ''
    ): void {
        $logData = [
            'event' => 'permission_check',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'permission' => $permission,
            'granted' => $granted,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        if (!$granted) {
            Log::channel('security')->warning('Permission denied', $logData);
        } else {
            Log::channel('security')->debug('Permission granted', $logData);
        }
    }
}