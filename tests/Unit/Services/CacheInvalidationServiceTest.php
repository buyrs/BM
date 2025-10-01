<?php

namespace Tests\Unit\Services;

use App\Services\CacheInvalidationService;
use App\Models\User;
use App\Models\Mission;
use App\Models\Checklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CacheInvalidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private CacheInvalidationService $cacheInvalidationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheInvalidationService = new CacheInvalidationService();
    }

    public function test_invalidate_user_cache()
    {
        $user = User::factory()->create();
        
        // This should not throw any exceptions
        $this->cacheInvalidationService->invalidateUserCache($user);
        
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    public function test_invalidate_role_cache()
    {
        $roleName = 'test_role';
        
        // This should not throw any exceptions
        $this->cacheInvalidationService->invalidateRoleCache($roleName);
        
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    public function test_invalidate_mission_cache()
    {
        $mission = Mission::factory()->create();
        
        // This should not throw any exceptions
        $this->cacheInvalidationService->invalidateMissionCache($mission);
        
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    public function test_invalidate_checklist_cache()
    {
        $checklist = Checklist::factory()->create();
        
        // This should not throw any exceptions
        $this->cacheInvalidationService->invalidateChecklistCache($checklist);
        
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    public function test_invalidate_dashboard_cache()
    {
        $userId = 1;
        
        // This should not throw any exceptions
        $this->cacheInvalidationService->invalidateDashboardCache($userId);
        
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    public function test_invalidate_all_cache()
    {
        // This should not throw any exceptions
        $this->cacheInvalidationService->invalidateAllCache();
        
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }
}