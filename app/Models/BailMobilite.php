<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BailMobilite extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date',
        'end_date',
        'address',
        'tenant_name',
        'tenant_phone',
        'tenant_email',
        'notes',
        'status',
        'ops_user_id',
        'entry_mission_id',
        'exit_mission_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    /**
     * Get the ops user who manages this bail mobilité.
     */
    public function opsUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ops_user_id');
    }

    /**
     * Get the entry mission for this bail mobilité.
     */
    public function entryMission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'entry_mission_id');
    }

    /**
     * Get the exit mission for this bail mobilité.
     */
    public function exitMission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'exit_mission_id');
    }

    /**
     * Get all notifications for this bail mobilité.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get all signatures for this bail mobilité.
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(BailMobiliteSignature::class);
    }

    /**
     * Get the entry signature for this bail mobilité.
     */
    public function entrySignature(): HasOne
    {
        return $this->hasOne(BailMobiliteSignature::class)->where('signature_type', 'entry');
    }

    /**
     * Get the exit signature for this bail mobilité.
     */
    public function exitSignature(): HasOne
    {
        return $this->hasOne(BailMobiliteSignature::class)->where('signature_type', 'exit');
    }

    /**
     * Scope to get bail mobilités with assigned status.
     */
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    /**
     * Scope to get bail mobilités with in_progress status.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to get bail mobilités with completed status.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get bail mobilités with incident status.
     */
    public function scopeIncident($query)
    {
        return $query->where('status', 'incident');
    }

    /**
     * Scope to get bail mobilités ending within specified days.
     */
    public function scopeEndingWithinDays($query, int $days)
    {
        return $query->where('end_date', '<=', now()->addDays($days)->toDateString())
                    ->where('end_date', '>=', now()->toDateString());
    }

    /**
     * Scope to get bail mobilités for a specific ops user.
     */
    public function scopeForOpsUser($query, int $opsUserId)
    {
        return $query->where('ops_user_id', $opsUserId);
    }

    /**
     * Check if the bail mobilité is ready for entry.
     */
    public function isReadyForEntry(): bool
    {
        return $this->status === 'assigned' && !is_null($this->entry_mission_id);
    }

    /**
     * Check if the bail mobilité is ready for exit.
     */
    public function isReadyForExit(): bool
    {
        return $this->status === 'in_progress' && !is_null($this->exit_mission_id);
    }

    /**
     * Get the duration of the bail mobilité in days.
     */
    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Get the remaining days until end date.
     */
    public function getRemainingDays(): int
    {
        return now()->diffInDays($this->end_date, false);
    }

    /**
     * Check if the bail mobilité needs exit reminder notification.
     */
    public function needsExitReminder(): bool
    {
        return $this->status === 'in_progress' && $this->getRemainingDays() <= 10;
    }

    /**
     * Get all incident reports for this bail mobilité.
     */
    public function incidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class);
    }

    /**
     * Get open incident reports for this bail mobilité.
     */
    public function openIncidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class)->where('status', 'open');
    }

    /**
     * Check if the bail mobilité has any open incidents.
     */
    public function hasOpenIncidents(): bool
    {
        return $this->openIncidentReports()->exists();
    }

    /**
     * Get the count of open incidents.
     */
    public function getOpenIncidentsCount(): int
    {
        return $this->openIncidentReports()->count();
    }
}