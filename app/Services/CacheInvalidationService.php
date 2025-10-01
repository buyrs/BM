<?php

namespace App\Services;

use App\Models\User;
use App\Models\Mission;
use App\Models\Checklist;
use Illuminate\Support\Facades\Cache;

class CacheInvalidationService
{
    /**
     * Invalidate user-related caches when user is updated
     *
     * @param User $user
     * @return void
     */
    public function invalidateUserCache(User $user): void
    {
        // Clear user permissions cache
        app(CacheService::class)->clearUserPermissionCache($user->id);
        
        // Clear user roles cache
        app(CacheService::class)->clearUserRoleCache($user->id);
        
        // Clear mission summary cache for this user
        app(CacheService::class)->forgetWithConfig('mission_summary', ['user_id' => $user->id]);
        
        // Clear dashboard stats for this user
        Cache::tags(['dashboard', 'user_' . $user->id])->flush();
    }

    /**
     * Invalidate role-related caches when role is updated
     *
     * @param string $roleName
     * @return void
     */
    public function invalidateRoleCache(string $roleName): void
    {
        // Clear role permissions cache
        app(CacheService::class)->clearRolePermissionCache($roleName);
        
        // Also clear all user permission caches for users with this role
        // In a real implementation, you might want to tag these caches
        // and flush them by tag instead of clearing individual caches
    }

    /**
     * Invalidate mission-related caches when mission is updated
     *
     * @param Mission $mission
     * @return void
     */
    public function invalidateMissionCache(Mission $mission): void
    {
        // Clear mission summary cache for checker assigned to this mission
        if ($mission->checker_id) {
            app(CacheService::class)->forgetWithConfig('mission_summary', ['user_id' => $mission->checker_id]);
        }
        
        // Clear mission summary cache for ops assigned to this mission
        if ($mission->ops_id) {
            app(CacheService::class)->forgetWithConfig('mission_summary', ['user_id' => $mission->ops_id]);
        }
        
        // Clear mission summary cache for admin who created this mission
        if ($mission->admin_id) {
            app(CacheService::class)->forgetWithConfig('mission_summary', ['user_id' => $mission->admin_id]);
        }
        
        // Clear dashboard stats for all related users
        Cache::tags(['missions'])->flush();
    }

    /**
     * Invalidate checklist-related caches when checklist is updated
     *
     * @param Checklist $checklist
     * @return void
     */
    public function invalidateChecklistCache(Checklist $checklist): void
    {
        // Clear mission summary cache for the mission this checklist belongs to
        if ($checklist->mission) {
            if ($checklist->mission->checker_id) {
                app(CacheService::class)->forgetWithConfig('mission_summary', ['user_id' => $checklist->mission->checker_id]);
            }
            if ($checklist->mission->ops_id) {
                app(CacheService::class)->forgetWithConfig('mission_summary', ['user_id' => $checklist->mission->ops_id]);
            }
            if ($checklist->mission->admin_id) {
                app(CacheService::class)->forgetWithConfig('mission_summary', ['user_id' => $checklist->mission->admin_id]);
            }
        }
        
        // Clear dashboard stats for all related users
        Cache::tags(['checklists'])->flush();
    }

    /**
     * Invalidate dropdown caches
     *
     * @param string|null $type Specific dropdown type, or null for all
     * @return void
     */
    public function invalidateDropdownCache(?string $type = null): void
    {
        if ($type) {
            app(CacheService::class)->forgetWithConfig('dropdown_data', ['type' => $type]);
        } else {
            // Clear all dropdown caches by flushing related keys
            Cache::tags(['dropdowns'])->flush();
        }
    }

    /**
     * Invalidate dashboard caches for a user
     *
     * @param int $userId
     * @return void
     */
    public function invalidateDashboardCache(int $userId): void
    {
        // Clear dashboard stats for this user
        Cache::tags(['dashboard', 'user_' . $userId])->flush();
        
        // Clear mission summary for this user
        app(CacheService::class)->forgetWithConfig('mission_summary', ['user_id' => $userId]);
    }

    /**
     * Invalidate all caches
     *
     * @return void
     */
    public function invalidateAllCache(): void
    {
        Cache::flush();
    }

    /**
     * Invalidate configuration caches
     *
     * @param string|null $configKey Specific config key, or null for all config
     * @return void
     */
    public function invalidateConfigCache(?string $configKey = null): void
    {
        if ($configKey) {
            app(CacheService::class)->forgetWithConfig('config', ['key' => $configKey]);
        } else {
            // Clear all config caches
            Cache::tags(['config'])->flush();
        }
    }
}