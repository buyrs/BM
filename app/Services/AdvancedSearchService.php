<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\User;
use App\Models\Property;
use App\Models\Checklist;
use App\Models\MaintenanceRequest;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AdvancedSearchService extends BaseService
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Search missions with advanced filters
     */
    public function searchMissions(array $filters): array
    {
        $query = Mission::query()->with(['admin', 'ops', 'checker', 'checklists']);

        // Apply filters
        $this->applyMissionFilters($query, $filters);

        // Get results with pagination
        $results = $query->paginate($filters['per_page'] ?? 25);

        // Log search
        $this->auditLogger->log(
            auth()->user(),
            'advanced_search',
            'Mission',
            null,
            ['filters' => $filters, 'results_count' => $results->total()]
        );

        return [
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem()
            ],
            'filters_applied' => $this->getAppliedFilters($filters),
            'search_stats' => $this->getMissionSearchStats($filters)
        ];
    }

    /**
     * Search users with advanced filters
     */
    public function searchUsers(array $filters): array
    {
        $query = User::query()->with(['missions', 'opsMissions', 'adminMissions']);

        // Apply filters
        $this->applyUserFilters($query, $filters);

        // Get results with pagination
        $results = $query->paginate($filters['per_page'] ?? 25);

        // Log search
        $this->auditLogger->log(
            auth()->user(),
            'advanced_search',
            'User',
            null,
            ['filters' => $filters, 'results_count' => $results->total()]
        );

        return [
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem()
            ],
            'filters_applied' => $this->getAppliedFilters($filters),
            'search_stats' => $this->getUserSearchStats($filters)
        ];
    }

    /**
     * Search properties with advanced filters
     */
    public function searchProperties(array $filters): array
    {
        $query = Property::query()->with(['missions']);

        // Apply filters
        $this->applyPropertyFilters($query, $filters);

        // Get results with pagination
        $results = $query->paginate($filters['per_page'] ?? 25);

        // Log search
        $this->auditLogger->log(
            auth()->user(),
            'advanced_search',
            'Property',
            null,
            ['filters' => $filters, 'results_count' => $results->total()]
        );

        return [
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem()
            ],
            'filters_applied' => $this->getAppliedFilters($filters),
            'search_stats' => $this->getPropertySearchStats($filters)
        ];
    }

    /**
     * Global search across all entities
     */
    public function globalSearch(string $query, array $options = []): array
    {
        $results = [];
        $searchTerm = trim($query);

        if (strlen($searchTerm) < 2) {
            return ['results' => [], 'total' => 0];
        }

        // Search missions
        $missions = Mission::where('title', 'LIKE', "%{$searchTerm}%")
            ->orWhere('description', 'LIKE', "%{$searchTerm}%")
            ->orWhere('property_address', 'LIKE', "%{$searchTerm}%")
            ->limit($options['limit'] ?? 10)
            ->get()
            ->map(function ($mission) {
                return [
                    'type' => 'mission',
                    'id' => $mission->id,
                    'title' => $mission->title,
                    'subtitle' => $mission->property_address,
                    'status' => $mission->status,
                    'url' => route('admin.missions.show', $mission)
                ];
            });

        // Search users
        $users = User::where('name', 'LIKE', "%{$searchTerm}%")
            ->orWhere('email', 'LIKE', "%{$searchTerm}%")
            ->limit($options['limit'] ?? 10)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'user',
                    'id' => $user->id,
                    'title' => $user->name,
                    'subtitle' => $user->email,
                    'status' => $user->role,
                    'url' => route('admin.users.show', $user)
                ];
            });

        // Search properties
        $properties = Property::where('property_address', 'LIKE', "%{$searchTerm}%")
            ->orWhere('owner_name', 'LIKE', "%{$searchTerm}%")
            ->orWhere('description', 'LIKE', "%{$searchTerm}%")
            ->limit($options['limit'] ?? 10)
            ->get()
            ->map(function ($property) {
                return [
                    'type' => 'property',
                    'id' => $property->id,
                    'title' => $property->property_address,
                    'subtitle' => $property->owner_name,
                    'status' => $property->property_type,
                    'url' => route('admin.properties.show', $property)
                ];
            });

        $results = collect()
            ->merge($missions)
            ->merge($users)
            ->merge($properties)
            ->take($options['total_limit'] ?? 30);

        // Log global search
        $this->auditLogger->log(
            auth()->user(),
            'global_search',
            'Global',
            null,
            ['query' => $searchTerm, 'results_count' => $results->count()]
        );

        return [
            'results' => $results->toArray(),
            'total' => $results->count(),
            'query' => $searchTerm
        ];
    }

    /**
     * Save search for later use
     */
    public function saveSearch(string $name, string $type, array $filters): array
    {
        $userId = auth()->id();
        $searchKey = "saved_search_{$userId}_{$type}_{$name}";

        $searchData = [
            'name' => $name,
            'type' => $type,
            'filters' => $filters,
            'created_at' => now()->toISOString(),
            'user_id' => $userId
        ];

        Cache::put($searchKey, $searchData, now()->addDays(30));

        // Log saved search
        $this->auditLogger->log(
            auth()->user(),
            'save_search',
            ucfirst($type),
            null,
            ['search_name' => $name, 'filters' => $filters]
        );

        return $searchData;
    }

    /**
     * Get saved searches for user
     */
    public function getSavedSearches(string $type = null): array
    {
        $userId = auth()->id();
        $pattern = $type ? "saved_search_{$userId}_{$type}_*" : "saved_search_{$userId}_*";
        
        // This is a simplified implementation - in production you'd want to use Redis SCAN
        $searches = [];
        $cacheKeys = Cache::getRedis()->keys($pattern);
        
        foreach ($cacheKeys as $key) {
            $search = Cache::get($key);
            if ($search) {
                $searches[] = $search;
            }
        }

        return collect($searches)
            ->sortByDesc('created_at')
            ->values()
            ->toArray();
    }

    /**
     * Delete saved search
     */
    public function deleteSavedSearch(string $name, string $type): bool
    {
        $userId = auth()->id();
        $searchKey = "saved_search_{$userId}_{$type}_{$name}";

        $deleted = Cache::forget($searchKey);

        if ($deleted) {
            $this->auditLogger->log(
                auth()->user(),
                'delete_saved_search',
                ucfirst($type),
                null,
                ['search_name' => $name]
            );
        }

        return $deleted;
    }

    /**
     * Export search results
     */
    public function exportSearchResults(string $type, array $filters, string $format = 'csv'): string
    {
        $results = match($type) {
            'missions' => $this->searchMissions(array_merge($filters, ['per_page' => 10000]))['data'],
            'users' => $this->searchUsers(array_merge($filters, ['per_page' => 10000]))['data'],
            'properties' => $this->searchProperties(array_merge($filters, ['per_page' => 10000]))['data'],
            default => []
        };

        $filename = "search_export_{$type}_" . date('Y-m-d_H-i-s') . ".{$format}";
        $filepath = storage_path("app/exports/{$filename}");

        // Ensure directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        if ($format === 'csv') {
            $this->exportToCsv($results, $filepath, $type);
        } elseif ($format === 'json') {
            $this->exportToJson($results, $filepath);
        }

        // Log export
        $this->auditLogger->log(
            auth()->user(),
            'export_search_results',
            ucfirst($type),
            null,
            ['format' => $format, 'results_count' => count($results), 'filename' => $filename]
        );

        return $filename;
    }

    /**
     * Get search analytics
     */
    public function getSearchAnalytics(): array
    {
        $userId = auth()->id();
        
        return Cache::remember("search_analytics_{$userId}", 300, function () {
            $recentSearches = AuditLog::where('user_id', auth()->id())
                ->where('action', 'advanced_search')
                ->where('created_at', '>=', now()->subDays(30))
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            $searchCounts = $recentSearches->groupBy('resource_type')
                ->map(function ($searches) {
                    return $searches->count();
                });

            $popularFilters = $recentSearches->pluck('changes')
                ->map(function ($changes) {
                    return array_keys($changes['filters'] ?? []);
                })
                ->flatten()
                ->countBy()
                ->sortDesc()
                ->take(10);

            return [
                'total_searches' => $recentSearches->count(),
                'searches_by_type' => $searchCounts->toArray(),
                'popular_filters' => $popularFilters->toArray(),
                'recent_searches' => $recentSearches->take(10)->map(function ($search) {
                    return [
                        'type' => strtolower($search->resource_type),
                        'filters' => $search->changes['filters'] ?? [],
                        'results_count' => $search->changes['results_count'] ?? 0,
                        'created_at' => $search->created_at->toISOString()
                    ];
                })->toArray()
            ];
        });
    }

    /**
     * Apply mission-specific filters
     */
    protected function applyMissionFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('property_address', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['checker_id'])) {
            $query->where('checker_id', $filters['checker_id']);
        }

        if (!empty($filters['ops_id'])) {
            $query->where('ops_id', $filters['ops_id']);
        }

        if (!empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        if (!empty($filters['checkin_from'])) {
            $query->where('checkin_date', '>=', Carbon::parse($filters['checkin_from']));
        }

        if (!empty($filters['checkin_to'])) {
            $query->where('checkin_date', '<=', Carbon::parse($filters['checkin_to']));
        }

        if (!empty($filters['property_type'])) {
            $query->whereHas('property', function ($q) use ($filters) {
                $q->where('property_type', $filters['property_type']);
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Apply user-specific filters
     */
    protected function applyUserFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['role'])) {
            if (is_array($filters['role'])) {
                $query->whereIn('role', $filters['role']);
            } else {
                $query->where('role', $filters['role']);
            }
        }

        if (!empty($filters['two_factor_enabled'])) {
            $query->where('two_factor_enabled', $filters['two_factor_enabled'] === 'true');
        }

        if (!empty($filters['last_login_from'])) {
            $query->where('last_login_at', '>=', Carbon::parse($filters['last_login_from']));
        }

        if (!empty($filters['last_login_to'])) {
            $query->where('last_login_at', '<=', Carbon::parse($filters['last_login_to'])->endOfDay());
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Apply property-specific filters
     */
    protected function applyPropertyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('property_address', 'LIKE', "%{$search}%")
                  ->orWhere('owner_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['property_type'])) {
            if (is_array($filters['property_type'])) {
                $query->whereIn('property_type', $filters['property_type']);
            } else {
                $query->where('property_type', $filters['property_type']);
            }
        }

        if (!empty($filters['owner_name'])) {
            $query->where('owner_name', 'LIKE', "%{$filters['owner_name']}%");
        }

        if (!empty($filters['has_missions'])) {
            if ($filters['has_missions'] === 'true') {
                $query->has('missions');
            } else {
                $query->doesntHave('missions');
            }
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Get applied filters summary
     */
    protected function getAppliedFilters(array $filters): array
    {
        $applied = [];
        
        foreach ($filters as $key => $value) {
            if (!empty($value) && !in_array($key, ['page', 'per_page', 'sort_by', 'sort_order'])) {
                $applied[$key] = $value;
            }
        }

        return $applied;
    }

    /**
     * Get mission search statistics
     */
    protected function getMissionSearchStats(array $filters): array
    {
        $baseQuery = Mission::query();
        $this->applyMissionFilters($baseQuery, array_diff_key($filters, ['per_page' => null, 'page' => null]));

        return [
            'total_found' => $baseQuery->count(),
            'status_breakdown' => $baseQuery->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray()
        ];
    }

    /**
     * Get user search statistics
     */
    protected function getUserSearchStats(array $filters): array
    {
        $baseQuery = User::query();
        $this->applyUserFilters($baseQuery, array_diff_key($filters, ['per_page' => null, 'page' => null]));

        return [
            'total_found' => $baseQuery->count(),
            'role_breakdown' => $baseQuery->select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray()
        ];
    }

    /**
     * Get property search statistics
     */
    protected function getPropertySearchStats(array $filters): array
    {
        $baseQuery = Property::query();
        $this->applyPropertyFilters($baseQuery, array_diff_key($filters, ['per_page' => null, 'page' => null]));

        return [
            'total_found' => $baseQuery->count(),
            'type_breakdown' => $baseQuery->select('property_type', DB::raw('count(*) as count'))
                ->groupBy('property_type')
                ->pluck('count', 'property_type')
                ->toArray()
        ];
    }

    /**
     * Export results to CSV
     */
    protected function exportToCsv(array $results, string $filepath, string $type): void
    {
        $file = fopen($filepath, 'w');
        
        if (empty($results)) {
            fclose($file);
            return;
        }

        // Write headers based on type
        $headers = $this->getCsvHeaders($type);
        fputcsv($file, $headers);

        // Write data
        foreach ($results as $result) {
            $row = $this->formatCsvRow($result, $type);
            fputcsv($file, $row);
        }

        fclose($file);
    }

    /**
     * Export results to JSON
     */
    protected function exportToJson(array $results, string $filepath): void
    {
        file_put_contents($filepath, json_encode($results, JSON_PRETTY_PRINT));
    }

    /**
     * Get CSV headers for different types
     */
    protected function getCsvHeaders(string $type): array
    {
        return match($type) {
            'missions' => ['ID', 'Title', 'Property Address', 'Status', 'Checker', 'Ops', 'Admin', 'Check-in Date', 'Check-out Date', 'Created At'],
            'users' => ['ID', 'Name', 'Email', 'Role', 'Two-Factor Enabled', 'Last Login', 'Created At'],
            'properties' => ['ID', 'Property Address', 'Owner Name', 'Property Type', 'Description', 'Created At'],
            default => ['ID', 'Data']
        };
    }

    /**
     * Format row for CSV export
     */
    protected function formatCsvRow($result, string $type): array
    {
        return match($type) {
            'missions' => [
                $result->id,
                $result->title,
                $result->property_address,
                $result->status,
                $result->checker->name ?? '',
                $result->ops->name ?? '',
                $result->admin->name ?? '',
                $result->checkin_date,
                $result->checkout_date,
                $result->created_at
            ],
            'users' => [
                $result->id,
                $result->name,
                $result->email,
                $result->role,
                $result->two_factor_enabled ? 'Yes' : 'No',
                $result->last_login_at,
                $result->created_at
            ],
            'properties' => [
                $result->id,
                $result->property_address,
                $result->owner_name,
                $result->property_type,
                $result->description,
                $result->created_at
            ],
            default => [$result->id, json_encode($result)]
        };
    }
}