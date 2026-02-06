<?php

namespace App\Services;

use App\Models\User;
use App\Models\Mission;
use App\Models\Property;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SmartAssignmentService extends BaseService
{
    /**
     * Default parameters for assignment scoring.
     */
    private array $weights = [
        'distance' => 40,      // Proximity to property
        'workload' => 30,      // Current assignments
        'availability' => 20,  // Schedule availability
        'experience' => 10,    // Past inspections at similar properties
    ];

    /**
     * Find the best checker for a mission based on multiple factors.
     */
    public function findBestChecker(Property $property, ?Carbon $preferredDate = null): array
    {
        $checkers = User::where('role', 'checker')
            ->where('status', 'active')
            ->get();

        if ($checkers->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No active checkers available',
                'suggestions' => [],
            ];
        }

        $scoredCheckers = $checkers->map(function ($checker) use ($property, $preferredDate) {
            $scores = $this->calculateCheckerScores($checker, $property, $preferredDate);
            
            return [
                'checker' => $checker,
                'scores' => $scores,
                'total_score' => $this->calculateTotalScore($scores),
            ];
        });

        // Sort by total score descending
        $ranked = $scoredCheckers->sortByDesc('total_score')->values();

        return [
            'success' => true,
            'recommended' => $ranked->first(),
            'alternatives' => $ranked->slice(1, 4)->values(),
            'all_scored' => $ranked,
        ];
    }

    /**
     * Calculate individual scores for a checker.
     */
    private function calculateCheckerScores(User $checker, Property $property, ?Carbon $date): array
    {
        return [
            'distance' => $this->calculateDistanceScore($checker, $property),
            'workload' => $this->calculateWorkloadScore($checker, $date),
            'availability' => $this->calculateAvailabilityScore($checker, $date),
            'experience' => $this->calculateExperienceScore($checker, $property),
        ];
    }

    /**
     * Calculate distance-based score.
     */
    private function calculateDistanceScore(User $checker, Property $property): float
    {
        // Get checker's base location from profile
        $checkerLat = $checker->profile['latitude'] ?? null;
        $checkerLng = $checker->profile['longitude'] ?? null;
        
        $propertyLat = $property->latitude ?? null;
        $propertyLng = $property->longitude ?? null;

        if (!$checkerLat || !$checkerLng || !$propertyLat || !$propertyLng) {
            return 50; // Default middle score if no location data
        }

        $distance = $this->calculateDistance($checkerLat, $checkerLng, $propertyLat, $propertyLng);

        // Score decreases with distance
        // 0-5km = 100, 5-15km = 80, 15-30km = 60, 30-50km = 40, 50+km = 20
        if ($distance <= 5) return 100;
        if ($distance <= 15) return 80;
        if ($distance <= 30) return 60;
        if ($distance <= 50) return 40;
        return 20;
    }

    /**
     * Calculate workload-based score.
     */
    private function calculateWorkloadScore(User $checker, ?Carbon $date): float
    {
        $targetDate = $date ?? Carbon::now();
        
        // Count missions for the target day
        $dailyMissions = Mission::where('checker_id', $checker->id)
            ->whereDate('due_date', $targetDate)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        // Count weekly missions
        $weeklyMissions = Mission::where('checker_id', $checker->id)
            ->whereBetween('due_date', [
                $targetDate->copy()->startOfWeek(),
                $targetDate->copy()->endOfWeek()
            ])
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        // Ideal: 3-4 daily, 15-20 weekly
        $dailyScore = max(0, 100 - ($dailyMissions * 20)); // Each mission reduces score
        $weeklyScore = max(0, 100 - ($weeklyMissions * 5));

        return ($dailyScore * 0.7) + ($weeklyScore * 0.3);
    }

    /**
     * Calculate availability-based score.
     */
    private function calculateAvailabilityScore(User $checker, ?Carbon $date): float
    {
        $targetDate = $date ?? Carbon::now();
        
        // Check if checker has set availability
        $schedule = $checker->profile['availability'] ?? [];
        $dayOfWeek = strtolower($targetDate->format('l'));
        
        if (empty($schedule)) {
            return 70; // Default if no schedule set
        }

        if (isset($schedule[$dayOfWeek])) {
            $daySchedule = $schedule[$dayOfWeek];
            
            if ($daySchedule === 'off') {
                return 0;
            }
            
            if ($daySchedule === 'available') {
                return 100;
            }
            
            // Partial availability
            if (is_array($daySchedule) && isset($daySchedule['hours'])) {
                $availableHours = $daySchedule['hours'];
                return min(100, $availableHours * 12.5); // 8 hours = 100
            }
        }

        return 50; // Default for undefined days
    }

    /**
     * Calculate experience-based score.
     */
    private function calculateExperienceScore(User $checker, Property $property): float
    {
        // Check previous missions at this property
        $propertyMissions = Mission::where('checker_id', $checker->id)
            ->where('property_address', $property->property_address)
            ->where('status', 'completed')
            ->count();

        // Previous experience at this property is valuable
        if ($propertyMissions >= 3) return 100;
        if ($propertyMissions >= 2) return 80;
        if ($propertyMissions >= 1) return 60;

        // Check similar properties in same area
        $areaMissions = Mission::where('checker_id', $checker->id)
            ->where('status', 'completed')
            ->whereHas('property', function ($q) use ($property) {
                $q->where('city', $property->city);
            })
            ->count();

        if ($areaMissions >= 10) return 50;
        if ($areaMissions >= 5) return 40;
        return 30;
    }

    /**
     * Calculate total weighted score.
     */
    private function calculateTotalScore(array $scores): float
    {
        $total = 0;
        
        foreach ($scores as $key => $score) {
            $weight = $this->weights[$key] ?? 0;
            $total += ($score * $weight / 100);
        }

        return round($total, 1);
    }

    /**
     * Calculate distance between two points using Haversine formula.
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Bulk assign checkers to multiple missions optimally.
     */
    public function bulkOptimalAssignment(Collection $missions): array
    {
        $assignments = [];
        $checkerLoads = [];

        // Sort missions by priority then due date
        $sortedMissions = $missions->sortBy([
            ['priority', 'desc'],
            ['due_date', 'asc'],
        ]);

        foreach ($sortedMissions as $mission) {
            $property = $mission->property;
            
            if (!$property) {
                $assignments[$mission->id] = [
                    'success' => false,
                    'reason' => 'No property associated',
                ];
                continue;
            }

            $result = $this->findBestChecker($property, $mission->due_date ? Carbon::parse($mission->due_date) : null);

            if ($result['success'] && $result['recommended']) {
                $checker = $result['recommended']['checker'];
                
                // Track temporary loads
                $checkerLoads[$checker->id] = ($checkerLoads[$checker->id] ?? 0) + 1;

                $assignments[$mission->id] = [
                    'success' => true,
                    'checker_id' => $checker->id,
                    'checker_name' => $checker->name,
                    'score' => $result['recommended']['total_score'],
                    'scores' => $result['recommended']['scores'],
                ];
            } else {
                $assignments[$mission->id] = [
                    'success' => false,
                    'reason' => $result['message'] ?? 'No suitable checker found',
                ];
            }
        }

        return [
            'assignments' => $assignments,
            'summary' => [
                'total' => $missions->count(),
                'assigned' => collect($assignments)->where('success', true)->count(),
                'failed' => collect($assignments)->where('success', false)->count(),
            ],
        ];
    }

    /**
     * Get checker workload summary.
     */
    public function getCheckerWorkloads(?Carbon $date = null): Collection
    {
        $targetDate = $date ?? Carbon::now();

        return User::where('role', 'checker')
            ->where('status', 'active')
            ->withCount([
                'missions as today_count' => function ($q) use ($targetDate) {
                    $q->whereDate('due_date', $targetDate)
                        ->whereIn('status', ['pending', 'in_progress']);
                },
                'missions as week_count' => function ($q) use ($targetDate) {
                    $q->whereBetween('due_date', [
                        $targetDate->copy()->startOfWeek(),
                        $targetDate->copy()->endOfWeek(),
                    ])->whereIn('status', ['pending', 'in_progress']);
                },
                'missions as overdue_count' => function ($q) use ($targetDate) {
                    $q->where('due_date', '<', $targetDate)
                        ->whereIn('status', ['pending', 'in_progress']);
                },
            ])
            ->get()
            ->map(function ($checker) {
                $capacity = $checker->profile['daily_capacity'] ?? 5;
                
                return [
                    'id' => $checker->id,
                    'name' => $checker->name,
                    'today' => $checker->today_count,
                    'week' => $checker->week_count,
                    'overdue' => $checker->overdue_count,
                    'capacity' => $capacity,
                    'utilization' => round(($checker->today_count / $capacity) * 100, 1),
                    'status' => $this->getWorkloadStatus($checker->today_count, $capacity),
                ];
            });
    }

    /**
     * Get workload status label.
     */
    private function getWorkloadStatus(int $current, int $capacity): string
    {
        $ratio = $current / max(1, $capacity);
        
        if ($ratio < 0.5) return 'available';
        if ($ratio < 0.8) return 'moderate';
        if ($ratio < 1.0) return 'busy';
        return 'overloaded';
    }
}
