<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'scheduled_at',
        'address',
        'tenant_name',
        'tenant_phone',
        'tenant_email',
        'notes',
        'agent_id',
        'status',
        'bail_mobilite_id',
        'mission_type',
        'ops_assigned_by',
        'scheduled_time'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'scheduled_time' => 'datetime:H:i'
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get the checklist associated with this mission.
     */
    public function checklist(): HasOne
    {
        return $this->hasOne(Checklist::class);
    }

    /**
     * Get the bail mobilité associated with this mission.
     */
    public function bailMobilite(): BelongsTo
    {
        return $this->belongsTo(BailMobilite::class);
    }

    /**
     * Get the ops user who assigned this mission.
     */
    public function opsAssignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ops_assigned_by');
    }

    /**
     * Get the bail mobilité where this is the entry mission.
     */
    public function bailMobiliteAsEntry(): HasOne
    {
        return $this->hasOne(BailMobilite::class, 'entry_mission_id');
    }

    /**
     * Get the bail mobilité where this is the exit mission.
     */
    public function bailMobiliteAsExit(): HasOne
    {
        return $this->hasOne(BailMobilite::class, 'exit_mission_id');
    }

    public function scopeUnassigned($query)
    {
        return $query->where('status', 'unassigned');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope to get entry missions.
     */
    public function scopeEntry($query)
    {
        return $query->where('mission_type', 'entry');
    }

    /**
     * Scope to get exit missions.
     */
    public function scopeExit($query)
    {
        return $query->where('mission_type', 'exit');
    }

    /**
     * Scope to get missions for a specific bail mobilité.
     */
    public function scopeForBailMobilite($query, int $bailMobiliteId)
    {
        return $query->where('bail_mobilite_id', $bailMobiliteId);
    }

    /**
     * Scope to get missions assigned by a specific ops user.
     */
    public function scopeAssignedByOps($query, int $opsUserId)
    {
        return $query->where('ops_assigned_by', $opsUserId);
    }

    /**
     * Check if this is a bail mobilité mission.
     */
    public function isBailMobiliteMission(): bool
    {
        return !is_null($this->bail_mobilite_id);
    }

    /**
     * Check if this is an entry mission.
     */
    public function isEntryMission(): bool
    {
        return $this->mission_type === 'entry';
    }

    /**
     * Check if this is an exit mission.
     */
    public function isExitMission(): bool
    {
        return $this->mission_type === 'exit';
    }

    /**
     * Get the full scheduled datetime combining date and time.
     */
    public function getFullScheduledDateTime(): ?Carbon
    {
        if (!$this->scheduled_at || !$this->scheduled_time) {
            return $this->scheduled_at;
        }

        return $this->scheduled_at->setTimeFromTimeString($this->scheduled_time);
    }
}      