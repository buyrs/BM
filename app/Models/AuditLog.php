<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'auditable_type',
        'auditable_id',
        'user_id',
        'user_email',
        'user_roles',
        'action',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'session_id',
        'request_id',
        'route_name',
        'url',
        'http_method',
        'response_status',
        'severity',
        'is_sensitive',
        'occurred_at'
    ];

    protected $casts = [
        'user_roles' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'is_sensitive' => 'boolean',
        'occurred_at' => 'datetime'
    ];

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get logs for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get logs for a specific model
     */
    public function scopeForModel($query, $modelType, $modelId = null)
    {
        $query = $query->where('auditable_type', $modelType);
        
        if ($modelId !== null) {
            $query->where('auditable_id', $modelId);
        }
        
        return $query;
    }

    /**
     * Scope to get logs by event type
     */
    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to get sensitive operations
     */
    public function scopeSensitive($query)
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Scope to get logs by severity
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to get logs within date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent logs
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('occurred_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope to get logs by IP address
     */
    public function scopeByIpAddress($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope to get failed operations
     */
    public function scopeFailures($query)
    {
        return $query->whereIn('severity', ['error', 'critical'])
                    ->orWhere('response_status', '>=', 400);
    }

    /**
     * Get formatted action description
     */
    public function getActionDescriptionAttribute(): string
    {
        $description = $this->action;
        
        if ($this->auditable_type && $this->auditable_id) {
            $modelName = class_basename($this->auditable_type);
            $description .= " on {$modelName} #{$this->auditable_id}";
        }
        
        return $description;
    }

    /**
     * Get severity color for UI display
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'red',
            'error' => 'orange',
            'warning' => 'yellow',
            'info' => 'blue',
            default => 'gray'
        };
    }

    /**
     * Check if this log entry represents a security event
     */
    public function isSecurityEvent(): bool
    {
        $securityEvents = [
            'login_failed',
            'unauthorized_access',
            'permission_denied',
            'signature_verification_failed',
            'tampering_detected',
            'suspicious_activity'
        ];

        return in_array($this->event_type, $securityEvents) || 
               $this->is_sensitive || 
               in_array($this->severity, ['error', 'critical']);
    }

    /**
     * Get changes summary for display
     */
    public function getChangesSummary(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        $oldValues = $this->old_values;
        $newValues = $this->new_values;

        foreach ($newValues as $field => $newValue) {
            $oldValue = $oldValues[$field] ?? null;
            
            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        return $changes;
    }

    /**
     * Get metadata value by key
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if the log entry has specific metadata
     */
    public function hasMetadata(string $key): bool
    {
        return isset($this->metadata[$key]);
    }
}