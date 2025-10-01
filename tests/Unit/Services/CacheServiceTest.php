<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CacheService;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Mockery;

class CacheServiceTest extends TestCase
{
    protected CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = new CacheService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_remember_data_without_tags()
    {
        $key = 'test_key';
        $value = 'test_value';
        $ttl = 3600;

        $result = $this->cacheService->remember($key, $ttl, function () use ($value) {
            return $value;
        });

        $this->assertEquals($value, $result);
        $this->assertEquals($value, Cache::get($key));
    }

    /** @test */
    public function it_can_remember_data_with_tags()
    {
        $key = 'test_key';
        $value = 'test_value';
        $ttl = 3600;
        $tags = ['users', 'test'];

        $result = $this->cacheService->remember($key, $ttl, function () use ($value) {
            return $value;
        }, $tags);

        $this->assertEquals($value, $result);
    }

    /** @test */
    public function it_handles_remember_exceptions_gracefully()
    {
        Log::shouldReceive('error')->once();

        // Mock Cache to throw exception
        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \Exception('Cache error'));

        $key = 'test_key';
        $value = 'fallback_value';

        $result = $this->cacheService->remember($key, 3600, function () use ($value) {
            return $value;
        });

        $this->assertEquals($value, $result);
    }

    /** @test */
    public function it_can_put_data_in_cache()
    {
        $key = 'test_put_key';
        $value = 'test_put_value';
        $ttl = 3600;

        $result = $this->cacheService->put($key, $value, $ttl);

        $this->assertTrue($result);
        $this->assertEquals($value, Cache::get($key));
    }

    /** @test */
    public function it_can_put_data_with_tags()
    {
        $key = 'test_put_key';
        $value = 'test_put_value';
        $ttl = 3600;
        $tags = ['users'];

        $result = $this->cacheService->put($key, $value, $ttl, $tags);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_put_exceptions()
    {
        Log::shouldReceive('error')->once();

        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new \Exception('Cache put error'));

        $result = $this->cacheService->put('test_key', 'test_value');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_data_from_cache()
    {
        $key = 'test_get_key';
        $value = 'test_get_value';
        
        Cache::put($key, $value, 3600);

        $result = $this->cacheService->get($key);

        $this->assertEquals($value, $result);
    }

    /** @test */
    public function it_returns_default_when_key_not_found()
    {
        $default = 'default_value';
        
        $result = $this->cacheService->get('nonexistent_key', $default);

        $this->assertEquals($default, $result);
    }

    /** @test */
    public function it_handles_get_exceptions()
    {
        Log::shouldReceive('error')->once();

        Cache::shouldReceive('get')
            ->once()
            ->andThrow(new \Exception('Cache get error'));

        $default = 'default_value';
        $result = $this->cacheService->get('test_key', $default);

        $this->assertEquals($default, $result);
    }

    /** @test */
    public function it_can_forget_cache_keys()
    {
        $key = 'test_forget_key';
        $value = 'test_forget_value';
        
        Cache::put($key, $value, 3600);
        $this->assertEquals($value, Cache::get($key));

        $result = $this->cacheService->forget($key);

        $this->assertTrue($result);
        $this->assertNull(Cache::get($key));
    }

    /** @test */
    public function it_handles_forget_exceptions()
    {
        Log::shouldReceive('error')->once();

        Cache::shouldReceive('forget')
            ->once()
            ->andThrow(new \Exception('Cache forget error'));

        $result = $this->cacheService->forget('test_key');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_flush_cache_by_tags()
    {
        $tags = ['users', 'test'];

        Cache::tags($tags)->put('tagged_key', 'tagged_value', 3600);
        $this->assertEquals('tagged_value', Cache::tags($tags)->get('tagged_key'));

        $result = $this->cacheService->flushTags($tags);

        $this->assertTrue($result);
        $this->assertNull(Cache::tags($tags)->get('tagged_key'));
    }

    /** @test */
    public function it_handles_flush_tags_exceptions()
    {
        Log::shouldReceive('error')->once();

        Cache::shouldReceive('tags')
            ->once()
            ->andThrow(new \Exception('Cache flush error'));

        $result = $this->cacheService->flushTags(['test']);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_generates_model_cache_keys()
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getKey')->andReturn(123);
        
        $key = $this->cacheService->modelKey($user);
        $expectedKey = 'user:123';

        $this->assertEquals($expectedKey, $key);
    }

    /** @test */
    public function it_generates_model_cache_keys_with_suffix()
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getKey')->andReturn(123);
        
        $key = $this->cacheService->modelKey($user, 'profile');
        $expectedKey = 'user:123:profile';

        $this->assertEquals($expectedKey, $key);
    }

    /** @test */
    public function it_generates_collection_cache_keys()
    {
        $key = $this->cacheService->collectionKey('User');
        $expectedKey = 'user:collection';

        $this->assertEquals($expectedKey, $key);
    }

    /** @test */
    public function it_generates_collection_cache_keys_with_params()
    {
        $params = ['role' => 'admin', 'active' => true];
        
        $key = $this->cacheService->collectionKey('User', $params);
        
        $this->assertStringStartsWith('user:collection:', $key);
        $this->assertStringContains(md5(serialize($params)), $key);
    }

    /** @test */
    public function it_can_cache_model_data()
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getKey')->andReturn(123);
        $user->shouldReceive('toArray')->andReturn([
            'id' => 123,
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $result = $this->cacheService->cacheModel($user);

        $this->assertTrue($result);
        
        $cachedData = $this->cacheService->getCachedModel(User::class, 123);
        $this->assertEquals('Test User', $cachedData['name']);
        $this->assertEquals('test@example.com', $cachedData['email']);
    }

    /** @test */
    public function it_can_get_cached_model_data()
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getKey')->andReturn(123);
        $user->shouldReceive('toArray')->andReturn([
            'id' => 123,
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $this->cacheService->cacheModel($user);
        
        $cachedData = $this->cacheService->getCachedModel(User::class, 123);

        $this->assertNotNull($cachedData);
        $this->assertEquals('Test User', $cachedData['name']);
    }

    /** @test */
    public function it_can_invalidate_model_cache()
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getKey')->andReturn(123);
        $user->shouldReceive('toArray')->andReturn([
            'id' => 123,
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $this->cacheService->cacheModel($user);
        
        // Verify data is cached
        $cachedData = $this->cacheService->getCachedModel(User::class, 123);
        $this->assertNotNull($cachedData);

        $result = $this->cacheService->invalidateModel($user);

        $this->assertTrue($result);
        
        // Verify data is no longer cached
        $cachedData = $this->cacheService->getCachedModel(User::class, 123);
        $this->assertNull($cachedData);
    }

    /** @test */
    public function it_can_warm_cache()
    {
        Log::shouldReceive('info')->times(2); // Start and completion logs

        $this->cacheService->warmCache();

        // Verify some cache keys were created
        $this->assertNotNull(Cache::tags(['users'])->get('users:active'));
        $this->assertNotNull(Cache::tags(['users'])->get('users:by_role'));
    }

    /** @test */
    public function it_handles_warm_cache_exceptions()
    {
        Log::shouldReceive('info')->once(); // Start log
        Log::shouldReceive('error')->once(); // Error log

        // Mock Cache to throw exception during warming
        Cache::shouldReceive('tags')
            ->andThrow(new \Exception('Cache warming error'));

        $this->cacheService->warmCache();
        
        // Test should complete without throwing exception
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_get_cache_stats()
    {
        // Mock Redis connection and info
        $mockRedis = Mockery::mock();
        $mockRedis->shouldReceive('info')
            ->once()
            ->andReturn([
                'used_memory_human' => '1.5M',
                'connected_clients' => '10',
                'total_commands_processed' => '1000',
                'keyspace_hits' => '800',
                'keyspace_misses' => '200'
            ]);

        Cache::shouldReceive('getRedis')
            ->once()
            ->andReturn($mockRedis);

        $stats = $this->cacheService->getStats();

        $this->assertArrayHasKey('memory_used', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);
        $this->assertEquals('1.5M', $stats['memory_used']);
        $this->assertEquals('80%', $stats['hit_rate']);
    }

    /** @test */
    public function it_handles_stats_exceptions()
    {
        Log::shouldReceive('error')->once();

        Cache::shouldReceive('getRedis')
            ->once()
            ->andThrow(new \Exception('Redis connection error'));

        $stats = $this->cacheService->getStats();

        $this->assertArrayHasKey('error', $stats);
        $this->assertEquals('Unable to retrieve cache statistics', $stats['error']);
    }

    /** @test */
    public function it_calculates_hit_rate_correctly()
    {
        $reflection = new \ReflectionClass($this->cacheService);
        $method = $reflection->getMethod('calculateHitRate');
        $method->setAccessible(true);

        // Test with hits and misses
        $info = ['keyspace_hits' => 80, 'keyspace_misses' => 20];
        $hitRate = $method->invoke($this->cacheService, $info);
        $this->assertEquals('80%', $hitRate);

        // Test with no data
        $info = [];
        $hitRate = $method->invoke($this->cacheService, $info);
        $this->assertEquals('0%', $hitRate);

        // Test with zero total
        $info = ['keyspace_hits' => 0, 'keyspace_misses' => 0];
        $hitRate = $method->invoke($this->cacheService, $info);
        $this->assertEquals('0%', $hitRate);
    }
}