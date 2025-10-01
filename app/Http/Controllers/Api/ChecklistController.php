<?php

namespace App\Http\Controllers\Api;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistController extends BaseApiController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    /**
     * Get all checklists with pagination and filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            // Get pagination and sorting parameters
            $paginationParams = $this->getPaginationParams($request);
            $sortingParams = $this->getSortingParams($request, ['created_at', 'updated_at']);
            $filters = $this->getFilterParams($request, ['mission_id', 'completed']);

            // Build query based on user role
            $query = Checklist::with(['mission', 'mission.property', 'items']);

            // Role-based filtering
            if ($user->role === 'checker') {
                $query->whereHas('mission', function ($q) use ($user) {
                    $q->where('checker_id', $user->id);
                });
            } elseif ($user->role === 'ops') {
                $query->whereHas('mission', function ($q) use ($user) {
                    $q->where('ops_id', $user->id);
                });
            }

            // Apply filters
            if (!empty($filters['mission_id'])) {
                $query->where('mission_id', $filters['mission_id']);
            }

            if (isset($filters['completed'])) {
                $completed = filter_var($filters['completed'], FILTER_VALIDATE_BOOLEAN);
                if ($completed) {
                    $query->whereNotNull('completed_at');
                } else {
                    $query->whereNull('completed_at');
                }
            }

            // Apply sorting
            $query->orderBy($sortingParams['sort_by'], $sortingParams['sort_order']);

            // Get paginated results
            $checklists = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedChecklists = $checklists->getCollection()->map(function ($checklist) {
                return $this->transformChecklist($checklist);
            });

            $checklists->setCollection($transformedChecklists);

            return $this->paginated($checklists, 'Checklists retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve checklists');
        }
    }

    /**
     * Get a specific checklist by ID
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            $checklist = Checklist::with(['mission', 'mission.property', 'items'])->findOrFail($id);

            // Check permissions
            if ($user->role === 'checker' && $checklist->mission->checker_id !== $user->id) {
                return $this->forbidden('You can only view your own checklists');
            }

            if ($user->role === 'ops' && $checklist->mission->ops_id !== $user->id) {
                return $this->forbidden('You can only view checklists for missions assigned to you');
            }

            return $this->success([
                'checklist' => $this->transformChecklist($checklist, ['items'])
            ], 'Checklist retrieved successfully');

        } catch (\Exception $e) {
            return $this->notFound('Checklist not found');
        }
    }

    /**
     * Update checklist item completion status
     */
    public function updateItem(Request $request, int $checklistId, int $itemId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            $checklist = Checklist::with(['mission', 'items'])->findOrFail($checklistId);
            $item = $checklist->items()->findOrFail($itemId);

            // Check permissions - only checker assigned to the mission can update
            if ($user->role !== 'checker' || $checklist->mission->checker_id !== $user->id) {
                return $this->forbidden('You can only update items in your own checklists');
            }

            // Validate request
            $validated = $request->validate([
                'is_completed' => ['required', 'boolean'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'photo_path' => ['nullable', 'string', 'max:500'],
            ]);

            // Store original data for audit
            $originalData = $item->toArray();

            // Update item
            $item->update($validated);

            // Check if all items are completed and update checklist
            $this->updateChecklistCompletion($checklist);

            // Log the action
            $this->auditLogger->log('checklist_item_updated', $user, [
                'checklist_id' => $checklist->id,
                'item_id' => $item->id,
                'mission_id' => $checklist->mission_id,
                'changes' => array_diff_assoc($validated, $originalData),
            ]);

            return $this->success([
                'item' => $this->transformChecklistItem($item),
                'checklist_completed' => !is_null($checklist->fresh()->completed_at),
            ], 'Checklist item updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->notFound('Checklist or item not found');
        }
    }

    /**
     * Mark entire checklist as completed
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            $checklist = Checklist::with(['mission', 'items'])->findOrFail($id);

            // Check permissions
            if ($user->role !== 'checker' || $checklist->mission->checker_id !== $user->id) {
                return $this->forbidden('You can only complete your own checklists');
            }

            // Check if all required items are completed
            $incompleteItems = $checklist->items()->where('is_completed', false)->count();
            
            if ($incompleteItems > 0) {
                return $this->error('Cannot complete checklist with incomplete items', 422);
            }

            // Mark checklist as completed
            $checklist->update([
                'completed_at' => now(),
                'completed_by' => $user->id,
            ]);

            // Update mission status if needed
            if ($checklist->mission->status === 'pending') {
                $checklist->mission->update(['status' => 'completed']);
            }

            // Log the action
            $this->auditLogger->log('checklist_completed', $user, [
                'checklist_id' => $checklist->id,
                'mission_id' => $checklist->mission_id,
            ]);

            return $this->success([
                'checklist' => $this->transformChecklist($checklist, ['items'])
            ], 'Checklist completed successfully');

        } catch (\Exception $e) {
            return $this->notFound('Checklist not found');
        }
    }

    /**
     * Get checklist statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            $query = Checklist::query();

            // Filter by user role
            if ($user->role === 'checker') {
                $query->whereHas('mission', function ($q) use ($user) {
                    $q->where('checker_id', $user->id);
                });
            } elseif ($user->role === 'ops') {
                $query->whereHas('mission', function ($q) use ($user) {
                    $q->where('ops_id', $user->id);
                });
            }

            $stats = [
                'total_checklists' => $query->count(),
                'completed_checklists' => (clone $query)->whereNotNull('completed_at')->count(),
                'pending_checklists' => (clone $query)->whereNull('completed_at')->count(),
                'completion_rate' => 0,
            ];

            // Calculate completion rate
            if ($stats['total_checklists'] > 0) {
                $stats['completion_rate'] = round(($stats['completed_checklists'] / $stats['total_checklists']) * 100, 2);
            }

            return $this->success($stats, 'Checklist statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve checklist statistics');
        }
    }

    /**
     * Update checklist completion status based on items
     */
    private function updateChecklistCompletion(Checklist $checklist): void
    {
        $totalItems = $checklist->items()->count();
        $completedItems = $checklist->items()->where('is_completed', true)->count();

        if ($totalItems > 0 && $completedItems === $totalItems && is_null($checklist->completed_at)) {
            $checklist->update([
                'completed_at' => now(),
                'completed_by' => auth()->id(),
            ]);
        } elseif ($completedItems < $totalItems && !is_null($checklist->completed_at)) {
            $checklist->update([
                'completed_at' => null,
                'completed_by' => null,
            ]);
        }
    }

    /**
     * Transform checklist for API response
     */
    private function transformChecklist(Checklist $checklist, array $relations = []): array
    {
        $data = [
            'id' => $checklist->id,
            'mission_id' => $checklist->mission_id,
            'completed_at' => $checklist->completed_at,
            'completed_by' => $checklist->completed_by,
            'created_at' => $checklist->created_at,
            'updated_at' => $checklist->updated_at,
            'mission' => $checklist->mission ? [
                'id' => $checklist->mission->id,
                'title' => $checklist->mission->title,
                'status' => $checklist->mission->status,
                'property' => $checklist->mission->property ? [
                    'id' => $checklist->mission->property->id,
                    'name' => $checklist->mission->property->name,
                    'address' => $checklist->mission->property->address,
                ] : null,
            ] : null,
        ];

        // Include items if requested
        if (in_array('items', $relations) && $checklist->relationLoaded('items')) {
            $data['items'] = $checklist->items->map(function ($item) {
                return $this->transformChecklistItem($item);
            });
        }

        return $data;
    }

    /**
     * Transform checklist item for API response
     */
    private function transformChecklistItem(ChecklistItem $item): array
    {
        return [
            'id' => $item->id,
            'description' => $item->description,
            'is_completed' => $item->is_completed,
            'requires_photo' => $item->requires_photo,
            'photo_path' => $item->photo_path,
            'notes' => $item->notes,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }
}