<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'bail_mobilite_id',
        'mission_id',
        'checklist_id',
        'type',
        'severity',
        'title',
        'description',
        'metadata',
        'status',
        'detected_at',
        'resolved_at',
        'created_by',
        'resolved_by',
        'resolution_notes'
    ];

    protected $casts = [
        'metadata' => 'array',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];

    // Incident types
    const TYPE_MISSING_CHECKLIST = 'missing_checklist';
    const TYPE_INCOMPLETE_CHECKLIST = 'incomplete_checklist';
    const TYPE_MISSING_TENANT_SIGNATURE = 'missing_tenant_signature';
    const TYPE_MISSING_REQUIRED_PHOTOS = 'missing_required_photos';
    const TYPE_MISSING_CONTRACT_SIGNATURE = 'missing_contract_signature';
    const TYPE_KEYS_NOT_RETURNED = 'keys_not_returned';
    const TYPE_OVERDUE_MISSION = 'overdue_mission';
    const TYPE_VALIDATION_TIMEOUT = 'validation_timeout';

    // Severity levels
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    // Status values
    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    /**
     * Get the bail mobilité that owns this incident report.
     */
    public function bailMobilite(): BelongsTo
    {
        return $this->belongsTo(BailMobilite::class);
    }

    /**
     * Get the mission associated with this incident report.
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    /**
     * Get the checklist associated with this incident report.
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    /**
     * Get the user who created this incident report.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who resolved this incident report.
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the corrective actions for this incident report.
     */
    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class);
    }

    /**
     * Scope to get open incidents.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope to get incidents in progress.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope to get resolved incidents.
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope to get closed incidents.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * Scope to get incidents by severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to get critical incidents.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Scope to get high severity incidents.
     */
    public function scopeHigh($query)
    {
        return $query->where('severity', self::SEVERITY_HIGH);
    }

    /**
     * Scope to get incidents by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get incidents for a specific bail mobilité.
     */
    public function scopeForBailMobilite($query, int $bailMobiliteId)
    {
        return $query->where('bail_mobilite_id', $bailMobiliteId);
    }

    /**
     * Scope to get incidents detected today.
     */
    public function scopeDetectedToday($query)
    {
        return $query->whereDate('detected_at', today());
    }

    /**
     * Scope to get incidents detected this week.
     */
    public function scopeDetectedThisWeek($query)
    {
        return $query->whereBetween('detected_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Check if the incident is open.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if the incident is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if the incident is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Check if the incident is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Check if the incident is critical.
     */
    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    /**
     * Check if the incident is high severity.
     */
    public function isHighSeverity(): bool
    {
        return $this->severity === self::SEVERITY_HIGH;
    }

    /**
     * Mark the incident as in progress.
     */
    public function markAsInProgress(User $user = null): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'resolved_by' => $user?->id
        ]);
    }

    /**
     * Mark the incident as resolved.
     */
    public function markAsResolved(User $user = null, string $resolutionNotes = null): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $user?->id,
            'resolution_notes' => $resolutionNotes
        ]);
    }

    /**
     * Mark the incident as closed.
     */
    public function markAsClosed(User $user = null, string $resolutionNotes = null): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'resolved_at' => $this->resolved_at ?? now(),
            'resolved_by' => $user?->id,
            'resolution_notes' => $resolutionNotes ?? $this->resolution_notes
        ]);
    }

    /**
     * Get the incident type label.
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_MISSING_CHECKLIST => 'Checklist manquante',
            self::TYPE_INCOMPLETE_CHECKLIST => 'Checklist incomplète',
            self::TYPE_MISSING_TENANT_SIGNATURE => 'Signature locataire manquante',
            self::TYPE_MISSING_REQUIRED_PHOTOS => 'Photos obligatoires manquantes',
            self::TYPE_MISSING_CONTRACT_SIGNATURE => 'Signature contrat manquante',
            self::TYPE_KEYS_NOT_RETURNED => 'Clés non remises',
            self::TYPE_OVERDUE_MISSION => 'Mission en retard',
            self::TYPE_VALIDATION_TIMEOUT => 'Délai de validation dépassé',
            default => 'Incident inconnu'
        };
    }

    /**
     * Get the severity label.
     */
    public function getSeverityLabel(): string
    {
        return match($this->severity) {
            self::SEVERITY_LOW => 'Faible',
            self::SEVERITY_MEDIUM => 'Moyen',
            self::SEVERITY_HIGH => 'Élevé',
            self::SEVERITY_CRITICAL => 'Critique',
            default => 'Inconnu'
        };
    }

    /**
     * Get the status label.
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_OPEN => 'Ouvert',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_RESOLVED => 'Résolu',
            self::STATUS_CLOSED => 'Fermé',
            default => 'Inconnu'
        };
    }

    /**
     * Get the severity color for UI display.
     */
    public function getSeverityColor(): string
    {
        return match($this->severity) {
            self::SEVERITY_LOW => 'green',
            self::SEVERITY_MEDIUM => 'yellow',
            self::SEVERITY_HIGH => 'orange',
            self::SEVERITY_CRITICAL => 'red',
            default => 'gray'
        };
    }

    /**
     * Get the status color for UI display.
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_OPEN => 'red',
            self::STATUS_IN_PROGRESS => 'yellow',
            self::STATUS_RESOLVED => 'green',
            self::STATUS_CLOSED => 'gray',
            default => 'gray'
        ];
    }

    /**
     * Get the time elapsed since detection.
     */
    public function getTimeElapsed(): string
    {
        return $this->detected_at->diffForHumans();
    }

    /**
     * Get the resolution time if resolved.
     */
    public function getResolutionTime(): ?string
    {
        if (!$this->resolved_at) {
            return null;
        }

        return $this->detected_at->diffForHumans($this->resolved_at, true);
    }
}