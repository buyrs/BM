<?php

namespace App\Http\Controllers\Api;

use App\Models\Property;
use App\Models\Mission;
use App\Services\ConditionTrackingService;
use App\Services\SmartChecklistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyConditionController extends BaseApiController
{
    public function __construct(
        private ConditionTrackingService $conditionService,
        private SmartChecklistService $checklistService
    ) {}

    /**
     * Get condition history for a property.
     */
    public function history(Request $request, int $propertyId): JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $filters = $this->getFilterParams($request, ['area']);

            $history = $this->conditionService->getPropertyHistory($property);

            if (!empty($filters['area'])) {
                $history = $history->filter(fn($c) => $c->area === $filters['area']);
            }

            $data = $history->map(function ($condition) {
                return [
                    'id' => $condition->id,
                    'area' => $condition->area,
                    'item' => $condition->item,
                    'condition' => $condition->condition,
                    'previous_condition' => $condition->previous_condition,
                    'notes' => $condition->notes,
                    'photo_path' => $condition->photo_path,
                    'recorded_at' => $condition->recorded_at,
                    'mission' => $condition->mission ? [
                        'id' => $condition->mission->id,
                        'title' => $condition->mission->title,
                    ] : null,
                    'hasDegraded' => $condition->hasDegraded(),
                ];
            });

            return $this->success($data, 'Condition history retrieved');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve condition history');
        }
    }

    /**
     * Get current condition score for a property.
     */
    public function score(Request $request, int $propertyId): JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $score = $this->conditionService->getPropertyScore($property);

            return $this->success($score, 'Condition score retrieved');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve condition score');
        }
    }

    /**
     * Record a new condition.
     */
    public function record(Request $request, int $propertyId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            $property = Property::findOrFail($propertyId);

            $validated = $request->validate([
                'area' => ['required', 'string', 'max:100'],
                'item' => ['required', 'string', 'max:200'],
                'condition' => ['required', 'in:excellent,good,fair,poor,critical'],
                'mission_id' => ['nullable', 'exists:missions,id'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'photo_path' => ['nullable', 'string'],
            ]);

            $mission = isset($validated['mission_id']) 
                ? Mission::find($validated['mission_id']) 
                : null;

            $condition = $this->conditionService->recordCondition(
                property: $property,
                area: $validated['area'],
                item: $validated['item'],
                condition: $validated['condition'],
                mission: $mission,
                recordedBy: $user->id,
                notes: $validated['notes'] ?? null,
                photoPath: $validated['photo_path'] ?? null
            );

            return $this->success($condition, 'Condition recorded');

        } catch (\Exception $e) {
            return $this->serverError('Failed to record condition');
        }
    }

    /**
     * Get degraded conditions.
     */
    public function degraded(Request $request, int $propertyId): JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $degraded = $this->conditionService->getDegradedConditions($property);

            return $this->success($degraded, 'Degraded conditions retrieved');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve degraded conditions');
        }
    }

    /**
     * Generate smart checklist for a mission.
     */
    public function generateChecklist(Request $request, int $missionId): JsonResponse
    {
        try {
            $mission = Mission::with('property')->findOrFail($missionId);
            $checklist = $this->checklistService->generateForMission($mission);

            return $this->success([
                'checklist_id' => $checklist->id,
                'items_count' => $checklist->items()->count(),
            ], 'Smart checklist generated');

        } catch (\Exception $e) {
            return $this->serverError('Failed to generate checklist');
        }
    }

    /**
     * Get checklist suggestions based on property history.
     */
    public function suggestions(Request $request, int $propertyId): JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $suggestions = $this->checklistService->suggestAdditionalItems($property);

            return $this->success($suggestions, 'Suggestions retrieved');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve suggestions');
        }
    }

    /**
     * Compare conditions between two inspections.
     */
    public function compare(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'before_mission_id' => ['required', 'exists:missions,id'],
                'after_mission_id' => ['required', 'exists:missions,id'],
            ]);

            $before = Mission::findOrFail($validated['before_mission_id']);
            $after = Mission::findOrFail($validated['after_mission_id']);

            $comparison = $this->conditionService->compareInspections($before, $after);

            return $this->success($comparison, 'Comparison completed');

        } catch (\Exception $e) {
            return $this->serverError('Failed to compare inspections');
        }
    }
}
