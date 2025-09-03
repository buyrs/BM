<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheService;

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

    /**
     * Optimized query scopes with eager loading
     */
    public function scopeWithRelations(Builder $query): Builder
    {
        return $query->with([
            'opsUser:id,name,email',
            'entryMission:id,scheduled_at,status,agent_id',
            'exitMission:id,scheduled_at,status,agent_id',
            'entrySignature:id,signature_type,signed_at',
            'exitSignature:id,signature_type,signed_at'
        ]);
    }

    public function scopeForKanban(Builder $query): Builder
    {
        return $query->withRelations()
                    ->withCount(['incidentReports', 'openIncidentReports'])
                    ->orderBy('created_at', 'desc');
    }

    public function scopeWithStats(Builder $query): Builder
    {
        return $query->selectRaw('
            COUNT(*) as total_bail_mobilites,
            COUNT(CASE WHEN status = "assigned" THEN 1 END) as assigned_count,
            COUNT(CASE WHEN status = "in_progress" THEN 1 END) as in_progress_count,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_count,
            COUNT(CASE WHEN status = "incident" THEN 1 END) as incident_count,
            AVG(DATEDIFF(end_date, start_date)) as avg_duration_days
        ');
    }

    public function scopeNeedingAttention(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->where('status', 'incident')
              ->orWhere(function($subQ) {
                  $subQ->where('status', 'in_progress')
                       ->where('end_date', '<=', now()->addDays(10));
              });
        });
    }

    /**
     * Cached query methods
     */
    public static function getCachedByStatus(string $status): \Illuminate\Support\Collection
    {
        $cacheKey = "bail_mobilites:status:{$status}";
        
        return Cache::remember($cacheKey, CacheService::SHORT_CACHE, function() use ($status) {
            return static::where('status', $status)
                        ->withRelations()
                        ->orderBy('created_at', 'desc')
                        ->get();
        });
    }

    public static function getCachedKanbanData(): array
    {
        $cacheKey = "bail_mobilites:kanban";
        
        return Cache::remember($cacheKey, CacheService::SHORT_CACHE, function() {
            $statuses = ['assigned', 'in_progress', 'completed', 'incident'];
            $kanbanData = [];
            
            foreach ($statuses as $status) {
                $kanbanData[$status] = static::where('status', $status)
                                            ->forKanban()
                                            ->get();
            }
            
            return $kanbanData;
        });
    }

    public static function getCachedStats(): array
    {
        $cacheKey = "bail_mobilites:stats";
        
        return Cache::remember($cacheKey, CacheService::MEDIUM_CACHE, function() {
            $stats = static::withStats()->first();
            
            return [
                'total' => $stats->total_bail_mobilites ?? 0,
                'assigned' => $stats->assigned_count ?? 0,
                'in_progress' => $stats->in_progress_count ?? 0,
                'completed' => $stats->completed_count ?? 0,
                'incident' => $stats->incident_count ?? 0,
                'avg_duration' => round($stats->avg_duration_days ?? 0, 1),
                'completion_rate' => $stats->total_bail_mobilites > 0 
                    ? round(($stats->completed_count / $stats->total_bail_mobilites) * 100, 2) 
                    : 0
            ];
        });
    }

    public static function getCachedNeedingAttention(): \Illuminate\Support\Collection
    {
        $cacheKey = "bail_mobilites:needing_attention";
        
        return Cache::remember($cacheKey, CacheService::SHORT_CACHE, function() {
            return static::needingAttention()
                        ->withRelations()
                        ->orderBy('end_date', 'asc')
                        ->get();
        });
    }

    /**
     * Boot method to handle cache invalidation
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($bailMobilite) {
            static::clearRelatedCaches($bailMobilite);
        });

        static::updated(function ($bailMobilite) {
            static::clearRelatedCaches($bailMobilite);
        });

        static::deleted(function ($bailMobilite) {
            static::clearRelatedCaches($bailMobilite);
        });
    }

    /**
     * Clear related caches
     */
    protected static function clearRelatedCaches($bailMobilite): void
    {
        $cacheService = app(CacheService::class);
        
        // Clear bail mobilité specific caches
        $cacheService->invalidateBailMobiliteCache($bailMobilite->id);
        
        // Clear status-specific caches
        Cache::forget("bail_mobilites:status:{$bailMobilite->status}");
        if ($bailMobilite->getOriginal('status') && $bailMobilite->getOriginal('status') !== $bailMobilite->status) {
            Cache::forget("bail_mobilites:status:{$bailMobilite->getOriginal('status')}");
        }
        
        // Clear general caches
        Cache::forget('bail_mobilites:kanban');
        Cache::forget('bail_mobilites:stats');
        Cache::forget('bail_mobilites:needing_attention');
        
        // Clear dashboard caches
        $cacheService->invalidateStatsCache();
    }
}