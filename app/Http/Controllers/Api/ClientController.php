<?php

namespace App\Http\Controllers\Api;

use App\Models\Client;
use App\Models\Mission;
use App\Models\Property;
use App\Policies\ClientPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends BaseApiController
{
    /**
     * Get client dashboard data.
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            // Verify client access
            $client = Client::where('user_id', $user->id)->first();
            
            if (!$client) {
                return $this->forbidden('No client account associated');
            }

            $stats = $client->getDashboardStats();
            
            // Get recent activity
            $propertyIds = $client->getPropertyIds();
            $recentMissions = Mission::whereIn('property_id', $propertyIds)
                ->where('status', 'completed')
                ->with(['property:id,name,property_address', 'checker:id,name'])
                ->orderBy('completed_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($mission) {
                    return [
                        'id' => $mission->id,
                        'title' => $mission->title,
                        'property' => $mission->property?->name,
                        'completed_at' => $mission->completed_at,
                        'has_issues' => $mission->has_issues ?? false,
                    ];
                });

            return $this->success([
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'company' => $client->company_name,
                ],
                'stats' => $stats,
                'recent_inspections' => $recentMissions,
            ], 'Dashboard loaded successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to load dashboard');
        }
    }

    /**
     * Get client's properties.
     */
    public function properties(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            $client = Client::where('user_id', $user->id)->first();

            if (!$client) {
                return $this->forbidden('No client account associated');
            }

            $paginationParams = $this->getPaginationParams($request);

            $properties = $client->properties()
                ->withCount([
                    'missions as total_inspections',
                    'missions as pending_inspections' => function ($q) {
                        $q->whereIn('status', ['pending', 'in_progress']);
                    },
                ])
                ->paginate($paginationParams['per_page']);

            return $this->paginated($properties, 'Properties retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve properties');
        }
    }

    /**
     * Get a specific property details.
     */
    public function property(Request $request, int $propertyId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            $client = Client::where('user_id', $user->id)->first();

            if (!$client) {
                return $this->forbidden('No client account associated');
            }

            $property = Property::where('id', $propertyId)
                ->where('client_id', $client->id)
                ->first();

            if (!$property) {
                return $this->notFound('Property not found');
            }

            // Get inspection history
            $inspections = Mission::where('property_id', $property->id)
                ->where('status', 'completed')
                ->with(['checker:id,name'])
                ->orderBy('completed_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($mission) {
                    return [
                        'id' => $mission->id,
                        'title' => $mission->title,
                        'completed_at' => $mission->completed_at,
                        'checker' => $mission->checker?->name,
                        'has_issues' => $mission->has_issues ?? false,
                        'photo_count' => $mission->photo_count ?? 0,
                    ];
                });

            return $this->success([
                'property' => [
                    'id' => $property->id,
                    'name' => $property->name,
                    'address' => $property->property_address,
                    'city' => $property->city,
                    'type' => $property->type,
                ],
                'inspections' => $inspections,
            ], 'Property details retrieved');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve property');
        }
    }

    /**
     * Get inspection report for client.
     */
    public function inspectionReport(Request $request, int $missionId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            $client = Client::where('user_id', $user->id)->first();

            if (!$client) {
                return $this->forbidden('No client account associated');
            }

            $mission = Mission::with([
                'property:id,name,property_address',
                'checker:id,name',
                'checklists.items',
            ])->find($missionId);

            if (!$mission) {
                return $this->notFound('Inspection not found');
            }

            // Verify access
            if (!$client->canAccessMission($mission)) {
                return $this->forbidden('Access denied to this inspection');
            }

            // Only show completed inspections to clients
            if ($mission->status !== 'completed') {
                return $this->error('Inspection report not yet available', 403);
            }

            // Build report data
            $report = [
                'id' => $mission->id,
                'title' => $mission->title,
                'property' => [
                    'name' => $mission->property?->name,
                    'address' => $mission->property?->property_address,
                ],
                'inspector' => $mission->checker?->name,
                'completed_at' => $mission->completed_at,
                'summary' => $mission->notes,
                'has_issues' => $mission->has_issues ?? false,
                'areas' => [],
            ];

            // Build checklist areas
            foreach ($mission->checklists as $checklist) {
                $area = [
                    'name' => $checklist->name,
                    'items' => [],
                ];

                foreach ($checklist->items as $item) {
                    $area['items'][] = [
                        'description' => $item->description,
                        'status' => $item->is_completed ? 'checked' : 'not_checked',
                        'has_photo' => !empty($item->photo_path),
                        'notes' => $item->notes,
                        'condition' => $item->data['condition'] ?? null,
                    ];
                }

                $report['areas'][] = $area;
            }

            return $this->success($report, 'Inspection report retrieved');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve inspection report');
        }
    }

    /**
     * Get all inspections for client.
     */
    public function inspections(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            $client = Client::where('user_id', $user->id)->first();

            if (!$client) {
                return $this->forbidden('No client account associated');
            }

            $paginationParams = $this->getPaginationParams($request);
            $filters = $this->getFilterParams($request, ['property_id', 'has_issues']);

            $propertyIds = $client->getPropertyIds();

            $query = Mission::whereIn('property_id', $propertyIds)
                ->where('status', 'completed')
                ->with(['property:id,name,property_address', 'checker:id,name']);

            if (!empty($filters['property_id'])) {
                $query->where('property_id', $filters['property_id']);
            }

            if (isset($filters['has_issues'])) {
                $query->where('has_issues', $filters['has_issues'] === 'true');
            }

            $inspections = $query->orderBy('completed_at', 'desc')
                ->paginate($paginationParams['per_page']);

            return $this->paginated($inspections, 'Inspections retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve inspections');
        }
    }
}
