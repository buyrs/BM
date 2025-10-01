<?php

namespace Tests\Unit\Services;

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    private CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = new CacheService();
    }

    public function test_put_and_get_cache()
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->cacheService->put($key, $value, 60);

        $this->assertEquals($value, $this->cacheService->get($key));
    }

    public function test_cache_expiration()
    {
        $key = 'expiring_key';
        $value = 'expiring_value';

        $this->cacheService->put($key, $value, 1); // 1 second TTL

        $this->assertEquals($value, $this->cacheService->get($key));

        // Wait for cache to expire
        sleep(2);

        $this->assertNull($this->cacheService->get($key));
    }

    public function test_remember_functionality()
    {
        $key = 'remember_key';
        $callbackValue = 'callback_value';

        $result = $this->cacheService->remember($key, 60, function () use ($callbackValue) {
            return $callbackValue;
        });

        $this->assertEquals($callbackValue, $result);

        // Second call should return cached value without executing callback
        $result2 = $this->cacheService->remember($key, 60, function () {
            return 'different_value'; // This should not be executed
        });

        $this->assertEquals($callbackValue, $result2);
    }

    public function test_forget_cache()
    {
        $key = 'delete_key';
        $value = 'delete_value';

        $this->cacheService->put($key, $value, 60);

        $this->assertEquals($value, $this->cacheService->get($key));

        $this->cacheService->forget($key);

        $this->assertNull($this->cacheService->get($key));
    }

    public function test_has_cache()
    {
        $key = 'existent_key';
        $value = 'existent_value';

        $this->cacheService->put($key, $value, 60);

        $this->assertTrue($this->cacheService->has($key));
        $this->assertFalse($this->cacheService->has('non_existent_key'));
    }

    public function test_increment_and_decrement()
    {
        $key = 'counter_key';

        $this->cacheService->put($key, 10, 60);

        $this->cacheService->increment($key);
        $this->assertEquals(11, $this->cacheService->get($key));

        $this->cacheService->decrement($key, 3);
        $this->assertEquals(8, $this->cacheService->get($key));
    }
}