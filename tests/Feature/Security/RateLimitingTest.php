<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test routes with rate limiting
        Route::middleware(['api', 'auth:sanctum', 'throttle:5,1'])->group(function () {
            Route::get('/test-rate-limit', function () {
                return response()->json(['message' => 'success']);
            })->name('test.rate-limit');
        });

        Route::middleware(['api', 'throttle:3,1'])->group(function () {
            Route::get('/test-rate-limit-unauthenticated', function () {
                return response()->json(['message' => 'success']);
            })->name('test.rate-limit.unauthenticated');
        });
    }

    /** @test */
    public function authenticated_user_can_make_requests_within_rate_limit()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Make 5 requests (within limit)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get('/test-rate-limit');

            $response->assertStatus(200);
            $response->assertJson(['message' => 'success']);
            
            // Check rate limit headers
            $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
            $this->assertNotNull($response->headers->get('X-RateLimit-Remaining'));
            $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));
        }
    }

    /** @test */
    public function authenticated_user_gets_rate_limited_when_exceeding_limit()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Make 5 requests (at limit)
        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get('/test-rate-limit');
        }

        // 6th request should be rate limited
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->get('/test-rate-limit');

        $response->assertStatus(429);
        $response->assertJsonStructure([
            'error' => [
                'code',
                'message',
                'retry_after'
            ]
        ]);
        
        // Check rate limit headers
        $this->assertEquals('0', $response->headers->get('X-RateLimit-Remaining'));
        $this->assertNotNull($response->headers->get('Retry-After'));
    }

    /** @test */
    public function unauthenticated_requests_are_rate_limited_by_ip()
    {
        // Make 3 requests (at limit)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->get('/test-rate-limit-unauthenticated');

            $response->assertStatus(200);
        }

        // 4th request should be rate limited
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/test-rate-limit-unauthenticated');

        $response->assertStatus(429);
        $response->assertJsonStructure([
            'error' => [
                'code',
                'message',
                'retry_after'
            ]
        ]);
    }

    /** @test */
    public function different_users_have_separate_rate_limits()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token1 = $user1->createToken('test-token')->plainTextToken;
        $token2 = $user2->createToken('test-token')->plainTextToken;

        // User 1 makes 5 requests (at limit)
        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $token1,
                'Accept' => 'application/json',
            ])->get('/test-rate-limit');
        }

        // User 1's next request should be rate limited
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
            'Accept' => 'application/json',
        ])->get('/test-rate-limit');
        $response1->assertStatus(429);

        // User 2 should still be able to make requests
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
            'Accept' => 'application/json',
        ])->get('/test-rate-limit');
        $response2->assertStatus(200);
    }

    /** @test */
    public function rate_limit_resets_after_time_window()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Make 5 requests (at limit)
        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get('/test-rate-limit');
        }

        // Next request should be rate limited
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->get('/test-rate-limit');
        $response->assertStatus(429);

        // Simulate time passing by clearing Redis (in real scenario, we'd wait)
        Redis::flushall();

        // Should be able to make requests again
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->get('/test-rate-limit');
        $response->assertStatus(200);
    }

    /** @test */
    public function rate_limit_headers_are_correctly_set()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->get('/test-rate-limit');

        $response->assertStatus(200);
        
        // Check that all required headers are present
        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Remaining'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));
        
        // Check header values
        $this->assertEquals('5', $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('4', $response->headers->get('X-RateLimit-Remaining'));
        $this->assertIsNumeric($response->headers->get('X-RateLimit-Reset'));
    }

    /** @test */
    public function api_rate_limiter_middleware_handles_different_routes_separately()
    {
        // Create additional test route with different limits
        Route::middleware(['api', 'auth:sanctum', 'throttle:10,1'])->group(function () {
            Route::get('/test-rate-limit-higher', function () {
                return response()->json(['message' => 'success']);
            })->name('test.rate-limit.higher');
        });

        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Exhaust limit on first route
        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get('/test-rate-limit');
        }

        // First route should be rate limited
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->get('/test-rate-limit');
        $response1->assertStatus(429);

        // Second route should still work (different rate limit)
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->get('/test-rate-limit-higher');
        $response2->assertStatus(200);
    }

    /** @test */
    public function rate_limit_error_response_has_correct_format()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Exhaust rate limit
        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get('/test-rate-limit');
        }

        // Get rate limited response
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->get('/test-rate-limit');

        $response->assertStatus(429);
        $response->assertJsonStructure([
            'error' => [
                'code',
                'message',
                'retry_after'
            ]
        ]);

        $data = $response->json();
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $data['error']['code']);
        $this->assertStringContainsString('Too many requests', $data['error']['message']);
        $this->assertIsInt($data['error']['retry_after']);
        $this->assertGreaterThan(0, $data['error']['retry_after']);
    }

    /** @test */
    public function rate_limiter_middleware_exists_and_is_functional()
    {
        // Test that the middleware class exists and can be instantiated
        $middleware = new \App\Http\Middleware\ApiRateLimiter();
        $this->assertInstanceOf(\App\Http\Middleware\ApiRateLimiter::class, $middleware);
        
        // Test that the middleware has the required methods
        $this->assertTrue(method_exists($middleware, 'handle'));
        $this->assertTrue(method_exists($middleware, 'clearRateLimit'));
    }
}