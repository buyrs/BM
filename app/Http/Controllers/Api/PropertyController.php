<?php

namespace App\Http\Controllers\Api;

use App\Models\Property;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyController extends BaseApiController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    /**
     * Get all properties with pagination and filtering
     * 
     * @group Properties
     * @authenticated
     * 
     * Retrieve a paginated list of properties with optional filtering and sorting.
     * 
     * @queryParam page integer Page number for pagination. Example: 1
     * @queryParam per_page integer Number of items per page (max 100). Example: 15
     * @queryParam sort_by string Field to sort by. Example: name
     * @queryParam sort_order string Sort order (asc/desc). Example: asc
     * @queryParam property_type string Filter by property type. Example: apartment
     * @queryParam status string Filter by status. Example: active
     * @queryParam search string Search in name, address, or description. Example: downtown
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Properties retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Downtown Apartment",
     *       "address": "123 Main St",
     *       "property_type": "apartment",
     *       "description": "Modern apartment in city center",
     *       "status": "active",
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "last_page": 5,
     *     "per_page": 15,
     *     "total": 75
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            // Get pagination and sorting parameters
            $paginationParams = $this->getPaginationParams($request);
            $sortingParams = $this->getSortingParams($request, ['name', 'address', 'property_type', 'created_at']);
            $filters = $this->getFilterParams($request, ['property_type', 'status', 'search']);

            // Build query
            $query = Property::query();

            // Apply filters
            if (!empty($filters['property_type'])) {
                $query->where('property_type', $filters['property_type']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply sorting
            $query->orderBy($sortingParams['sort_by'], $sortingParams['sort_order']);

            // Get paginated results
            $properties = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedProperties = $properties->getCollection()->map(function ($property) {
                return $this->transformProperty($property);
            });

            $properties->setCollection($transformedProperties);

            return $this->paginated($properties, 'Properties retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve properties');
        }
    }

    /**
     * Get a specific property by ID
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $property = Property::with(['missions', 'missions.checklist'])->findOrFail($id);

            return $this->success([
                'property' => $this->transformProperty($property, ['missions'])
            ], 'Property retrieved successfully');

        } catch (\Exception $e) {
            return $this->notFound('Property not found');
        }
    }

    /**
     * Create a new property
     * 
     * @group Properties
     * @authenticated
     * 
     * Create a new property. Requires admin or ops role.
     * 
     * @bodyParam name string required Property name. Example: Downtown Apartment
     * @bodyParam address string required Property address. Example: 123 Main St, City, State
     * @bodyParam property_type string required Property type. Example: apartment
     * @bodyParam description string Property description. Example: Modern apartment in city center
     * @bodyParam status string Property status (active/inactive/maintenance). Example: active
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "Property created successfully",
     *   "data": {
     *     "property": {
     *       "id": 1,
     *       "name": "Downtown Apartment",
     *       "address": "123 Main St, City, State",
     *       "property_type": "apartment",
     *       "description": "Modern apartment in city center",
     *       "status": "active",
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     }
     *   }
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "Insufficient permissions to create properties"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            // Check permissions
            if (!$this->checkRole($request, ['admin', 'ops'])) {
                return $this->forbidden('Insufficient permissions to create properties');
            }

            // Validate request
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:500'],
                'property_type' => ['required', 'string', Rule::in(['apartment', 'house', 'condo', 'townhouse', 'other'])],
                'description' => ['nullable', 'string', 'max:1000'],
                'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'maintenance'])],
            ]);

            // Set default status
            $validated['status'] = $validated['status'] ?? 'active';

            // Create property
            $property = Property::create($validated);

            // Log the action
            $this->auditLogger->log('property_created', $user, [
                'property_id' => $property->id,
                'property_name' => $property->name,
            ]);

            return $this->success([
                'property' => $this->transformProperty($property)
            ], 'Property created successfully', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create property');
        }
    }

    /**
     * Update an existing property
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            // Check permissions
            if (!$this->checkRole($request, ['admin', 'ops'])) {
                return $this->forbidden('Insufficient permissions to update properties');
            }

            $property = Property::findOrFail($id);

            // Validate request
            $validated = $request->validate([
                'name' => ['sometimes', 'string', 'max:255'],
                'address' => ['sometimes', 'string', 'max:500'],
                'property_type' => ['sometimes', 'string', Rule::in(['apartment', 'house', 'condo', 'townhouse', 'other'])],
                'description' => ['nullable', 'string', 'max:1000'],
                'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'maintenance'])],
            ]);

            // Store original data for audit
            $originalData = $property->toArray();

            // Update property
            $property->update($validated);

            // Log the action
            $this->auditLogger->log('property_updated', $user, [
                'property_id' => $property->id,
                'property_name' => $property->name,
                'changes' => array_diff_assoc($validated, $originalData),
            ]);

            return $this->success([
                'property' => $this->transformProperty($property)
            ], 'Property updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->notFound('Property not found');
        }
    }

    /**
     * Delete a property
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            // Check permissions
            if (!$this->checkRole($request, ['admin'])) {
                return $this->forbidden('Insufficient permissions to delete properties');
            }

            $property = Property::findOrFail($id);

            // Check if property has active missions
            $activeMissions = $property->missions()->whereIn('status', ['pending', 'in_progress'])->count();
            
            if ($activeMissions > 0) {
                return $this->error('Cannot delete property with active missions', 409);
            }

            // Store property data for audit
            $propertyData = $property->toArray();

            // Delete property
            $property->delete();

            // Log the action
            $this->auditLogger->log('property_deleted', $user, [
                'property_id' => $id,
                'property_name' => $propertyData['name'],
            ]);

            return $this->success(null, 'Property deleted successfully');

        } catch (\Exception $e) {
            return $this->notFound('Property not found');
        }
    }

    /**
     * Get property statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            // Check permissions
            if (!$this->checkRole($request, ['admin', 'ops'])) {
                return $this->forbidden('Insufficient permissions to view statistics');
            }

            $stats = [
                'total_properties' => Property::count(),
                'active_properties' => Property::where('status', 'active')->count(),
                'inactive_properties' => Property::where('status', 'inactive')->count(),
                'maintenance_properties' => Property::where('status', 'maintenance')->count(),
                'properties_by_type' => Property::selectRaw('property_type, COUNT(*) as count')
                    ->groupBy('property_type')
                    ->pluck('count', 'property_type'),
                'recent_properties' => Property::orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(fn($property) => $this->transformProperty($property)),
            ];

            return $this->success($stats, 'Property statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve property statistics');
        }
    }

    /**
     * Transform property for API response
     */
    private function transformProperty(Property $property, array $relations = []): array
    {
        $data = [
            'id' => $property->id,
            'name' => $property->name,
            'address' => $property->address,
            'property_type' => $property->property_type,
            'description' => $property->description,
            'status' => $property->status,
            'created_at' => $property->created_at,
            'updated_at' => $property->updated_at,
        ];

        // Include relations if requested
        if (in_array('missions', $relations) && $property->relationLoaded('missions')) {
            $data['missions'] = $property->missions->map(function ($mission) {
                return [
                    'id' => $mission->id,
                    'title' => $mission->title,
                    'status' => $mission->status,
                    'due_date' => $mission->due_date,
                    'created_at' => $mission->created_at,
                ];
            });
        }

        return $data;
    }
}