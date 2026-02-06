<?php

namespace App\Http\Controllers\Api;

use App\Services\SmartAssignmentService;
use App\Services\RouteOptimizationService;
use App\Models\Mission;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OpsController extends BaseApiController
{
    public function __construct(
        private SmartAssignmentService $assignmentService,
        private RouteOptimizationService $routeService
    ) {}

    /**
     * Get checker workloads.
     */
    public function workloads(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$this->checkRole($request, ['admin', 'ops'])) {
                return $this->forbidden('Insufficient permissions');
            }

            $date = $request->has('date') 
                ? Carbon::parse($request->date) 
                : Carbon::today();

            $workloads = $this->assignmentService->getCheckerWorkloads($date);

            return $this->success($workloads, 'Workloads retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve workloads');
        }
    }

    /**
     * Get smart assignment suggestions for a property.
     */
    public function suggestChecker(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$this->checkRole($request, ['admin', 'ops'])) {
                return $this->forbidden('Insufficient permissions');
            }

            $validated = $request->validate([
                'property_id' => ['required', 'exists:properties,id'],
                'preferred_date' => ['nullable', 'date'],
            ]);

            $property = Property::findOrFail($validated['property_id']);
            $preferredDate = isset($validated['preferred_date']) 
                ? Carbon::parse($validated['preferred_date']) 
                : null;

            $result = $this->assignmentService->findBestChecker($property, $preferredDate);

            return $this->success($result, 'Suggestions retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get checker suggestions');
        }
    }

    /**
     * Bulk assign checkers optimally.
     */
    public function bulkAssign(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$this->checkRole($request, ['admin', 'ops'])) {
                return $this->forbidden('Insufficient permissions');
            }

            $validated = $request->validate([
                'mission_ids' => ['required', 'array'],
                'mission_ids.*' => ['exists:missions,id'],
            ]);

            $missions = Mission::whereIn('id', $validated['mission_ids'])
                ->with('property')
                ->get();

            $result = $this->assignmentService->bulkOptimalAssignment($missions);

            // Apply assignments if requested
            if ($request->boolean('apply', false)) {
                foreach ($result['assignments'] as $missionId => $assignment) {
                    if ($assignment['success']) {
                        Mission::where('id', $missionId)
                            ->update(['checker_id' => $assignment['checker_id']]);
                    }
                }
            }

            return $this->success($result, 'Bulk assignment completed');

        } catch (\Exception $e) {
            return $this->serverError('Failed to perform bulk assignment');
        }
    }

    /**
     * Optimize route for missions.
     */
    public function optimizeRoute(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            $validated = $request->validate([
                'mission_ids' => ['required', 'array'],
                'mission_ids.*' => ['exists:missions,id'],
                'start_lat' => ['nullable', 'numeric'],
                'start_lng' => ['nullable', 'numeric'],
                'start_address' => ['nullable', 'string'],
            ]);

            $missions = Mission::whereIn('id', $validated['mission_ids'])
                ->with('property')
                ->get();

            $startPoint = null;
            if (isset($validated['start_lat']) && isset($validated['start_lng'])) {
                $startPoint = [
                    'lat' => $validated['start_lat'],
                    'lng' => $validated['start_lng'],
                    'address' => $validated['start_address'] ?? 'Starting Point',
                ];
            }

            $result = $this->routeService->optimizeRoute($missions, $startPoint);

            return $this->success($result, 'Route optimized successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to optimize route');
        }
    }

    /**
     * Compare original vs optimized route.
     */
    public function compareRoutes(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            $validated = $request->validate([
                'mission_ids' => ['required', 'array'],
                'mission_ids.*' => ['exists:missions,id'],
                'start_lat' => ['nullable', 'numeric'],
                'start_lng' => ['nullable', 'numeric'],
            ]);

            $missions = Mission::whereIn('id', $validated['mission_ids'])
                ->with('property')
                ->get();

            $startPoint = null;
            if (isset($validated['start_lat']) && isset($validated['start_lng'])) {
                $startPoint = [
                    'lat' => $validated['start_lat'],
                    'lng' => $validated['start_lng'],
                ];
            }

            $result = $this->routeService->compareRoutes($missions, $startPoint);

            return $this->success($result, 'Route comparison completed');

        } catch (\Exception $e) {
            return $this->serverError('Failed to compare routes');
        }
    }

    /**
     * Get daily route for a checker.
     */
    public function dailyRoute(Request $request, int $checkerId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            // Checkers can view their own route, ops/admin can view any
            if ($user->role === 'checker' && $user->id !== $checkerId) {
                return $this->forbidden('You can only view your own route');
            }

            $date = $request->has('date') 
                ? Carbon::parse($request->date) 
                : Carbon::today();

            $result = $this->routeService->getDailyRoute($checkerId, $date);

            return $this->success($result, 'Daily route retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get daily route');
        }
    }
}
