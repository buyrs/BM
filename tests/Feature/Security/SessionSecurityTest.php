<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\SessionSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    private SessionSecurityService $sessionSecurityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionSecurityService = app(SessionSecurityService::class);
        
        // Clear Redis before each test
        Redis::flushall();
    }

    /** @test */
    public function tracks_user_login_information()
    {
        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        $userAgent = 'Mozilla/5.0 Test Browser';

        $this->actingAs($user);
        Session::start();

        $this->sessionSecurityService->trackLogin($user->id, $ipAddress, $userAgent);

        // Check session data
        $this->assertEquals($ipAddress, Session::get('security.ip_address'));
        $this->assertEquals($userAgent, Session::get('security.user_agent'));
        $this->assertNotNull(Session::get('security.login_time'));
        $this->assertNotNull(Session::get('security.last_activity'));

        // Check Redis tracking
        $sessionKey = "user_sessions:{$user->id}";
        $sessionData = Redis::hget($sessionKey, Session::getId());
        $this->assertNotNull($sessionData);

        $data = json_decode($sessionData, true);
        $this->assertEquals(Session::getId(), $data['session_id']);
        $this->assertEquals($ipAddress, $data['ip_address']);
        $this->assertEquals($userAgent, $data['user_agent']);
    }

    /** @test */
    public function validates_session_security_successfully()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Session::start();

        // Set up session security data
        Session::put('security.ip_address', '192.168.1.1');
        Session::put('security.user_agent', 'Test Browser');
        Session::put('security.last_activity', now()->timestamp);

        // Mock request data
        $this->app['request']->server->set('REMOTE_ADDR', '192.168.1.1');
        $this->app['request']->headers->set('User-Agent', 'Test Browser');

        $isValid = $this->sessionSecurityService->validateSession();

        $this->assertTrue($isValid);
        $this->assertNotNull(Session::get('security.last_activity'));
    }

    /** @test */
    public function invalidates_session_on_user_agent_change()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Session::start();

        // Set up session with original user agent
        Session::put('security.ip_address', '192.168.1.1');
        Session::put('security.user_agent', 'Original Browser');
        Session::put('security.last_activity', now()->timestamp);

        // Mock different user agent
        $this->app['request']->server->set('REMOTE_ADDR', '192.168.1.1');
        $this->app['request']->headers->set('User-Agent', 'Different Browser');

        $isValid = $this->sessionSecurityService->validateSession();

        $this->assertFalse($isValid);
        $this->assertFalse(Auth::check());
    }

    /** @test */
    public function invalidates_session_on_timeout()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Session::start();

        // Set up session with old activity time
        Session::put('security.ip_address', '192.168.1.1');
        Session::put('security.user_agent', 'Test Browser');
        Session::put('security.last_activity', now()->subHours(2)->timestamp);

        // Mock current request
        $this->app['request']->server->set('REMOTE_ADDR', '192.168.1.1');
        $this->app['request']->headers->set('User-Agent', 'Test Browser');

        // Set max inactivity to 1 hour
        config(['session.max_inactivity' => 3600]);

        $isValid = $this->sessionSecurityService->validateSession();

        $this->assertFalse($isValid);
        $this->assertFalse(Auth::check());
    }

    /** @test */
    public function gets_active_sessions_for_user()
    {
        $user = User::factory()->create();
        
        // Create multiple sessions
        $sessions = [
            [
                'session_id' => 'session1',
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Browser 1',
                'login_time' => now()->subHour()->timestamp,
                'last_activity' => now()->subMinutes(30)->timestamp,
            ],
            [
                'session_id' => 'session2',
                'ip_address' => '192.168.1.2',
                'user_agent' => 'Browser 2',
                'login_time' => now()->subMinutes(30)->timestamp,
                'last_activity' => now()->subMinutes(10)->timestamp,
            ]
        ];

        $sessionKey = "user_sessions:{$user->id}";
        foreach ($sessions as $session) {
            Redis::hset($sessionKey, $session['session_id'], json_encode($session));
        }

        $activeSessions = $this->sessionSecurityService->getActiveSessions($user->id);

        $this->assertCount(2, $activeSessions);
        
        // Should be sorted by last activity (most recent first)
        $this->assertEquals('session2', $activeSessions[0]['session_id']);
        $this->assertEquals('session1', $activeSessions[1]['session_id']);
    }

    /** @test */
    public function terminates_specific_session()
    {
        $user = User::factory()->create();
        
        // Create sessions
        $sessionKey = "user_sessions:{$user->id}";
        Redis::hset($sessionKey, 'session1', json_encode(['data' => 'test1']));
        Redis::hset($sessionKey, 'session2', json_encode(['data' => 'test2']));

        $terminated = $this->sessionSecurityService->terminateSession($user->id, 'session1');

        $this->assertTrue($terminated);
        $this->assertNull(Redis::hget($sessionKey, 'session1'));
        $this->assertNotNull(Redis::hget($sessionKey, 'session2'));
    }

    /** @test */
    public function terminates_other_sessions_except_current()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Session::start();
        
        $currentSessionId = Session::getId();
        
        // Create multiple sessions
        $sessionKey = "user_sessions:{$user->id}";
        Redis::hset($sessionKey, $currentSessionId, json_encode(['data' => 'current']));
        Redis::hset($sessionKey, 'session1', json_encode(['data' => 'other1']));
        Redis::hset($sessionKey, 'session2', json_encode(['data' => 'other2']));

        $terminated = $this->sessionSecurityService->terminateOtherSessions($user->id);

        $this->assertEquals(2, $terminated);
        $this->assertNotNull(Redis::hget($sessionKey, $currentSessionId));
        $this->assertNull(Redis::hget($sessionKey, 'session1'));
        $this->assertNull(Redis::hget($sessionKey, 'session2'));
    }

    /** @test */
    public function detects_suspicious_activity_multiple_ips()
    {
        $user = User::factory()->create();
        
        // Create sessions from multiple IPs
        $sessions = [];
        for ($i = 1; $i <= 5; $i++) {
            $sessions[] = [
                'session_id' => "session{$i}",
                'ip_address' => "192.168.1.{$i}",
                'user_agent' => 'Browser',
                'login_time' => now()->timestamp,
                'last_activity' => now()->timestamp,
            ];
        }

        $sessionKey = "user_sessions:{$user->id}";
        foreach ($sessions as $session) {
            Redis::hset($sessionKey, $session['session_id'], json_encode($session));
        }

        $suspiciousActivities = $this->sessionSecurityService->checkSuspiciousActivity($user->id);

        $this->assertNotEmpty($suspiciousActivities);
        $this->assertEquals('multiple_ips', $suspiciousActivities[0]['type']);
        $this->assertStringContainsString('Multiple concurrent sessions', $suspiciousActivities[0]['message']);
    }

    /** @test */
    public function detects_suspicious_activity_rapid_sessions()
    {
        $user = User::factory()->create();
        
        // Create many recent sessions
        $sessions = [];
        for ($i = 1; $i <= 6; $i++) {
            $sessions[] = [
                'session_id' => "session{$i}",
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Browser',
                'login_time' => now()->subMinutes(2)->timestamp, // Within last 5 minutes
                'last_activity' => now()->timestamp,
            ];
        }

        $sessionKey = "user_sessions:{$user->id}";
        foreach ($sessions as $session) {
            Redis::hset($sessionKey, $session['session_id'], json_encode($session));
        }

        $suspiciousActivities = $this->sessionSecurityService->checkSuspiciousActivity($user->id);

        $this->assertNotEmpty($suspiciousActivities);
        $this->assertEquals('rapid_sessions', $suspiciousActivities[0]['type']);
        $this->assertStringContainsString('Rapid session creation', $suspiciousActivities[0]['message']);
    }

    /** @test */
    public function cleans_up_expired_sessions()
    {
        $user = User::factory()->create();
        
        // Create expired and active sessions
        $sessionKey = "user_sessions:{$user->id}";
        
        // Expired session
        $expiredSession = [
            'session_id' => 'expired_session',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Browser',
            'login_time' => now()->subHours(3)->timestamp,
            'last_activity' => now()->subHours(3)->timestamp,
        ];
        
        // Active session
        $activeSession = [
            'session_id' => 'active_session',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Browser',
            'login_time' => now()->subMinutes(30)->timestamp,
            'last_activity' => now()->subMinutes(10)->timestamp,
        ];

        Redis::hset($sessionKey, 'expired_session', json_encode($expiredSession));
        Redis::hset($sessionKey, 'active_session', json_encode($activeSession));

        // Set session lifetime to 2 hours
        config(['session.lifetime' => 120]);

        $cleaned = $this->sessionSecurityService->cleanupExpiredSessions();

        $this->assertEquals(1, $cleaned);
        $this->assertNull(Redis::hget($sessionKey, 'expired_session'));
        $this->assertNotNull(Redis::hget($sessionKey, 'active_session'));
    }

    /** @test */
    public function invalidates_session_logs_security_violation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Session::start();

        // Track the session first
        $sessionKey = "user_sessions:{$user->id}";
        Redis::hset($sessionKey, Session::getId(), json_encode(['test' => 'data']));

        $this->sessionSecurityService->invalidateSession('Test security violation');

        // Check that session is invalidated
        $this->assertFalse(Auth::check());
        
        // Check that session is removed from Redis
        $this->assertNull(Redis::hget($sessionKey, Session::getId()));
    }

    /** @test */
    public function updates_session_activity_in_redis()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Session::start();

        // Set up initial session data
        $sessionKey = "user_sessions:{$user->id}";
        $initialData = [
            'session_id' => Session::getId(),
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Browser',
            'login_time' => now()->subHour()->timestamp,
            'last_activity' => now()->subHour()->timestamp,
        ];
        Redis::hset($sessionKey, Session::getId(), json_encode($initialData));

        // Set up session security data for validation
        Session::put('security.ip_address', '192.168.1.1');
        Session::put('security.user_agent', 'Browser');
        Session::put('security.last_activity', now()->subMinutes(30)->timestamp);

        // Mock request
        $this->app['request']->server->set('REMOTE_ADDR', '192.168.1.1');
        $this->app['request']->headers->set('User-Agent', 'Browser');

        // Validate session (this should update activity)
        $this->sessionSecurityService->validateSession();

        // Check that last_activity was updated
        $updatedData = json_decode(Redis::hget($sessionKey, Session::getId()), true);
        $this->assertGreaterThan($initialData['last_activity'], $updatedData['last_activity']);
    }

    /** @test */
    public function handles_unauthenticated_session_validation()
    {
        $isValid = $this->sessionSecurityService->validateSession();

        $this->assertFalse($isValid);
    }

    /** @test */
    public function terminates_current_session_when_requested()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Session::start();

        $currentSessionId = Session::getId();
        
        // Set up session in Redis
        $sessionKey = "user_sessions:{$user->id}";
        Redis::hset($sessionKey, $currentSessionId, json_encode(['data' => 'test']));

        $terminated = $this->sessionSecurityService->terminateSession($user->id, $currentSessionId);

        $this->assertTrue($terminated);
        $this->assertFalse(Auth::check());
        $this->assertNull(Redis::hget($sessionKey, $currentSessionId));
    }

    protected function tearDown(): void
    {
        // Clean up Redis after each test
        Redis::flushall();
        parent::tearDown();
    }
}