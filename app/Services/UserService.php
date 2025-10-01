<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserService
{
    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Get users with roles, with caching
     *
     * @param array $roles
     * @param bool $useCache
     * @return Collection
     */
    public function getUsersByRoles(array $roles, bool $useCache = true): Collection
    {
        $cacheKey = 'users_by_roles_' . md5(serialize($roles));
        
        if ($useCache) {
            $cached = $this->cacheService->get($cacheKey);
            if ($cached) {
                return collect($cached)->map(function ($userData) {
                    $user = new User();
                    $user->forceFill($userData);
                    return $user;
                });
            }
        }

        $users = User::whereIn('role', $roles)->get();
        
        if ($useCache) {
            $this->cacheService->put($cacheKey, $users->toArray(), 1800); // 30 minutes
        }

        return $users;
    }

    /**
     * Get all checkers with caching
     *
     * @param bool $useCache
     * @return Collection
     */
    public function getCheckers(bool $useCache = true): Collection
    {
        return $this->getUsersByRoles(['checker'], $useCache);
    }

    /**
     * Get all ops with caching
     *
     * @param bool $useCache
     * @return Collection
     */
    public function getOps(bool $useCache = true): Collection
    {
        return $this->getUsersByRoles(['ops'], $useCache);
    }

    /**
     * Get all admins with caching
     *
     * @param bool $useCache
     * @return Collection
     */
    public function getAdmins(bool $useCache = true): Collection
    {
        return $this->getUsersByRoles(['admin', 'administrators'], $useCache);
    }

    /**
     * Get user by ID with caching
     *
     * @param int $id
     * @param bool $useCache
     * @return User|null
     */
    public function getUserById(int $id, bool $useCache = true): ?User
    {
        $cacheKey = "user_{$id}";
        
        if ($useCache) {
            $cached = $this->cacheService->get($cacheKey);
            if ($cached) {
                $user = new User();
                $user->forceFill($cached);
                return $user;
            }
        }

        $user = User::find($id);
        
        if ($user && $useCache) {
            $this->cacheService->put($cacheKey, $user->toArray(), 3600); // 1 hour
        }

        return $user;
    }

    /**
     * Clear user cache
     *
     * @param int $id
     * @return void
     */
    public function clearUserCache(int $id): void
    {
        $this->cacheService->forget("user_{$id}");
        
        // Clear related cache entries
        $this->cacheService->clearUserPermissionCache($id);
        $this->cacheService->clearUserRoleCache($id);
    }

    /**
     * Clear users by roles cache
     *
     * @param array $roles
     * @return void
     */
    public function clearUsersByRolesCache(array $roles): void
    {
        $cacheKey = 'users_by_roles_' . md5(serialize($roles));
        $this->cacheService->forget($cacheKey);
    }

    /**
     * Get users with role and additional relationship data
     *
     * @param array $roles
     * @param array $with
     * @param bool $useCache
     * @return Collection
     */
    public function getUsersByRolesWith(array $roles, array $with = [], bool $useCache = true): Collection
    {
        $cacheKey = 'users_by_roles_with_' . md5(serialize([$roles, $with]));
        
        if ($useCache) {
            $cached = $this->cacheService->get($cacheKey);
            if ($cached) {
                return collect($cached)->map(function ($userData) use ($with) {
                    $user = new User();
                    $user->forceFill($userData);
                    // Note: Relations can't be fully reconstructed from cache without more complex logic
                    return $user;
                });
            }
        }

        $query = User::whereIn('role', $roles);
        
        if (!empty($with)) {
            $query = $query->with($with);
        }

        $users = $query->get();
        
        if ($useCache) {
            $this->cacheService->put($cacheKey, $users->toArray(), 1800); // 30 minutes
        }

        return $users;
    }

    /**
     * Count users by role
     *
     * @param string $role
     * @return int
     */
    public function countUsersByRole(string $role): int
    {
        $cacheKey = "count_users_by_role_{$role}";
        
        return $this->cacheService->remember($cacheKey, 3600, function () use ($role) {
            return User::where('role', $role)->count();
        });
    }

    /**
     * Get all active users in the last 24 hours
     *
     * @return Collection
     */
    public function getActiveUsers(): Collection
    {
        $cacheKey = 'active_users_last_24h';

        return $this->cacheService->remember($cacheKey, 1800, function () { // 30 minutes
            return User::where('last_login_at', '>=', now()->subDay())->get();
        });
    }
}