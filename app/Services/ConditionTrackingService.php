<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Mission;
use App\Models\PropertyCondition;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ConditionTrackingService extends BaseService
{
    /**
     * Record a condition for a property item.
     */
    public function recordCondition(
        Property $property,
        string $area,
        string $item,
        string $condition,
        ?Mission $mission = null,
        ?int $recordedBy = null,
        ?string $notes = null,
        ?string $photoPath = null
    ): PropertyCondition {
        // Get previous condition for this item
        $previous = PropertyCondition::where('property_id', $property->id)
            ->where('area', $area)
            ->where('item', $item)
            ->latest('recorded_at')
            ->first();

        return PropertyCondition::create([
            'property_id' => $property->id,
            'mission_id' => $mission?->id,
            'area' => $area,
            'item' => $item,
            'condition' => $condition,
            'previous_condition' => $previous?->condition,
            'notes' => $notes,
            'photo_path' => $photoPath,
            'recorded_by' => $recordedBy,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Get condition history for a property.
     */
    public function getPropertyHistory(Property $property, int $limit = 50): Collection
    {
        return PropertyCondition::where('property_id', $property->id)
            ->with(['mission:id,title,completed_at', 'recordedBy:id,name'])
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get current conditions for all items in a property.
     */
    public function getCurrentConditions(Property $property): Collection
    {
        return PropertyCondition::where('property_id', $property->id)
            ->select('property_conditions.*')
            ->join(
                \DB::raw('(SELECT area, item, MAX(recorded_at) as max_date FROM property_conditions WHERE property_id = ' . $property->id . ' GROUP BY area, item) as latest'),
                function ($join) {
                    $join->on('property_conditions.area', '=', 'latest.area')
                        ->on('property_conditions.item', '=', 'latest.item')
                        ->on('property_conditions.recorded_at', '=', 'latest.max_date');
                }
            )
            ->orderBy('area')
            ->orderBy('item')
            ->get();
    }

    /**
     * Get property condition summary/score.
     */
    public function getPropertyScore(Property $property): array
    {
        $conditions = $this->getCurrentConditions($property);

        if ($conditions->isEmpty()) {
            return [
                'score' => null,
                'total_items' => 0,
                'by_condition' => [],
            ];
        }

        $totalScore = 0;
        $byCondition = [];

        foreach ($conditions as $condition) {
            $totalScore += $condition->score;
            $byCondition[$condition->condition] = ($byCondition[$condition->condition] ?? 0) + 1;
        }

        return [
            'score' => round($totalScore / $conditions->count()),
            'total_items' => $conditions->count(),
            'by_condition' => $byCondition,
        ];
    }

    /**
     * Get degraded conditions since a date.
     */
    public function getDegradedConditions(Property $property, ?Carbon $since = null): Collection
    {
        $query = PropertyCondition::where('property_id', $property->id)
            ->degraded()
            ->with(['mission:id,title']);

        if ($since) {
            $query->where('recorded_at', '>=', $since);
        }

        return $query->orderBy('recorded_at', 'desc')->get();
    }

    /**
     * Get condition timeline for a specific item.
     */
    public function getItemTimeline(Property $property, string $area, string $item): Collection
    {
        return PropertyCondition::where('property_id', $property->id)
            ->where('area', $area)
            ->where('item', $item)
            ->with(['mission:id,title,completed_at', 'recordedBy:id,name'])
            ->orderBy('recorded_at', 'desc')
            ->get();
    }

    /**
     * Compare conditions between two inspections.
     */
    public function compareInspections(Mission $before, Mission $after): array
    {
        $beforeConditions = PropertyCondition::where('mission_id', $before->id)
            ->get()
            ->keyBy(fn($c) => "{$c->area}:{$c->item}");

        $afterConditions = PropertyCondition::where('mission_id', $after->id)
            ->get()
            ->keyBy(fn($c) => "{$c->area}:{$c->item}");

        $comparison = [
            'improved' => [],
            'degraded' => [],
            'unchanged' => [],
            'new_items' => [],
        ];

        foreach ($afterConditions as $key => $after) {
            if (!isset($beforeConditions[$key])) {
                $comparison['new_items'][] = $after;
                continue;
            }

            $before = $beforeConditions[$key];
            $beforeScore = PropertyCondition::CONDITIONS[$before->condition]['score'] ?? 0;
            $afterScore = PropertyCondition::CONDITIONS[$after->condition]['score'] ?? 0;

            if ($afterScore > $beforeScore) {
                $comparison['improved'][] = [
                    'item' => $after,
                    'previous' => $before->condition,
                ];
            } elseif ($afterScore < $beforeScore) {
                $comparison['degraded'][] = [
                    'item' => $after,
                    'previous' => $before->condition,
                ];
            } else {
                $comparison['unchanged'][] = $after;
            }
        }

        return $comparison;
    }

    /**
     * Get areas needing attention (poor/critical conditions).
     */
    public function getAreasNeedingAttention(Property $property): Collection
    {
        return $this->getCurrentConditions($property)
            ->filter(fn($c) => in_array($c->condition, ['poor', 'critical']))
            ->groupBy('area');
    }
}
