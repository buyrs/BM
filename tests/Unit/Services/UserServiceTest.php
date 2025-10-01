<?php

namespace Tests\Unit\Services;

use App\Services\UserService;
use App\Services\CacheService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;
    private $mockCacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockCacheService = Mockery::mock(CacheService::class);
        $this->userService = new UserService($this->mockCacheService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_users_by_roles()
    {
        $user = User::factory()->create(['role' => 'checker']);
        
        $result = $this->userService->getUsersByRoles(['checker'], false);
        
        $this->assertCount(1, $result);
        $this->assertEquals($user->id, $result->first()->id);
    }

    public function test_get_checkers()
    {
        $checker = User::factory()->create(['role' => 'checker']);
        User::factory()->create(['role' => 'admin']); // This should not be included
        
        $result = $this->userService->getCheckers(false);
        
        $this->assertCount(1, $result);
        $this->assertEquals($checker->id, $result->first()->id);
        $this->assertEquals('checker', $result->first()->role);
    }

    public function test_get_ops()
    {
        $ops = User::factory()->create(['role' => 'ops']);
        User::factory()->create(['role' => 'checker']); // This should not be included
        
        $result = $this->userService->getOps(false);
        
        $this->assertCount(1, $result);
        $this->assertEquals($ops->id, $result->first()->id);
        $this->assertEquals('ops', $result->first()->role);
    }

    public function test_get_admins()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'checker']); // This should not be included
        
        $result = $this->userService->getAdmins(false);
        
        $this->assertCount(1, $result);
        $this->assertEquals($admin->id, $result->first()->id);
        $this->assertEquals('admin', $result->first()->role);
    }

    public function test_get_user_by_id()
    {
        $user = User::factory()->create();
        
        $result = $this->userService->getUserById($user->id, false);
        
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals($user->name, $result->name);
        $this->assertEquals($user->email, $result->email);
    }

    public function test_get_user_by_id_not_found()
    {
        $result = $this->userService->getUserById(999999, false);
        
        $this->assertNull($result);
    }

    public function test_count_users_by_role()
    {
        User::factory()->count(3)->create(['role' => 'checker']);
        User::factory()->create(['role' => 'admin']);
        
        $result = $this->userService->countUsersByRole('checker');
        
        $this->assertEquals(3, $result);
    }
}