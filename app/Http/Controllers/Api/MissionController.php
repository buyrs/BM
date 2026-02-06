<?php

namespace App\Http\Controllers\Api;

use App\Models\Mission;
use App\Models\Property;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MissionController extends BaseApiController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    /**
     * Get all missions with pagination and filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            // Get pagination and sorting parameters
            $paginationParams = $this->getPaginationParams($request);
            $sortingParams = $this->getSortingParams($request, ['title', 'status', 'due_date', 'created_at']);
            $filters = $this->getFilterParams($request, ['status', 'property_id', 'checker_id', 'ops_id', 'search']);

            // Build query based on user role
            $query = Mission::with(['property', 'checker', 'ops', 'checklist']);

            // Role-based filtering
            if ($user->role === 'checker') {
                $query->where('checker_id', $user->id);
            } elseif ($user->role === 'ops') {
                $query->where('ops_id', $user->id);
            }

            // Apply filters
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['property_id'])) {
                $query->where('property_id', $filters['property_id']);
            }

            if (!empty($filters['checker_id']) && $this->checkRole($request, ['admin', 'ops'])) {
                $query->where('checker_id', $filters['checker_id']);
            }

            if (!empty($filters['ops_id']) && $this->checkRole($request, ['admin'])) {
                $query->where('ops_id', $filters['ops_id']);
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply sorting
            $query->orderBy($sortingParams['sort_by'], $sortingParams['sort_order']);

            // Get paginated results
            $missions = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedMissions = $missions->getCollection()->map(function ($mission) {
                return $this->transformMission($mission);
            });

            $missions->setCollection($transformedMissions);

            return $this->paginated($missions, 'Missions retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve missions');
        }
    }

    /**
     * Get a specific mission by ID
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            $mission = Mission::with(['property', 'checker', 'ops', 'checklist', 'checklist.items'])->findOrFail($id);

            // Check permissions
            if ($user->role === 'checker' && $mission->checker_id !== $user->id) {
                return $this->forbidden('You can only view your own missions');
            }

            if ($user->role === 'ops' && $mission->ops_id !== $user->id) {
                return $this->forbidden('You can only view missions assigned to you');
            }

            return $this->success([
                'mission' => $this->transformMission($mission, ['checklist'])
            ], 'Mission retrieved successfully');

        } catch (\Exception $e) {
            return $this->notFound('Mission not found');
        }
    }

    /**
     * Create a new mission
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            // Check permissions
            if (!$this->checkRole($request, ['admin', 'ops'])) {
                return $this->forbidden('Insufficient permissions to create missions');
            }

            // Validate request
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
                'property_id' => ['required', 'exists:properties,id'],
                'checker_id' => ['required', 'exists:users,id'],
                'ops_id' => ['nullable', 'exists:users,id'],
                'due_date' => ['nullable', 'date', 'after:today'],
                'priority' => ['nullable', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            ]);

            // Set defaults
            $validated['status'] = 'pending';
            $validated['priority'] = $validated['priority'] ?? 'medium';
            $validated['admin_id'] = $user->id;
            $validated['ops_id'] = $validated['ops_id'] ?? $user->id;

            // Verify checker role
            $checker = User::findOrFail($validated['checker_id']);
            if ($checker->role !== 'checker') {
                return $this->error('Selected user is not a checker', 422);
            }

            // Create mission
            $mission = Mission::create($validated);
            $mission->load(['property', 'checker', 'ops']);

            // Log the action
            $this->auditLogger->log('mission_created', $user, [
                'mission_id' => $mission->id,
                'mission_title' => $mission->title,
                'property_id' => $mission->property_id,
                'checker_id' => $mission->checker_id,
            ]);

            return $this->success([
                'mission' => $this->transformMission($mission)
            ], 'Mission created successfully', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create mission');
        }
    }

    /**
     * Update an existing mission
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            $mission = Mission::findOrFail($id);

            // Check permissions
            if ($user->role === 'checker' && $mission->checker_id !== $user->id) {
                return $this->forbidden('You can only update your own missions');
            }

            if ($user->role === 'ops' && $mission->ops_id !== $user->id && !$this->checkRole($request, ['admin'])) {
                return $this->forbidden('Insufficient permissions to update this mission');
            }

            // Validate request based on user role
            $rules = [];
            
            if ($this->checkRole($request, ['admin', 'ops'])) {
                $rules = [
                    'title' => ['sometimes', 'string', 'max:255'],
                    'description' => ['nullable', 'string', 'max:1000'],
                    'checker_id' => ['sometimes', 'exists:users,id'],
                    'ops_id' => ['sometimes', 'exists:users,id'],
                    'due_date' => ['nullable', 'date'],
                    'priority' => ['sometimes', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
                    'status' => ['sometimes', 'string', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
                ];
            } else {
                // Checkers can only update status and add notes
                $rules = [
                    'status' => ['sometimes', 'string', Rule::in(['in_progress', 'completed'])],
                    'notes' => ['nullable', 'string', 'max:1000'],
                ];
            }

            $validated = $request->validate($rules);

            // Store original data for audit
            $originalData = $mission->toArray();

            // Update mission
            $mission->update($validated);

            // Log the action
            $this->auditLogger->log('mission_updated', $user, [
                'mission_id' => $mission->id,
                'mission_title' => $mission->title,
                'changes' => array_diff_assoc($validated, $originalData),
            ]);

            $mission->load(['property', 'checker', 'ops']);

            return $this->success([
                'mission' => $this->transformMission($mission)
            ], 'Mission updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->notFound('Mission not found');
        }
    }

    /**
     * Delete a mission
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            // Check permissions
            if (!$this->checkRole($request, ['admin'])) {
                return $this->forbidden('Insufficient permissions to delete missions');
            }

            $mission = Mission::findOrFail($id);

            // Check if mission can be deleted
            if ($mission->status === 'in_progress') {
                return $this->error('Cannot delete mission that is in progress', 409);
            }

            // Store mission data for audit
            $missionData = $mission->toArray();

            // Delete mission
            $mission->delete();

            // Log the action
            $this->auditLogger->log('mission_deleted', $user, [
                'mission_id' => $id,
                'mission_title' => $missionData['title'],
            ]);

            return $this->success(null, 'Mission deleted successfully');

        } catch (\Exception $e) {
            return $this->notFound('Mission not found');
        }
    }

    /**
     * Get mission statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            // Check permissions
            if (!$this->checkRole($request, ['admin', 'ops'])) {
                return $this->forbidden('Insufficient permissions to view statistics');
            }

            $query = Mission::query();

            // Filter by user role
            if ($user->role === 'ops') {
                $query->where('ops_id', $user->id);
            }

            $stats = [
                'total_missions' => $query->count(),
                'pending_missions' => (clone $query)->where('status', 'pending')->count(),
                'in_progress_missions' => (clone $query)->where('status', 'in_progress')->count(),
                'completed_missions' => (clone $query)->where('status', 'completed')->count(),
                'cancelled_missions' => (clone $query)->where('status', 'cancelled')->count(),
                'overdue_missions' => (clone $query)->where('due_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'missions_by_priority' => (clone $query)->selectRaw('priority, COUNT(*) as count')
                    ->groupBy('priority')
                    ->pluck('count', 'priority'),
            ];

            return $this->success($stats, 'Mission statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve mission statistics');
        }
    }

    /**
     * Get comparison with previous inspection
     */
    public function comparison(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            $mission = Mission::with(['checklists.items', 'checker', 'property'])->findOrFail($id);

            // Check permissions
            if ($user->role === 'checker' && $mission->checker_id !== $user->id) {
                return $this->forbidden('You can only view comparisons for your own missions');
            }

            $comparisonService = app(\App\Services\InspectionComparisonService::class);
            $comparison = $comparisonService->getComparison($mission);

            return $this->success($comparison, 'Comparison retrieved successfully');

        } catch (\Exception $e) {
            return $this->notFound('Mission not found');
        }
    }

    /**
     * Transform mission for API response
     */
    private function transformMission(Mission $mission, array $relations = []): array
    {
        $data = [
            'id' => $mission->id,
            'title' => $mission->title,
            'description' => $mission->description,
            'status' => $mission->status,
            'priority' => $mission->priority,
            'due_date' => $mission->due_date,
            'notes' => $mission->notes,
            'created_at' => $mission->created_at,
            'updated_at' => $mission->updated_at,
            'property' => $mission->property ? [
                'id' => $mission->property->id,
                'name' => $mission->property->name,
                'address' => $mission->property->address,
            ] : null,
            'checker' => $mission->checker ? [
                'id' => $mission->checker->id,
                'name' => $mission->checker->name,
                'email' => $mission->checker->email,
            ] : null,
            'ops' => $mission->ops ? [
                'id' => $mission->ops->id,
                'name' => $mission->ops->name,
                'email' => $mission->ops->email,
            ] : null,
        ];

        // Include checklist if requested
        if (in_array('checklist', $relations) && $mission->relationLoaded('checklist') && $mission->checklist) {
            $data['checklist'] = [
                'id' => $mission->checklist->id,
                'items' => $mission->checklist->items ? $mission->checklist->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'description' => $item->description,
                        'is_completed' => $item->is_completed,
                        'requires_photo' => $item->requires_photo,
                        'photo_path' => $item->photo_path,
                        'notes' => $item->notes,
                    ];
                }) : [],
            ];
        }

        return $data;
    }
}