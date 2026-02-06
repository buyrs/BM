<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyCondition extends Model
{
    protected $fillable = [
        'property_id',
        'mission_id',
        'area',              // kitchen, bathroom, bedroom, etc.
        'item',              // specific item like 'sink', 'wall', etc.
        'condition',         // good, fair, poor, critical
        'previous_condition',
        'notes',
        'photo_path',
        'recorded_by',       // checker_id
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    /**
     * Condition levels with severity scoring.
     */
    public const CONDITIONS = [
        'excellent' => ['score' => 100, 'color' => 'success', 'label' => 'Excellent'],
        'good' => ['score' => 80, 'color' => 'success', 'label' => 'Good'],
        'fair' => ['score' => 60, 'color' => 'warning', 'label' => 'Fair'],
        'poor' => ['score' => 40, 'color' => 'orange', 'label' => 'Poor'],
        'critical' => ['score' => 20, 'color' => 'danger', 'label' => 'Critical'],
    ];

    /**
     * Get the property.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the mission that recorded this condition.
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    /**
     * Get the checker who recorded this.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope: by area.
     */
    public function scopeForArea($query, string $area)
    {
        return $query->where('area', $area);
    }

    /**
     * Scope: conditions that degraded.
     */
    public function scopeDegraded($query)
    {
        return $query->whereColumn('condition', '!=', 'previous_condition')
            ->whereRaw("FIELD(condition, 'critical', 'poor', 'fair', 'good', 'excellent') < FIELD(previous_condition, 'critical', 'poor', 'fair', 'good', 'excellent')");
    }

    /**
     * Check if condition has degraded.
     */
    public function hasDegraded(): bool
    {
        if (!$this->previous_condition) {
            return false;
        }

        $currentScore = self::CONDITIONS[$this->condition]['score'] ?? 0;
        $previousScore = self::CONDITIONS[$this->previous_condition]['score'] ?? 0;

        return $currentScore < $previousScore;
    }

    /**
     * Get condition severity score.
     */
    public function getScoreAttribute(): int
    {
        return self::CONDITIONS[$this->condition]['score'] ?? 0;
    }

    /**
     * Get condition color class.
     */
    public function getColorAttribute(): string
    {
        return self::CONDITIONS[$this->condition]['color'] ?? 'secondary';
    }
}
