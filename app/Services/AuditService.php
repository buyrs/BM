<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * Log a user action with comprehensive audit trail
     */
    public static function logAction(
        string $eventType,
        string $action,
        ?Model $model = null,
        ?User $user = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
        string $severity = 'info',
        bool $isSensitive = false
    ): AuditLog {
        $request = request();
        $user = $user ?? auth()->user();

        // Generate unique request ID for tracing
        $requestId = $request->header('X-Request-ID') ?? Str::uuid()->toString();

        // Prepare audit log data
        $auditData = [
            'event_type' => $eventType,
            'action' => $action,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model?->getKey(),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_roles' => $user?->getRoleNames()->toArray(),
            'old_values' => self::sanitizeValues($oldValues),
            'new_values' => self::sanitizeValues($newValues),
            'metadata' => array_merge($metadata, self::captureRequestMetadata($request)),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'request_id' => $requestId,
            'route_name' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'http_method' => $request->method(),
            'severity' => $severity,
            'is_sensitive' => $isSensitive,
            'occurred_at' => now()
        ];

        // Create audit log entry
        $auditLog = AuditLog::create($auditData);

        // Log to security channel for sensitive operations
        if ($isSensitive || in_array($severity, ['error', 'critical'])) {
            Log::channel('security')->log($severity, "Audit: {$action}", [
                'audit_id' => $auditLog->id,
                'user' => $user?->email ?? 'anonymous',
                'event_type' => $eventType,
                'model' => $model ? get_class($model) . '#' . $model->getKey() : null,
                'ip' => $request->ip(),
                'request_id' => $requestId
            ]);
        }

        return $auditLog;
    }

    /**
     * Log model creation
     */
    public static function logCreated(Model $model, ?User $user = null, array $metadata = []): AuditLog
    {
        return self::logAction(
            'created',
            'Created ' . class_basename($model),
            $model,
            $user,
            [],
            $model->getAttributes(),
            $metadata,
            'info',
            self::isSensitiveModel($model)
        );
    }

    /**
     * Log model update
     */
    public static function logUpdated(Model $model, array $oldValues, ?User $user = null, array $metadata = []): AuditLog
    {
        return self::logAction(
            'updated',
            'Updated ' . class_basename($model),
            $model,
            $user,
            $oldValues,
            $model->getAttributes(),
            $metadata,
            'info',
            self::isSensitiveModel($model)
        );
    }

    /**
     * Log model deletion
     */
    public static function logDeleted(Model $model, ?User $user = null, array $metadata = []): AuditLog
    {
        return self::logAction(
            'deleted',
            'Deleted ' . class_basename($model),
            $model,
            $user,
            $model->getAttributes(),
            [],
            $metadata,
            'warning',
            self::isSensitiveModel($model)
        );
    }

    /**
     * Log model view/access
     */
    public static function logViewed(Model $model, ?User $user = null, array $metadata = []): AuditLog
    {
        return self::logAction(
            'viewed',
            'Viewed ' . class_basename($model),
            $model,
            $user,
            [],
            [],
            $metadata,
            'info',
            self::isSensitiveModel($model)
        );
    }

    /**
     * Log authentication events
     */
    public static function logAuthentication(string $event, ?User $user = null, array $metadata = []): AuditLog
    {
        $severity = in_array($event, ['login_failed', 'logout_forced']) ? 'warning' : 'info';
        
        return self::logAction(
            $event,
            ucfirst(str_replace('_', ' ', $event)),
            null,
            $user,
            [],
            [],
            $metadata,
            $severity,
            true
        );
    }

    /**
     * Log permission checks
     */
    public static function logPermissionCheck(
        string $permission,
        bool $granted,
        ?User $user = null,
        ?Model $model = null,
        array $metadata = []
    ): AuditLog {
        $action = $granted ? "Permission granted: {$permission}" : "Permission denied: {$permission}";
        $severity = $granted ? 'info' : 'warning';
        
        return self::logAction(
            $granted ? 'permission_granted' : 'permission_denied',
            $action,
            $model,
            $user,
            [],
            [],
            array_merge($metadata, ['permission' => $permission]),
            $severity,
            !$granted
        );
    }

    /**
     * Log security events
     */
    public static function logSecurityEvent(
        string $eventType,
        string $description,
        ?User $user = null,
        ?Model $model = null,
        array $metadata = [],
        string $severity = 'warning'
    ): AuditLog {
        return self::logAction(
            $eventType,
            $description,
            $model,
            $user,
            [],
            [],
            $metadata,
            $severity,
            true
        );
    }

    /**
     * Log file operations
     */
    public static function logFileOperation(
        string $operation,
        string $filename,
        ?User $user = null,
        array $metadata = []
    ): AuditLog {
        $isSensitive = str_contains($filename, 'signature') || 
                      str_contains($filename, 'contract') ||
                      str_contains($filename, 'private');

        return self::logAction(
            'file_' . $operation,
            ucfirst($operation) . " file: {$filename}",
            null,
            $user,
            [],
            [],
            array_merge($metadata, ['filename' => $filename]),
            'info',
            $isSensitive
        );
    }

    /**
     * Log API access
     */
    public static function logApiAccess(
        string $endpoint,
        string $method,
        int $responseStatus,
        ?User $user = null,
        array $metadata = []
    ): AuditLog {
        $severity = $responseStatus >= 400 ? 'error' : 'info';
        $isSensitive = str_contains($endpoint, 'signature') || 
                      str_contains($endpoint, 'contract') ||
                      $responseStatus >= 400;

        return self::logAction(
            'api_access',
            "{$method} {$endpoint}",
            null,
            $user,
            [],
            [],
            array_merge($metadata, [
                'endpoint' => $endpoint,
                'method' => $method,
                'response_status' => $responseStatus
            ]),
            $severity,
            $isSensitive
        );
    }

    /**
     * Log bulk operations
     */
    public static function logBulkOperation(
        string $operation,
        string $modelType,
        int $affectedCount,
        ?User $user = null,
        array $metadata = []
    ): AuditLog {
        return self::logAction(
            'bulk_' . $operation,
            "Bulk {$operation} on {$affectedCount} {$modelType} records",
            null,
            $user,
            [],
            [],
            array_merge($metadata, [
                'model_type' => $modelType,
                'affected_count' => $affectedCount
            ]),
            'warning',
            true
        );
    }

    /**
     * Log export operations
     */
    public static function logExport(
        string $exportType,
        string $format,
        int $recordCount,
        ?User $user = null,
        array $metadata = []
    ): AuditLog {
        return self::logAction(
            'data_export',
            "Exported {$recordCount} {$exportType} records as {$format}",
            null,
            $user,
            [],
            [],
            array_merge($metadata, [
                'export_type' => $exportType,
                'format' => $format,
                'record_count' => $recordCount
            ]),
            'info',
            true
        );
    }

    /**
     * Capture comprehensive request metadata
     */
    private static function captureRequestMetadata(Request $request): array
    {
        return [
            'referer' => $request->header('Referer'),
            'accept_language' => $request->header('Accept-Language'),
            'accept_encoding' => $request->header('Accept-Encoding'),
            'connection_type' => $request->header('Connection'),
            'request_time' => $request->server('REQUEST_TIME_FLOAT'),
            'server_name' => $request->server('SERVER_NAME'),
            'server_port' => $request->server('SERVER_PORT'),
            'https' => $request->isSecure(),
            'ajax' => $request->ajax(),
            'pjax' => $request->pjax(),
            'query_params' => $request->query(),
            'has_files' => $request->hasFile('*'),
            'content_length' => $request->header('Content-Length'),
            'content_type' => $request->header('Content-Type')
        ];
    }

    /**
     * Sanitize values to remove sensitive data
     */
    private static function sanitizeValues(array $values): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'private_key',
            'signature_data'
        ];

        $sanitized = [];
        
        foreach ($values as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_string($value) && strlen($value) > 1000) {
                // Truncate very long strings
                $sanitized[$key] = substr($value, 0, 1000) . '... [TRUNCATED]';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Check if a model contains sensitive data
     */
    private static function isSensitiveModel(Model $model): bool
    {
        $sensitiveModels = [
            'App\Models\BailMobiliteSignature',
            'App\Models\ContractTemplate',
            'App\Models\User',
            'App\Models\BailMobilite'
        ];

        return in_array(get_class($model), $sensitiveModels);
    }

    /**
     * Get audit trail for a specific model
     */
    public static function getAuditTrail(Model $model, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::forModel(get_class($model), $model->getKey())
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get security events for a user
     */
    public static function getSecurityEvents(?User $user = null, int $hours = 24): \Illuminate\Database\Eloquent\Collection
    {
        $query = AuditLog::where('is_sensitive', true)
            ->orWhereIn('severity', ['error', 'critical'])
            ->orWhereIn('event_type', [
                'login_failed',
                'permission_denied',
                'unauthorized_access',
                'tampering_detected'
            ]);

        if ($user) {
            $query->where('user_id', $user->id);
        }

        return $query->recent($hours)
            ->orderBy('occurred_at', 'desc')
            ->get();
    }

    /**
     * Get audit statistics
     */
    public static function getAuditStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'total_events' => AuditLog::where('occurred_at', '>=', $startDate)->count(),
            'sensitive_events' => AuditLog::where('occurred_at', '>=', $startDate)
                ->where('is_sensitive', true)->count(),
            'failed_events' => AuditLog::where('occurred_at', '>=', $startDate)
                ->whereIn('severity', ['error', 'critical'])->count(),
            'unique_users' => AuditLog::where('occurred_at', '>=', $startDate)
                ->whereNotNull('user_id')->distinct('user_id')->count(),
            'unique_ips' => AuditLog::where('occurred_at', '>=', $startDate)
                ->whereNotNull('ip_address')->distinct('ip_address')->count(),
            'events_by_type' => AuditLog::where('occurred_at', '>=', $startDate)
                ->groupBy('event_type')
                ->selectRaw('event_type, count(*) as count')
                ->pluck('count', 'event_type')
                ->toArray(),
            'events_by_severity' => AuditLog::where('occurred_at', '>=', $startDate)
                ->groupBy('severity')
                ->selectRaw('severity, count(*) as count')
                ->pluck('count', 'severity')
                ->toArray()
        ];
    }

    /**
     * Clean up old audit logs based on retention policy
     */
    public static function cleanupOldLogs(int $retentionDays = 2555): int // ~7 years default
    {
        $cutoffDate = now()->subDays($retentionDays);
        
        // Archive sensitive logs before deletion
        $sensitiveLogsToArchive = AuditLog::where('is_sensitive', true)
            ->where('occurred_at', '<', $cutoffDate)
            ->get();

        foreach ($sensitiveLogsToArchive as $log) {
            self::archiveAuditLog($log);
        }

        // Delete old logs
        $deletedCount = AuditLog::where('occurred_at', '<', $cutoffDate)->delete();

        Log::channel('security')->info('Audit log cleanup completed', [
            'deleted_count' => $deletedCount,
            'archived_sensitive_count' => $sensitiveLogsToArchive->count(),
            'cutoff_date' => $cutoffDate,
            'retention_days' => $retentionDays
        ]);

        return $deletedCount;
    }

    /**
     * Archive sensitive audit log before deletion
     */
    private static function archiveAuditLog(AuditLog $log): void
    {
        $archiveData = $log->toArray();
        $archiveFilename = sprintf(
            'archives/audit_logs/%s_%s_%s.json',
            $log->id,
            $log->event_type,
            $log->occurred_at->format('Y-m-d_H-i-s')
        );

        $encryptedData = encrypt(json_encode($archiveData, JSON_PRETTY_PRINT));
        \Storage::disk('private')->put($archiveFilename, $encryptedData);

        // Create hash for integrity verification
        $hash = hash('sha256', $encryptedData);
        \Storage::disk('private')->put($archiveFilename . '.hash', $hash);
    }

    /**
     * Search audit logs with advanced filters
     */
    public static function searchLogs(array $filters = [], int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        $query = AuditLog::query();

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (isset($filters['is_sensitive'])) {
            $query->where('is_sensitive', $filters['is_sensitive']);
        }

        if (isset($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        if (isset($filters['model_type'])) {
            $query->where('auditable_type', $filters['model_type']);
        }

        if (isset($filters['model_id'])) {
            $query->where('auditable_id', $filters['model_id']);
        }

        if (isset($filters['start_date'])) {
            $query->where('occurred_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('occurred_at', '<=', $filters['end_date']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('user_email', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhereJsonContains('metadata', $search);
            });
        }

        return $query->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();
    }
}