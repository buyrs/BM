<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'two_factor_secret',
        'remember_token',
        'api_token',
    ];

    protected array $suspiciousActions = [
        'multiple_failed_logins',
        'privilege_escalation',
        'bulk_delete',
        'sensitive_data_access',
        'unusual_activity_pattern',
    ];

    /**
     * Log a user action
     */
    public function log(
        string $action,
        ?Model $resource = null,
        ?array $changes = null,
        ?User $user = null,
        ?Request $request = null
    ): AuditLog {
        $user = $user ?? Auth::user();
        $request = $request ?? request();

        $auditData = [
            'user_id' => $user?->id,
            'action' => $action,
            'resource_type' => $resource ? get_class($resource) : null,
            'resource_id' => $resource?->getKey(),
            'changes' => $this->sanitizeChanges($changes),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ];

        $auditLog = AuditLog::create($auditData);

        // Check for suspicious activity
        $this->checkSuspiciousActivity($auditLog);

        return $auditLog;
    }

    /**
     * Log model creation
     */
    public function logCreated(Model $model, ?User $user = null): AuditLog
    {
        return $this->log(
            'created',
            $model,
            $model->getAttributes(),
            $user
        );
    }

    /**
     * Log model update
     */
    public function logUpdated(Model $model, array $originalAttributes, ?User $user = null): AuditLog
    {
        $changes = [];
        foreach ($model->getDirty() as $key => $newValue) {
            $changes[$key] = [
                'old' => $originalAttributes[$key] ?? null,
                'new' => $newValue,
            ];
        }

        return $this->log(
            'updated',
            $model,
            $changes,
            $user
        );
    }

    /**
     * Log model deletion
     */
    public function logDeleted(Model $model, ?User $user = null): AuditLog
    {
        return $this->log(
            'deleted',
            $model,
            $model->getAttributes(),
            $user
        );
    }

    /**
     * Log user login
     */
    public function logLogin(User $user, bool $successful = true): AuditLog
    {
        $action = $successful ? 'login_successful' : 'login_failed';
        
        $auditLog = $this->log($action, $user, [
            'successful' => $successful,
            'timestamp' => now()->toISOString(),
        ], $user);

        // Check for multiple failed logins
        if (!$successful) {
            $this->checkFailedLoginAttempts($user);
        }

        return $auditLog;
    }

    /**
     * Log user logout
     */
    public function logLogout(User $user): AuditLog
    {
        return $this->log('logout', $user, [
            'timestamp' => now()->toISOString(),
        ], $user);
    }

    /**
     * Log sensitive data access
     */
    public function logSensitiveAccess(string $dataType, ?Model $resource = null, ?User $user = null): AuditLog
    {
        return $this->log('sensitive_data_access', $resource, [
            'data_type' => $dataType,
            'timestamp' => now()->toISOString(),
        ], $user);
    }

    /**
     * Log bulk operations
     */
    public function logBulkOperation(string $operation, string $resourceType, int $count, ?User $user = null): AuditLog
    {
        $auditData = [
            'user_id' => $user?->id,
            'action' => "bulk_{$operation}",
            'resource_type' => null, // Bulk operations don't have a specific resource
            'resource_id' => null,
            'changes' => $this->sanitizeChanges([
                'resource_type' => $resourceType,
                'count' => $count,
                'timestamp' => now()->toISOString(),
            ]),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ];

        $auditLog = AuditLog::create($auditData);

        // Check for suspicious activity
        $this->checkSuspiciousActivity($auditLog);

        return $auditLog;
    }

    /**
     * Log permission changes
     */
    public function logPermissionChange(User $targetUser, array $changes, ?User $user = null): AuditLog
    {
        return $this->log('permission_change', $targetUser, [
            'permission_changes' => $changes,
            'timestamp' => now()->toISOString(),
        ], $user);
    }

    /**
     * Get audit logs with filtering
     */
    public function getAuditLogs(array $filters = [], int $perPage = 50)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->byAction($filters['action']);
        }

        if (isset($filters['resource_type'])) {
            $query->byResourceType($filters['resource_type']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('action', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('resource_type', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('changes', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Get suspicious activity logs
     */
    public function getSuspiciousActivity(int $days = 7)
    {
        return AuditLog::with('user')
            ->whereIn('action', $this->suspiciousActions)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Sanitize changes to remove sensitive data
     */
    protected function sanitizeChanges(?array $changes): ?array
    {
        if (!$changes) {
            return null;
        }

        foreach ($this->sensitiveFields as $field) {
            if (isset($changes[$field])) {
                $changes[$field] = '[REDACTED]';
            }
            
            // Handle nested changes (for updates)
            if (isset($changes[$field]['old'])) {
                $changes[$field]['old'] = '[REDACTED]';
            }
            if (isset($changes[$field]['new'])) {
                $changes[$field]['new'] = '[REDACTED]';
            }
        }

        return $changes;
    }

    /**
     * Check for suspicious activity patterns
     */
    protected function checkSuspiciousActivity(AuditLog $auditLog): void
    {
        try {
            // Check for rapid successive actions
            if ($auditLog->user_id) {
                $recentActions = AuditLog::where('user_id', $auditLog->user_id)
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->count();

                if ($recentActions > 50) {
                    $this->flagSuspiciousActivity('unusual_activity_pattern', $auditLog);
                }
            }

            // Check for privilege escalation attempts
            if ($auditLog->action === 'permission_change' || $auditLog->action === 'updated') {
                if ($auditLog->resource_type === User::class) {
                    $changes = $auditLog->changes ?? [];
                    if (isset($changes['role'])) {
                        $this->flagSuspiciousActivity('privilege_escalation', $auditLog);
                    }
                }
            }

            // Check for bulk delete operations
            if (str_contains($auditLog->action, 'bulk_delete')) {
                $count = $auditLog->changes['count'] ?? 0;
                if ($count > 100) {
                    $this->flagSuspiciousActivity('bulk_delete', $auditLog);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error checking suspicious activity', [
                'audit_log_id' => $auditLog->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check for multiple failed login attempts
     */
    protected function checkFailedLoginAttempts(User $user): void
    {
        $failedAttempts = AuditLog::where('user_id', $user->id)
            ->where('action', 'login_failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($failedAttempts >= 5) {
            $this->log('multiple_failed_logins', $user, [
                'failed_attempts' => $failedAttempts,
                'time_window' => '1 hour',
            ], $user);
        }
    }

    /**
     * Flag suspicious activity
     */
    protected function flagSuspiciousActivity(string $type, AuditLog $originalLog): void
    {
        $this->log($type, null, [
            'original_log_id' => $originalLog->id,
            'flagged_at' => now()->toISOString(),
            'details' => $originalLog->toArray(),
        ], $originalLog->user);

        // Log to system log for immediate attention
        Log::warning('Suspicious activity detected', [
            'type' => $type,
            'user_id' => $originalLog->user_id,
            'original_action' => $originalLog->action,
            'audit_log_id' => $originalLog->id,
        ]);
    }

    /**
     * Clean up old audit logs based on retention policy
     */
    public function cleanupOldLogs(int $retentionDays = 365): int
    {
        $cutoffDate = now()->subDays($retentionDays);
        
        $deletedCount = AuditLog::where('created_at', '<', $cutoffDate)->delete();

        if ($deletedCount > 0) {
            $this->log('audit_log_cleanup', null, [
                'deleted_count' => $deletedCount,
                'retention_days' => $retentionDays,
                'cutoff_date' => $cutoffDate->toISOString(),
            ]);
        }

        return $deletedCount;
    }
}