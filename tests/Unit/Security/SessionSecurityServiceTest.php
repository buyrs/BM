<?php

namespace Tests\Unit\Security;

use App\Models\User;
use App\Services\SessionSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SessionSecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    private SessionSecurityService $sessionSecurityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionSecurityService = new SessionSecurityService();
    }

    /** @test */
    public function service_can_be_instantiated()
    {
        $this->assertInstanceOf(SessionSecurityService::class, $this->sessionSecurityService);
    }

    /** @test */
    public function validates_session_returns_false_for_unauthenticated_user()
    {
        $result = $this->sessionSecurityService->validateSession();
        
        $this->assertFalse($result);
    }

    /** @test */
    public function service_has_required_methods()
    {
        $methods = [
            'trackLogin',
            'validateSession',
            'invalidateSession',
            'getActiveSessions',
            'terminateSession',
            'terminateOtherSessions',
            'checkSuspiciousActivity',
            'cleanupExpiredSessions'
        ];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists($this->sessionSecurityService, $method),
                "Method {$method} should exist on SessionSecurityService"
            );
        }
    }

    /** @test */
    public function track_login_updates_user_last_login()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Session::start();

        $originalLastLogin = $user->last_login_at;
        
        $this->sessionSecurityService->trackLogin($user->id, '192.168.1.1', 'Test Browser');

        $user->refresh();
        $this->assertNotEquals($originalLastLogin, $user->last_login_at);
        $this->assertEquals(0, $user->login_attempts);
    }

    /** @test */
    public function validate_session_checks_user_agent_changes()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Session::start();

        // Set up session with original user agent
        Session::put('security.user_agent', 'Original Browser');
        Session::put('security.last_activity', now()->timestamp);

        // Mock different user agent in request
        $this->app['request']->headers->set('User-Agent', 'Different Browser');

        $result = $this->sessionSecurityService->validateSession();

        $this->assertFalse($result);
        $this->assertFalse(Auth::check());
    }

    /** @test */
    public function validate_session_checks_timeout()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Session::start();

        // Set up session with old activity time
        Session::put('security.user_agent', 'Test Browser');
        Session::put('security.last_activity', now()->subHours(2)->timestamp);

        // Mock current request
        $this->app['request']->headers->set('User-Agent', 'Test Browser');

        // Set max inactivity to 1 hour
        config(['session.max_inactivity' => 3600]);

        $result = $this->sessionSecurityService->validateSession();

        $this->assertFalse($result);
        $this->assertFalse(Auth::check());
    }

    /** @test */
    public function validate_session_succeeds_with_valid_conditions()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Session::start();

        // Set up valid session data
        Session::put('security.user_agent', 'Test Browser');
        Session::put('security.last_activity', now()->timestamp);

        // Mock matching request
        $this->app['request']->headers->set('User-Agent', 'Test Browser');

        $result = $this->sessionSecurityService->validateSession();

        $this->assertTrue($result);
        $this->assertTrue(Auth::check());
    }

    /** @test */
    public function invalidate_session_logs_out_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Session::start();

        $this->assertTrue(Auth::check());

        $this->sessionSecurityService->invalidateSession('Test reason');

        $this->assertFalse(Auth::check());
    }

    /** @test */
    public function check_suspicious_activity_returns_array()
    {
        $user = User::factory()->create();
        
        $result = $this->sessionSecurityService->checkSuspiciousActivity($user->id);

        $this->assertIsArray($result);
    }

    /** @test */
    public function cleanup_expired_sessions_returns_integer()
    {
        $result = $this->sessionSecurityService->cleanupExpiredSessions();

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    /** @test */
    public function get_active_sessions_returns_array()
    {
        $user = User::factory()->create();
        
        $result = $this->sessionSecurityService->getActiveSessions($user->id);

        $this->assertIsArray($result);
    }

    /** @test */
    public function terminate_session_returns_boolean()
    {
        $user = User::factory()->create();
        
        $result = $this->sessionSecurityService->terminateSession($user->id, 'test-session-id');

        $this->assertIsBool($result);
    }

    /** @test */
    public function terminate_other_sessions_returns_integer()
    {
        $user = User::factory()->create();
        
        $result = $this->sessionSecurityService->terminateOtherSessions($user->id);

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }
}