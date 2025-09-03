<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheService;

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
    ];

    /**
     * Boot the model and add event listeners for calendar synchronization.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear calendar cache when mission is updated
        static::updated(function ($mission) {
            static::clearCalendarCache($mission);
            static::broadcastCalendarUpdate($mission, 'updated');
        });

        // Clear calendar cache when mission is created
        static::created(function ($mission) {
            static::clearCalendarCache($mission);
            static::broadcastCalendarUpdate($mission, 'created');
        });

        // Clear calendar cache when mission is deleted
        static::deleted(function ($mission) {
            static::clearCalendarCache($mission);
            static::broadcastCalendarUpdate($mission, 'deleted');
        });
    }

    /**
     * Clear calendar-related cache entries.
     */
    protected static function clearCalendarCache($mission)
    {
        $date = $mission->scheduled_at ? $mission->scheduled_at->format('Y-m-d') : now()->format('Y-m-d');
        $month = $mission->scheduled_at ? $mission->scheduled_at->format('Y-m') : now()->format('Y-m');
        
        // Clear specific date cache
        Cache::forget("calendar_missions_{$date}");
        
        // Clear month cache
        Cache::forget("calendar_missions_month_{$month}");
        
        // Clear week cache
        $weekStart = $mission->scheduled_at ? $mission->scheduled_at->startOfWeek()->format('Y-m-d') : now()->startOfWeek()->format('Y-m-d');
        Cache::forget("calendar_missions_week_{$weekStart}");
        
        // Clear general calendar cache
        Cache::forget('calendar_missions_all');
        Cache::forget('calendar_stats');
    }

    /**
     * Broadcast calendar update event.
     */
    protected static function broadcastCalendarUpdate($mission, $action)
    {
        // This could be extended to use Laravel Broadcasting for real-time updates
        // For now, we'll just log the event
        \Log::info("Calendar sync: Mission {$mission->id} {$action}", [
            'mission_id' => $mission->id,
            'action' => $action,
            'status' => $mission->status,
            'scheduled_at' => $mission->scheduled_at?->toISOString(),
            'bail_mobilite_id' => $mission->bail_mobilite_id,
        ]);
    }

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
     * Get the notifications associated with this mission.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'mission_id');
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

    /**
     * Optimized query scopes with eager loading
     */
    public function scopeWithRelations(Builder $query): Builder
    {
        return $query->with([
            'agent:id,name,email',
            'bailMobilite:id,tenant_name,address,status',
            'opsAssignedBy:id,name,email',
            'checklist:id,mission_id,status'
        ]);
    }

    public function scopeForDashboard(Builder $query, int $userId, string $role): Builder
    {
        $query = $query->withRelations();
        
        if ($role === 'checker') {
            $query->where('agent_id', $userId);
        } elseif ($role === 'ops') {
            $query->where('ops_assigned_by', $userId);
        }
        
        return $query->orderBy('scheduled_at', 'desc');
    }

    public function scopeUpcoming(Builder $query, int $days = 7): Builder
    {
        return $query->where('scheduled_at', '>=', now())
                    ->where('scheduled_at', '<=', now()->addDays($days))
                    ->whereIn('status', ['assigned', 'in_progress']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('scheduled_at', '<', now())
                    ->whereIn('status', ['assigned', 'in_progress']);
    }

    public function scopeByDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('scheduled_at', [$startDate, $endDate]);
    }

    public function scopeWithStats(Builder $query): Builder
    {
        return $query->selectRaw('
            COUNT(*) as total_missions,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_missions,
            COUNT(CASE WHEN status = "in_progress" THEN 1 END) as in_progress_missions,
            COUNT(CASE WHEN status = "assigned" THEN 1 END) as assigned_missions,
            COUNT(CASE WHEN scheduled_at < NOW() AND status != "completed" THEN 1 END) as overdue_missions
        ');
    }

    /**
     * Cached query methods
     */
    public static function getCachedMissionsByAgent(int $agentId): \Illuminate\Support\Collection
    {
        $cacheService = app(CacheService::class);
        
        return $cacheService->getMissionsByAgent($agentId, function() use ($agentId) {
            return static::where('agent_id', $agentId)
                        ->withRelations()
                        ->orderBy('scheduled_at', 'desc')
                        ->get();
        });
    }

    public static function getCachedUpcomingMissions(int $days = 7): \Illuminate\Support\Collection
    {
        $cacheKey = "missions:upcoming:{$days}";
        
        return Cache::remember($cacheKey, CacheService::SHORT_CACHE, function() use ($days) {
            return static::upcoming($days)
                        ->withRelations()
                        ->orderBy('scheduled_at', 'asc')
                        ->get();
        });
    }

    public static function getCachedOverdueMissions(): \Illuminate\Support\Collection
    {
        $cacheKey = "missions:overdue";
        
        return Cache::remember($cacheKey, CacheService::SHORT_CACHE, function() {
            return static::overdue()
                        ->withRelations()
                        ->orderBy('scheduled_at', 'asc')
                        ->get();
        });
    }

    public static function getCachedMissionStats(): array
    {
        $cacheKey = "missions:stats";
        
        return Cache::remember($cacheKey, CacheService::MEDIUM_CACHE, function() {
            $stats = static::withStats()->first();
            
            return [
                'total' => $stats->total_missions ?? 0,
                'completed' => $stats->completed_missions ?? 0,
                'in_progress' => $stats->in_progress_missions ?? 0,
                'assigned' => $stats->assigned_missions ?? 0,
                'overdue' => $stats->overdue_missions ?? 0,
                'completion_rate' => $stats->total_missions > 0 
                    ? round(($stats->completed_missions / $stats->total_missions) * 100, 2) 
                    : 0
            ];
        });
    }

    /**
     * Clear related caches when mission is updated
     */
    protected static function clearRelatedCaches($mission): void
    {
        $cacheService = app(CacheService::class);
        
        // Clear mission-specific caches
        $cacheService->invalidateMissionCache($mission->id);
        
        // Clear agent-specific caches
        if ($mission->agent_id) {
            Cache::forget("missions:agent:{$mission->agent_id}");
        }
        
        // Clear general mission caches
        Cache::forget('missions:upcoming:7');
        Cache::forget('missions:overdue');
        Cache::forget('missions:stats');
        
        // Clear calendar caches
        if ($mission->scheduled_at) {
            $cacheService->invalidateCalendarCache($mission->scheduled_at->format('Y-m-d'));
        }
    }
}      