<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Query\Builder;

class DatabaseOptimizationService
{
    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Execute optimized queries with proper eager loading
     * 
     * @param string $modelClass
     * @param array $relations
     * @param array $conditions
     * @param string $orderBy
     * @param int|null $limit
     * @param bool $useCache
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOptimizedModelQuery(
        string $modelClass,
        array $relations = [],
        array $conditions = [],
        string $orderBy = 'id',
        ?int $limit = null,
        bool $useCache = true
    ) {
        $cacheKey = $this->generateCacheKey($modelClass, $relations, $conditions, $orderBy, $limit);
        
        if ($useCache) {
            $cachedResult = $this->cacheService->get($cacheKey);
            if ($cachedResult) {
                return $this->convertCachedDataToCollection($modelClass, $cachedResult);
            }
        }

        $query = $modelClass::query();

        // Apply eager loading
        if (!empty($relations)) {
            $query->with($relations);
        }

        // Apply conditions
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                if (isset($value['operator']) && isset($value['value'])) {
                    $query->where($field, $value['operator'], $value['value']);
                } else {
                    $query->whereIn($field, $value);
                }
            } else {
                $query->where($field, $value);
            }
        }

        // Apply ordering
        $query->orderBy($orderBy);

        // Apply limit if specified
        if ($limit) {
            $query->limit($limit);
        }

        $result = $query->get();

        if ($useCache) {
            $this->cacheService->put($cacheKey, $result->toArray(), 1800); // 30 minutes
        }

        return $result;
    }

    /**
     * Get paginated optimized query results
     *
     * @param string $modelClass
     * @param array $relations
     * @param array $conditions
     * @param string $orderBy
     * @param int $perPage
     * @param bool $useCache
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getOptimizedModelPagination(
        string $modelClass,
        array $relations = [],
        array $conditions = [],
        string $orderBy = 'id',
        int $perPage = 15,
        bool $useCache = false // Don't cache pagination by default as it can cause issues
    ) {
        $query = $modelClass::query();

        // Apply eager loading
        if (!empty($relations)) {
            $query->with($relations);
        }

        // Apply conditions
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                if (isset($value['operator']) && isset($value['value'])) {
                    $query->where($field, $value['operator'], $value['value']);
                } else {
                    $query->whereIn($field, $value);
                }
            } else {
                $query->where($field, $value);
            }
        }

        // Apply ordering
        $query->orderBy($orderBy);

        return $query->paginate($perPage);
    }

    /**
     * Execute raw query with performance optimization
     *
     * @param string $query
     * @param array $bindings
     * @param int $ttl
     * @return mixed
     */
    public function executeOptimizedRawQuery(string $query, array $bindings = [], int $ttl = 1800)
    {
        $cacheKey = 'raw_query_' . md5($query . serialize($bindings));
        
        return $this->cacheService->remember($cacheKey, $ttl, function () use ($query, $bindings) {
            return DB::select($query, $bindings);
        });
    }

    /**
     * Get optimized count query
     *
     * @param string $modelClass
     * @param array $conditions
     * @return int
     */
    public function getOptimizedCount(string $modelClass, array $conditions = []): int
    {
        $cacheKey = 'count_' . $modelClass . '_' . md5(serialize($conditions));
        
        return $this->cacheService->remember($cacheKey, 300, function () use ($modelClass, $conditions) { // 5 minutes for counts
            $query = $modelClass::query();
            
            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
            
            return $query->count();
        });
    }

    /**
     * Get optimized exists query
     *
     * @param string $modelClass
     * @param array $conditions
     * @return bool
     */
    public function getOptimizedExists(string $modelClass, array $conditions = []): bool
    {
        $query = $modelClass::query();
        
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->exists();
    }

    /**
     * Generate cache key for query
     *
     * @param string $modelClass
     * @param array $relations
     * @param array $conditions
     * @param string $orderBy
     * @param int|null $limit
     * @return string
     */
    private function generateCacheKey(string $modelClass, array $relations, array $conditions, string $orderBy, ?int $limit): string
    {
        $key = $modelClass . '_' . 
               md5(serialize($relations)) . '_' . 
               md5(serialize($conditions)) . '_' . 
               $orderBy . '_' . 
               ($limit ?? 'null');
        
        return 'optimized_query_' . $key;
    }

    /**
     * Convert cached data back to Eloquent collection
     *
     * @param string $modelClass
     * @param array $cachedData
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function convertCachedDataToCollection(string $modelClass, array $cachedData)
    {
        $collection = collect();
        
        foreach ($cachedData as $itemData) {
            $model = new $modelClass();
            $model->forceFill($itemData);
            $collection->push($model);
        }
        
        return $collection;
    }

    /**
     * Optimize query for dashboard statistics
     *
     * @param string $forUserType
     * @return array
     */
    public function getOptimizedDashboardStats(string $forUserType): array
    {
        $cacheKey = "dashboard_stats_{$forUserType}";
        
        return $this->cacheService->remember($cacheKey, 900, function () use ($forUserType) { // 15 minutes
            $stats = [];
            
            switch ($forUserType) {
                case 'admin':
                    $stats['total_users'] = $this->getOptimizedCount('App\Models\User');
                    $stats['total_missions'] = $this->getOptimizedCount('App\Models\Mission');
                    $stats['pending_missions'] = $this->getOptimizedCount('App\Models\Mission', ['status' => 'pending']);
                    $stats['completed_missions'] = $this->getOptimizedCount('App\Models\Mission', ['status' => 'completed']);
                    break;
                    
                case 'ops':
                    $stats['total_missions'] = $this->getOptimizedCount('App\Models\Mission', ['ops_id' => auth()->id()]);
                    $stats['pending_missions'] = $this->getOptimizedCount('App\Models\Mission', ['ops_id' => auth()->id(), 'status' => 'pending']);
                    $stats['approved_missions'] = $this->getOptimizedCount('App\Models\Mission', ['ops_id' => auth()->id(), 'status' => 'approved']);
                    break;
                    
                case 'checker':
                    $stats['total_missions'] = $this->getOptimizedCount('App\Models\Mission', ['checker_id' => auth()->id()]);
                    $stats['pending_checklists'] = $this->getOptimizedCount('App\Models\Checklist', ['mission.checker_id' => auth()->id(), 'status' => 'pending']);
                    $stats['completed_checklists'] = $this->getOptimizedCount('App\Models\Checklist', ['mission.checker_id' => auth()->id(), 'status' => 'completed']);
                    break;
            }
            
            return $stats;
        });
    }

    /**
     * Optimize search query with full-text search where available
     *
     * @param string $modelClass
     * @param string $searchTerm
     * @param array $searchColumns
     * @param array $additionalConditions
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOptimizedSearchQuery(
        string $modelClass,
        string $searchTerm,
        array $searchColumns = ['name', 'email'],
        array $additionalConditions = []
    ) {
        $cacheKey = 'search_' . $modelClass . '_' . md5($searchTerm . serialize($searchColumns) . serialize($additionalConditions));
        
        return $this->cacheService->remember($cacheKey, 600, function () use ($modelClass, $searchTerm, $searchColumns, $additionalConditions) {
            $query = $modelClass::query();
            
            // Add search conditions
            $query->where(function ($q) use ($searchTerm, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'LIKE', "%{$searchTerm}%");
                }
            });
            
            // Add additional conditions
            foreach ($additionalConditions as $field => $value) {
                $query->where($field, $value);
            }
            
            return $query->get();
        });
    }

    /**
     * Perform optimized bulk operations
     *
     * @param string $modelClass
     * @param array $updates
     * @return int
     */
    public function performOptimizedBulkUpdate(string $modelClass, array $updates): int
    {
        $updatedCount = 0;
        
        foreach ($updates as $condition => $updateData) {
            $query = $modelClass::where($condition[0], $condition[1], $condition[2]);
            $updatedCount += $query->update($updateData);
        }
        
        return $updatedCount;
    }
}