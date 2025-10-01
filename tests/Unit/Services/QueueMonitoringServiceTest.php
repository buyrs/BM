<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\QueueMonitoringService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;

class QueueMonitoringServiceTest extends TestCase
{
    protected QueueMonitoringService $queueService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queueService = new QueueMonitoringService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_queue_statistics()
    {
        // Mock Redis connection
        $mockRedis = Mockery::mock();
        $mockRedis->shouldReceive('llen')->andReturn(5);
        $mockRedis->shouldReceive('zcard')->andReturn(2);

        Redis::shouldReceive('connection')
            ->with('queues')
            ->andReturn($mockRedis);

        // Mock DB for failed jobs count
        DB::shouldReceive('table')
            ->with('failed_jobs')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('count')
            ->andReturn(1);

        $stats = $this->queueService->getQueueStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('default', $stats);
        $this->assertEquals(5, $stats['default']['waiting']);
        $this->assertEquals(2, $stats['default']['delayed']);
        $this->assertEquals(1, $stats['default']['failed']);
    }

    /** @test */
    public function it_handles_queue_info_exceptions()
    {
        Log::shouldReceive('error')->once();

        Redis::shouldReceive('connection')
            ->with('queues')
            ->andThrow(new \Exception('Redis connection failed'));

        $info = $this->queueService->getQueueInfo('default');

        $this->assertArrayHasKey('error', $info);
        $this->assertEquals('Redis connection failed', $info['error']);
        $this->assertEquals('unknown', $info['waiting']);
    }

    /** @test */
    public function it_can_get_failed_jobs_count()
    {
        // Create some failed jobs in the database
        DB::table('failed_jobs')->insert([
            [
                'uuid' => 'test-uuid-1',
                'connection' => 'redis',
                'queue' => 'default',
                'payload' => json_encode(['test' => 'data']),
                'exception' => 'Test exception',
                'failed_at' => now()
            ],
            [
                'uuid' => 'test-uuid-2',
                'connection' => 'redis',
                'queue' => 'emails',
                'payload' => json_encode(['test' => 'data']),
                'exception' => 'Test exception',
                'failed_at' => now()
            ]
        ]);

        $reflection = new \ReflectionClass($this->queueService);
        $method = $reflection->getMethod('getFailedJobsCount');
        $method->setAccessible(true);

        $count = $method->invoke($this->queueService, 'default');
        $this->assertEquals(1, $count);

        $count = $method->invoke($this->queueService, 'emails');
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function it_handles_failed_jobs_count_exceptions()
    {
        Log::shouldReceive('error')->once();

        DB::shouldReceive('table')
            ->with('failed_jobs')
            ->andThrow(new \Exception('Database error'));

        $reflection = new \ReflectionClass($this->queueService);
        $method = $reflection->getMethod('getFailedJobsCount');
        $method->setAccessible(true);

        $count = $method->invoke($this->queueService, 'default');
        $this->assertEquals(0, $count);
    }

    /** @test */
    public function it_can_clear_failed_jobs()
    {
        // Create failed jobs
        DB::table('failed_jobs')->insert([
            [
                'uuid' => 'test-uuid-1',
                'connection' => 'redis',
                'queue' => 'default',
                'payload' => json_encode(['test' => 'data']),
                'exception' => 'Test exception',
                'failed_at' => now()
            ],
            [
                'uuid' => 'test-uuid-2',
                'connection' => 'redis',
                'queue' => 'emails',
                'payload' => json_encode(['test' => 'data']),
                'exception' => 'Test exception',
                'failed_at' => now()
            ]
        ]);

        Log::shouldReceive('info')->once();

        $count = $this->queueService->clearFailedJobs('default');

        $this->assertEquals(1, $count);
        $this->assertEquals(1, DB::table('failed_jobs')->count()); // Only emails queue job remains
    }

    /** @test */
    public function it_can_clear_all_failed_jobs()
    {
        // Create failed jobs
        DB::table('failed_jobs')->insert([
            [
                'uuid' => 'test-uuid-1',
                'connection' => 'redis',
                'queue' => 'default',
                'payload' => json_encode(['test' => 'data']),
                'exception' => 'Test exception',
                'failed_at' => now()
            ],
            [
                'uuid' => 'test-uuid-2',
                'connection' => 'redis',
                'queue' => 'emails',
                'payload' => json_encode(['test' => 'data']),
                'exception' => 'Test exception',
                'failed_at' => now()
            ]
        ]);

        Log::shouldReceive('info')->once();

        $count = $this->queueService->clearFailedJobs();

        $this->assertEquals(2, $count);
        $this->assertEquals(0, DB::table('failed_jobs')->count());
    }

    /** @test */
    public function it_handles_clear_failed_jobs_exceptions()
    {
        Log::shouldReceive('error')->once();

        DB::shouldReceive('table')
            ->with('failed_jobs')
            ->andThrow(new \Exception('Database error'));

        $count = $this->queueService->clearFailedJobs('default');

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function it_can_retry_failed_jobs()
    {
        // Create a serialized job payload
        $jobPayload = [
            'uuid' => 'test-uuid',
            'displayName' => 'App\\Jobs\\TestJob',
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'maxTries' => null,
            'maxExceptions' => null,
            'failOnTimeout' => false,
            'backoff' => null,
            'timeout' => null,
            'retryUntil' => null,
            'data' => [
                'commandName' => 'App\\Jobs\\TestJob',
                'command' => serialize(new \App\Jobs\SendEmailJob(['to' => 'test@example.com']))
            ]
        ];

        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-1',
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => json_encode($jobPayload),
            'exception' => 'Test exception',
            'failed_at' => now()
        ]);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->zeroOrMoreTimes(); // Allow error logs for job retry failures

        $count = $this->queueService->retryFailedJobs('default');

        $this->assertEquals(1, $count);
        $this->assertEquals(0, DB::table('failed_jobs')->where('queue', 'default')->count());
    }

    /** @test */
    public function it_handles_retry_failed_jobs_exceptions()
    {
        Log::shouldReceive('error')->times(2); // One for main exception, one for job retry failure

        // Create invalid job payload
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-1',
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => 'invalid json',
            'exception' => 'Test exception',
            'failed_at' => now()
        ]);

        $count = $this->queueService->retryFailedJobs('default');

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function it_can_assess_queue_health()
    {
        // Mock Redis for healthy queues
        $mockRedis = Mockery::mock();
        $mockRedis->shouldReceive('llen')->andReturn(10); // Low waiting jobs
        $mockRedis->shouldReceive('zcard')->andReturn(5); // Low reserved jobs

        Redis::shouldReceive('connection')
            ->with('queues')
            ->andReturn($mockRedis);

        // Mock DB for low failed jobs
        DB::shouldReceive('table')
            ->with('failed_jobs')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('count')
            ->andReturn(2);

        $health = $this->queueService->getQueueHealth();

        $this->assertEquals('healthy', $health['status']);
        $this->assertEmpty($health['issues']);
        $this->assertEmpty($health['recommendations']);
    }

    /** @test */
    public function it_detects_queue_health_warnings()
    {
        // Mock Redis for warning conditions
        $mockRedis = Mockery::mock();
        $mockRedis->shouldReceive('llen')->andReturn(1500); // High waiting jobs
        $mockRedis->shouldReceive('zcard')->andReturn(5); // Normal reserved jobs

        Redis::shouldReceive('connection')
            ->with('queues')
            ->andReturn($mockRedis);

        // Mock DB for high failed jobs
        DB::shouldReceive('table')
            ->with('failed_jobs')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('count')
            ->andReturn(15);

        $health = $this->queueService->getQueueHealth();

        $this->assertEquals('warning', $health['status']);
        $this->assertNotEmpty($health['issues']);
        $this->assertNotEmpty($health['recommendations']);
    }

    /** @test */
    public function it_detects_critical_queue_health_issues()
    {
        // Mock Redis for critical conditions
        $mockRedis = Mockery::mock();
        $mockRedis->shouldReceive('llen')->andReturn(500); // Normal waiting jobs
        $mockRedis->shouldReceive('zcard')->andReturn(150); // High reserved jobs (stuck)

        Redis::shouldReceive('connection')
            ->with('queues')
            ->andReturn($mockRedis);

        // Mock DB for normal failed jobs
        DB::shouldReceive('table')
            ->with('failed_jobs')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('count')
            ->andReturn(5);

        $health = $this->queueService->getQueueHealth();

        $this->assertEquals('critical', $health['status']);
        $this->assertStringContains('reserved jobs (possibly stuck)', $health['issues'][0]);
        $this->assertStringContains('Restart queue workers', $health['recommendations'][0]);
    }

    /** @test */
    public function it_can_purge_queue()
    {
        $mockRedis = Mockery::mock();
        $mockRedis->shouldReceive('del')
            ->with('queues:default')
            ->once();
        $mockRedis->shouldReceive('del')
            ->with('queues:default:delayed')
            ->once();
        $mockRedis->shouldReceive('del')
            ->with('queues:default:reserved')
            ->once();

        Redis::shouldReceive('connection')
            ->with('queues')
            ->andReturn($mockRedis);

        Log::shouldReceive('info')->once();

        $result = $this->queueService->purgeQueue('default');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_purge_queue_exceptions()
    {
        Log::shouldReceive('error')->once();

        Redis::shouldReceive('connection')
            ->with('queues')
            ->andThrow(new \Exception('Redis error'));

        $result = $this->queueService->purgeQueue('default');

        $this->assertFalse($result);
    }
}