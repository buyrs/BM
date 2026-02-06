<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\Property;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class RouteOptimizationService extends BaseService
{
    /**
     * Average speed in km/h for travel time calculations.
     */
    private float $averageSpeed = 30;

    /**
     * Average time per inspection in minutes.
     */
    private int $inspectionDuration = 45;

    /**
     * Optimize route for multiple missions.
     */
    public function optimizeRoute(Collection $missions, array $startPoint = null): array
    {
        if ($missions->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No missions to optimize',
            ];
        }

        // Get all properties with coordinates
        $stops = $missions->map(function ($mission) {
            $property = $mission->property;
            
            return [
                'mission_id' => $mission->id,
                'mission_title' => $mission->title,
                'property_id' => $property?->id,
                'address' => $property?->property_address ?? $mission->property_address,
                'lat' => $property?->latitude ?? null,
                'lng' => $property?->longitude ?? null,
                'priority' => $mission->priority ?? 'medium',
                'due_date' => $mission->due_date,
            ];
        })->filter(function ($stop) {
            return $stop['lat'] && $stop['lng'];
        });

        if ($stops->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No missions with valid coordinates',
                'unlocated' => $missions->pluck('id')->toArray(),
            ];
        }

        // Use nearest neighbor algorithm for route optimization
        $optimizedRoute = $this->nearestNeighborRoute($stops, $startPoint);

        // Calculate metrics
        $metrics = $this->calculateRouteMetrics($optimizedRoute);

        return [
            'success' => true,
            'route' => $optimizedRoute,
            'metrics' => $metrics,
            'schedule' => $this->generateSchedule($optimizedRoute, $metrics),
        ];
    }

    /**
     * Nearest neighbor algorithm for route optimization.
     */
    private function nearestNeighborRoute(Collection $stops, ?array $startPoint): array
    {
        $route = [];
        $remaining = $stops->values()->toArray();
        
        // Start from given point or first mission
        $currentLat = $startPoint['lat'] ?? $remaining[0]['lat'];
        $currentLng = $startPoint['lng'] ?? $remaining[0]['lng'];

        // If we have a start point, include it
        if ($startPoint) {
            $route[] = [
                'type' => 'start',
                'address' => $startPoint['address'] ?? 'Starting Point',
                'lat' => $startPoint['lat'],
                'lng' => $startPoint['lng'],
            ];
        }

        while (!empty($remaining)) {
            $nearestIndex = null;
            $nearestDistance = PHP_INT_MAX;

            // Find nearest unvisited stop
            foreach ($remaining as $index => $stop) {
                $distance = $this->calculateDistance(
                    $currentLat, $currentLng,
                    $stop['lat'], $stop['lng']
                );

                // Prioritize high priority missions slightly
                $priorityBonus = match ($stop['priority']) {
                    'urgent' => 0.7,
                    'high' => 0.8,
                    'medium' => 1.0,
                    'low' => 1.2,
                    default => 1.0,
                };

                $adjustedDistance = $distance * $priorityBonus;

                if ($adjustedDistance < $nearestDistance) {
                    $nearestDistance = $adjustedDistance;
                    $nearestIndex = $index;
                }
            }

            if ($nearestIndex !== null) {
                $stop = $remaining[$nearestIndex];
                $stop['type'] = 'mission';
                $stop['distance_from_prev'] = $this->calculateDistance(
                    $currentLat, $currentLng,
                    $stop['lat'], $stop['lng']
                );
                
                $route[] = $stop;
                $currentLat = $stop['lat'];
                $currentLng = $stop['lng'];
                
                unset($remaining[$nearestIndex]);
                $remaining = array_values($remaining);
            }
        }

        return $route;
    }

    /**
     * Calculate route metrics.
     */
    private function calculateRouteMetrics(array $route): array
    {
        $totalDistance = 0;
        $stops = 0;

        foreach ($route as $stop) {
            if (isset($stop['distance_from_prev'])) {
                $totalDistance += $stop['distance_from_prev'];
            }
            if ($stop['type'] === 'mission') {
                $stops++;
            }
        }

        $travelTime = ($totalDistance / $this->averageSpeed) * 60; // in minutes
        $inspectionTime = $stops * $this->inspectionDuration;
        $totalTime = $travelTime + $inspectionTime;

        return [
            'total_distance_km' => round($totalDistance, 1),
            'total_stops' => $stops,
            'estimated_travel_time_minutes' => round($travelTime),
            'estimated_inspection_time_minutes' => $inspectionTime,
            'total_estimated_time_minutes' => round($totalTime),
            'total_estimated_hours' => round($totalTime / 60, 1),
        ];
    }

    /**
     * Generate time-based schedule.
     */
    private function generateSchedule(array $route, array $metrics, ?Carbon $startTime = null): array
    {
        $currentTime = $startTime ?? Carbon::now()->setTime(9, 0);
        $schedule = [];

        foreach ($route as $stop) {
            $entry = [
                'type' => $stop['type'],
                'address' => $stop['address'],
                'arrival_time' => $currentTime->format('H:i'),
            ];

            if ($stop['type'] === 'mission') {
                $entry['mission_id'] = $stop['mission_id'];
                $entry['mission_title'] = $stop['mission_title'];
                $entry['departure_time'] = $currentTime->copy()
                    ->addMinutes($this->inspectionDuration)
                    ->format('H:i');
                
                // Add inspection time
                $currentTime->addMinutes($this->inspectionDuration);
            }

            // Add travel time to next stop
            if (isset($stop['distance_from_prev']) && $stop['distance_from_prev'] > 0) {
                $travelMinutes = ($stop['distance_from_prev'] / $this->averageSpeed) * 60;
                $entry['travel_minutes'] = round($travelMinutes);
            }

            $schedule[] = $entry;

            // Add travel time for next stop
            if (isset($stop['distance_from_prev'])) {
                $currentTime->addMinutes(($stop['distance_from_prev'] / $this->averageSpeed) * 60);
            }
        }

        return $schedule;
    }

    /**
     * Compare original vs optimized route.
     */
    public function compareRoutes(Collection $missions, array $startPoint = null): array
    {
        // Original order metrics
        $originalStops = $missions->map(function ($mission) {
            $property = $mission->property;
            return [
                'lat' => $property?->latitude,
                'lng' => $property?->longitude,
            ];
        })->filter(fn($s) => $s['lat'] && $s['lng'])->values();

        $originalDistance = $this->calculateTotalDistance($originalStops->toArray(), $startPoint);

        // Optimized route
        $optimized = $this->optimizeRoute($missions, $startPoint);

        if (!$optimized['success']) {
            return $optimized;
        }

        return [
            'success' => true,
            'original' => [
                'distance_km' => round($originalDistance, 1),
                'estimated_time_hours' => round(($originalDistance / $this->averageSpeed) + ($missions->count() * $this->inspectionDuration / 60), 1),
            ],
            'optimized' => [
                'distance_km' => $optimized['metrics']['total_distance_km'],
                'estimated_time_hours' => $optimized['metrics']['total_estimated_hours'],
            ],
            'savings' => [
                'distance_km' => round($originalDistance - $optimized['metrics']['total_distance_km'], 1),
                'time_minutes' => round((($originalDistance - $optimized['metrics']['total_distance_km']) / $this->averageSpeed) * 60),
                'percentage' => $originalDistance > 0 
                    ? round((1 - $optimized['metrics']['total_distance_km'] / $originalDistance) * 100, 1)
                    : 0,
            ],
            'route' => $optimized['route'],
            'schedule' => $optimized['schedule'],
        ];
    }

    /**
     * Calculate total distance for a list of stops.
     */
    private function calculateTotalDistance(array $stops, ?array $startPoint = null): float
    {
        $total = 0;
        $prevLat = $startPoint['lat'] ?? ($stops[0]['lat'] ?? 0);
        $prevLng = $startPoint['lng'] ?? ($stops[0]['lng'] ?? 0);

        foreach ($stops as $stop) {
            $total += $this->calculateDistance($prevLat, $prevLng, $stop['lat'], $stop['lng']);
            $prevLat = $stop['lat'];
            $prevLng = $stop['lng'];
        }

        return $total;
    }

    /**
     * Calculate distance between two points (Haversine formula).
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
     * Get suggested route for a checker's day.
     */
    public function getDailyRoute(int $checkerId, ?Carbon $date = null): array
    {
        $targetDate = $date ?? Carbon::today();

        $missions = Mission::where('checker_id', $checkerId)
            ->whereDate('due_date', $targetDate)
            ->whereIn('status', ['pending', 'in_progress'])
            ->with('property')
            ->get();

        if ($missions->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No missions scheduled',
                'missions' => [],
            ];
        }

        return $this->optimizeRoute($missions);
    }
}
