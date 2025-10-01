<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\Property;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\User;
use App\Models\MaintenanceRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsService extends BaseService
{
    /**
     * Get mission completion metrics
     */
    public function getMissionMetrics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $cacheKey = "mission_metrics_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 3600, function () use ($startDate, $endDate) {
            $missions = Mission::whereBetween('created_at', [$startDate, $endDate]);
            
            $totalMissions = $missions->count();
            $completedMissions = $missions->where('status', 'completed')->count();
            $inProgressMissions = $missions->where('status', 'in_progress')->count();
            $pendingMissions = $missions->where('status', 'pending')->count();
            
            $completionRate = $totalMissions > 0 ? ($completedMissions / $totalMissions) * 100 : 0;
            
            // Average completion time for completed missions
            $avgCompletionTime = Mission::where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
                ->value('avg_hours') ?? 0;

            return [
                'total_missions' => $totalMissions,
                'completed_missions' => $completedMissions,
                'in_progress_missions' => $inProgressMissions,
                'pending_missions' => $pendingMissions,
                'completion_rate' => round($completionRate, 2),
                'avg_completion_time_hours' => round($avgCompletionTime, 2),
            ];
        });
    }

    /**
     * Get user performance metrics
     */
    public function getUserPerformanceMetrics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $cacheKey = "user_performance_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 3600, function () use ($startDate, $endDate) {
            // Checker performance
            $checkerPerformance = User::where('role', 'checker')
                ->withCount([
                    'missions as total_missions' => function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    },
                    'missions as completed_missions' => function ($query) use ($startDate, $endDate) {
                        $query->where('status', 'completed')
                              ->whereBetween('created_at', [$startDate, $endDate]);
                    }
                ])
                ->get()
                ->map(function ($user) {
                    $completionRate = $user->total_missions > 0 
                        ? ($user->completed_missions / $user->total_missions) * 100 
                        : 0;
                    
                    return [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'total_missions' => $user->total_missions,
                        'completed_missions' => $user->completed_missions,
                        'completion_rate' => round($completionRate, 2),
                    ];
                });

            // Ops performance
            $opsPerformance = User::where('role', 'ops')
                ->withCount([
                    'missions as assigned_missions' => function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                ])
                ->get()
                ->map(function ($user) {
                    return [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'assigned_missions' => $user->assigned_missions,
                    ];
                });

            return [
                'checker_performance' => $checkerPerformance,
                'ops_performance' => $opsPerformance,
            ];
        });
    }

    /**
     * Get property and checklist completion tracking
     */
    public function getPropertyMetrics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $cacheKey = "property_metrics_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 3600, function () use ($startDate, $endDate) {
            // Property type distribution
            $propertyTypeDistribution = Property::select('property_type', DB::raw('count(*) as count'))
                ->groupBy('property_type')
                ->get()
                ->pluck('count', 'property_type')
                ->toArray();

            // Mission completion by property type
            $missionsByPropertyType = DB::table('missions')
                ->join('properties', 'missions.property_address', '=', 'properties.property_address')
                ->select('properties.property_type', 'missions.status', DB::raw('count(*) as count'))
                ->whereBetween('missions.created_at', [$startDate, $endDate])
                ->groupBy('properties.property_type', 'missions.status')
                ->get()
                ->groupBy('property_type')
                ->map(function ($missions) {
                    $total = $missions->sum('count');
                    $completed = $missions->where('status', 'completed')->sum('count');
                    $completionRate = $total > 0 ? ($completed / $total) * 100 : 0;
                    
                    return [
                        'total_missions' => $total,
                        'completed_missions' => $completed,
                        'completion_rate' => round($completionRate, 2),
                    ];
                })
                ->toArray();

            // Checklist completion metrics
            $checklistMetrics = $this->getChecklistCompletionMetrics($startDate, $endDate);

            return [
                'property_type_distribution' => $propertyTypeDistribution,
                'missions_by_property_type' => $missionsByPropertyType,
                'checklist_metrics' => $checklistMetrics,
            ];
        });
    }

    /**
     * Get checklist completion metrics
     */
    public function getChecklistCompletionMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $totalChecklists = Checklist::whereBetween('created_at', [$startDate, $endDate])->count();
        $completedChecklists = Checklist::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Checklist item completion rates
        $checklistItemStats = DB::table('checklist_items')
            ->join('checklists', 'checklist_items.checklist_id', '=', 'checklists.id')
            ->select('checklist_items.state', DB::raw('count(*) as count'))
            ->whereBetween('checklists.created_at', [$startDate, $endDate])
            ->groupBy('checklist_items.state')
            ->get()
            ->pluck('count', 'state')
            ->toArray();

        // Average items per checklist
        $avgItemsPerChecklist = DB::table('checklist_items')
            ->join('checklists', 'checklist_items.checklist_id', '=', 'checklists.id')
            ->whereBetween('checklists.created_at', [$startDate, $endDate])
            ->selectRaw('AVG(items_count) as avg_items')
            ->from(DB::raw('(SELECT checklist_id, COUNT(*) as items_count FROM checklist_items GROUP BY checklist_id) as subquery'))
            ->value('avg_items') ?? 0;

        return [
            'total_checklists' => $totalChecklists,
            'completed_checklists' => $completedChecklists,
            'completion_rate' => $totalChecklists > 0 ? round(($completedChecklists / $totalChecklists) * 100, 2) : 0,
            'item_states' => $checklistItemStats,
            'avg_items_per_checklist' => round($avgItemsPerChecklist, 2),
        ];
    }

    /**
     * Get maintenance request metrics
     */
    public function getMaintenanceMetrics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $cacheKey = "maintenance_metrics_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 3600, function () use ($startDate, $endDate) {
            $maintenanceRequests = MaintenanceRequest::whereBetween('created_at', [$startDate, $endDate]);
            
            $totalRequests = $maintenanceRequests->count();
            $completedRequests = $maintenanceRequests->where('status', 'completed')->count();
            $pendingRequests = $maintenanceRequests->where('status', 'pending')->count();
            $inProgressRequests = $maintenanceRequests->where('status', 'in_progress')->count();

            // Priority distribution
            $priorityDistribution = MaintenanceRequest::whereBetween('created_at', [$startDate, $endDate])
                ->select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->get()
                ->pluck('count', 'priority')
                ->toArray();

            // Average resolution time
            $avgResolutionTime = MaintenanceRequest::where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours')
                ->value('avg_hours') ?? 0;

            return [
                'total_requests' => $totalRequests,
                'completed_requests' => $completedRequests,
                'pending_requests' => $pendingRequests,
                'in_progress_requests' => $inProgressRequests,
                'completion_rate' => $totalRequests > 0 ? round(($completedRequests / $totalRequests) * 100, 2) : 0,
                'priority_distribution' => $priorityDistribution,
                'avg_resolution_time_hours' => round($avgResolutionTime, 2),
            ];
        });
    }

    /**
     * Get system performance metrics
     */
    public function getSystemMetrics(): array
    {
        $cacheKey = "system_metrics_" . Carbon::now()->format('Y-m-d-H');
        
        return Cache::remember($cacheKey, 1800, function () {
            // User activity metrics
            $totalUsers = User::count();
            $activeUsers = User::where('last_login_at', '>=', Carbon::now()->subDays(7))->count();
            
            // Recent activity
            $recentMissions = Mission::where('created_at', '>=', Carbon::now()->subDays(7))->count();
            $recentChecklists = Checklist::where('created_at', '>=', Carbon::now()->subDays(7))->count();
            
            // Storage metrics (if file metadata exists)
            $totalFiles = 0;
            $totalFileSize = 0;
            
            if (class_exists('App\Models\FileMetadata')) {
                $fileStats = \App\Models\FileMetadata::selectRaw('COUNT(*) as count, SUM(size) as total_size')->first();
                $totalFiles = $fileStats->count ?? 0;
                $totalFileSize = $fileStats->total_size ?? 0;
            }

            return [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'user_activity_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0,
                'recent_missions' => $recentMissions,
                'recent_checklists' => $recentChecklists,
                'total_files' => $totalFiles,
                'total_file_size_mb' => round($totalFileSize / 1024 / 1024, 2),
            ];
        });
    }

    /**
     * Get comprehensive dashboard data
     */
    public function getDashboardData(Carbon $startDate = null, Carbon $endDate = null): array
    {
        return [
            'mission_metrics' => $this->getMissionMetrics($startDate, $endDate),
            'user_performance' => $this->getUserPerformanceMetrics($startDate, $endDate),
            'property_metrics' => $this->getPropertyMetrics($startDate, $endDate),
            'maintenance_metrics' => $this->getMaintenanceMetrics($startDate, $endDate),
            'system_metrics' => $this->getSystemMetrics(),
            'generated_at' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(): void
    {
        $patterns = [
            'mission_metrics_*',
            'user_performance_*',
            'property_metrics_*',
            'maintenance_metrics_*',
            'system_metrics_*',
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Get trending data for charts
     */
    public function getTrendingData(string $metric, Carbon $startDate, Carbon $endDate, string $interval = 'daily'): array
    {
        $cacheKey = "trending_{$metric}_{$interval}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 1800, function () use ($metric, $startDate, $endDate, $interval) {
            $dateFormat = $interval === 'daily' ? '%Y-%m-%d' : '%Y-%m';
            
            switch ($metric) {
                case 'missions_created':
                    return Mission::selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as date, COUNT(*) as count")
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get()
                        ->toArray();
                        
                case 'missions_completed':
                    return Mission::selectRaw("DATE_FORMAT(updated_at, '{$dateFormat}') as date, COUNT(*) as count")
                        ->where('status', 'completed')
                        ->whereBetween('updated_at', [$startDate, $endDate])
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get()
                        ->toArray();
                        
                case 'checklists_completed':
                    return Checklist::selectRaw("DATE_FORMAT(submitted_at, '{$dateFormat}') as date, COUNT(*) as count")
                        ->where('status', 'completed')
                        ->whereBetween('submitted_at', [$startDate, $endDate])
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get()
                        ->toArray();
                        
                default:
                    return [];
            }
        });
    }
}