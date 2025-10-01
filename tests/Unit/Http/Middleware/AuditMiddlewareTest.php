<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\AuditMiddleware;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected AuditMiddleware $middleware;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AuditMiddleware(new AuditLogger());
        $this->user = User::factory()->create(['role' => 'admin']);
    }

    #[Test]
    public function it_logs_post_requests()
    {
        Auth::login($this->user);
        
        $request = Request::create('/properties', 'POST', [
            'name' => 'Test Property',
            'address' => '123 Test St'
        ]);
        $request->setRouteResolver(function () {
            return new \Illuminate\Routing\Route('POST', '/properties', []);
        });

        $response = new Response('Created', 201);

        $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $auditLog = AuditLog::where('action', 'create_attempt')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('POST', $auditLog->changes['method']);
        $this->assertEquals(201, $auditLog->changes['status_code']);
        $this->assertArrayHasKey('request_data', $auditLog->changes);
        $this->assertEquals('Test Property', $auditLog->changes['request_data']['name']);
    }

    #[Test]
    public function it_logs_put_requests()
    {
        Auth::login($this->user);
        
        $request = Request::create('/properties/1', 'PUT', [
            'name' => 'Updated Property'
        ]);

        $response = new Response('Updated', 200);

        $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $auditLog = AuditLog::where('action', 'update_attempt')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('PUT', $auditLog->changes['method']);
    }

    #[Test]
    public function it_logs_delete_requests()
    {
        Auth::login($this->user);
        
        $request = Request::create('/properties/1', 'DELETE');
        $response = new Response('Deleted', 200);

        $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $auditLog = AuditLog::where('action', 'delete_attempt')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('DELETE', $auditLog->changes['method']);
    }

    #[Test]
    public function it_skips_get_requests()
    {
        Auth::login($this->user);
        
        $request = Request::create('/properties', 'GET');
        $response = new Response('OK', 200);

        $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertEquals(0, AuditLog::count());
    }

    #[Test]
    public function it_skips_excluded_routes()
    {
        Auth::login($this->user);
        
        $excludedRoutes = [
            '/api/health',
            '/api/status',
            '/_debugbar/test',
            '/telescope/test'
        ];

        foreach ($excludedRoutes as $route) {
            $request = Request::create($route, 'POST');
            $response = new Response('OK', 200);

            $this->middleware->handle($request, function () use ($response) {
                return $response;
            });
        }

        $this->assertEquals(0, AuditLog::count());
    }

    #[Test]
    public function it_detects_login_attempts()
    {
        Auth::login($this->user);
        
        $request = Request::create('/login', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);
        $response = new Response('OK', 200);

        $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $auditLog = AuditLog::where('action', 'login_attempt')->first();
        $this->assertNotNull($auditLog);
        $this->assertStringContainsString('/login', $auditLog->changes['url']);
    }

    #[Test]
    public function it_detects_logout_attempts()
    {
        Auth::login($this->user);
        
        $request = Request::create('/logout', 'POST');
        $response = new Response('OK', 200);

        $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $auditLog = AuditLog::where('action', 'logout_attempt')->first();
        $this->assertNotNull($auditLog);
    }

    #[Test]
    public function it_excludes_sensitive_fields_from_request_data()
    {
        Auth::login($this->user);
        
        $request = Request::create('/users', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'current_password' => 'oldsecret',
            'two_factor_secret' => 'ABCD1234',
            '_token' => 'csrf_token',
            '_method' => 'POST'
        ]);
        $response = new Response('Created', 201);

        $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $auditLog = AuditLog::where('action', 'create_attempt')->first();
        $requestData = $auditLog->changes['request_data'];

        $this->assertEquals('John Doe', $requestData['name']);
        $this->assertEquals('john@example.com', $requestData['email']);
        $this->assertArrayNotHasKey('password', $requestData);
        $this->assertArrayNotHasKey('password_confirmation', $requestData);
        $this->assertArrayNotHasKey('current_password', $requestData);
        $this->assertArrayNotHasKey('two_factor_secret', $requestData);
        $this->assertArrayNotHasKey('_token', $requestData);
        $this->assertArrayNotHasKey('_method', $requestData);
    }

    #[Test]
    public function it_logs_sensitive_data_access_for_sensitive_routes()
    {
        Auth::login($this->user);
        
        $sensitiveRoutes = [
            '/users/1' => 'user_data',
            '/admin/dashboard' => 'admin_panel',
            '/password/change' => 'password_data',
            '/two-factor/verify' => 'two_factor_data'
        ];

        foreach ($sensitiveRoutes as $route => $expectedDataType) {
            $request = Request::create($route, 'POST');
            $response = new Response('OK', 200);

            $this->middleware->handle($request, function () use ($response) {
                return $response;
            });

            $sensitiveLog = AuditLog::where('action', 'sensitive_data_access')
                ->where('changes->data_type', $expectedDataType)
                ->first();
            
            $this->assertNotNull($sensitiveLog, "Failed to log sensitive access for route: {$route}");
        }
    }

    #[Test]
    public function it_captures_request_ip_and_user_agent()
    {
        Auth::login($this->user);
        
        $request = Request::create('/properties', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.100',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Test Browser'
        ]);
        $response = new Response('Created', 201);

        $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $auditLog = AuditLog::where('action', 'create_attempt')->first();
        $this->assertEquals('192.168.1.100', $auditLog->ip_address);
        $this->assertEquals('Mozilla/5.0 Test Browser', $auditLog->user_agent);
    }

    #[Test]
    public function it_works_without_authenticated_user()
    {
        $request = Request::create('/public/endpoint', 'POST', [
            'data' => 'test'
        ]);
        $response = new Response('OK', 200);

        $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $auditLog = AuditLog::where('action', 'create_attempt')->first();
        $this->assertNotNull($auditLog);
        $this->assertNull($auditLog->user_id);
        $this->assertEquals('test', $auditLog->changes['request_data']['data']);
    }

    #[Test]
    public function it_handles_audit_logging_errors_gracefully()
    {
        Auth::login($this->user);

        // Mock AuditLogger to throw an exception
        $mockAuditLogger = $this->createMock(AuditLogger::class);
        $mockAuditLogger->method('log')->willThrowException(new \Exception('Audit logging failed'));
        
        $middleware = new AuditMiddleware($mockAuditLogger);
        
        $request = Request::create('/properties', 'POST');
        $response = new Response('OK', 200);

        // Should not throw exception
        $result = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertEquals($response, $result);
        
        // The middleware should handle the exception gracefully
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    #[Test]
    public function it_handles_empty_request_data()
    {
        Auth::login($this->user);
        
        $request = Request::create('/properties', 'POST'); // No data
        $response = new Response('Created', 201);

        $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $auditLog = AuditLog::where('action', 'create_attempt')->first();
        $this->assertNotNull($auditLog);
        $this->assertArrayNotHasKey('request_data', $auditLog->changes);
    }
}