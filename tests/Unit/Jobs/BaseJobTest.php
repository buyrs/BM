<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\BaseJob;
use Illuminate\Support\Facades\Log;
use Mockery;

class BaseJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_has_correct_default_configuration()
    {
        $job = new TestableBaseJob();

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(300, $job->timeout);
        $this->assertEquals(60, $job->backoff);
    }

    /** @test */
    public function it_calculates_retry_until_correctly()
    {
        $job = new TestableBaseJob();
        
        $retryUntil = $job->retryUntil();
        $expectedTime = now()->addMinutes(30);

        // Allow for small time differences in test execution
        $this->assertTrue(abs($retryUntil->timestamp - $expectedTime->timestamp) < 5);
    }

    /** @test */
    public function it_has_exponential_backoff_strategy()
    {
        $job = new TestableBaseJob();
        
        $backoffTimes = $job->backoff();

        $this->assertEquals([60, 300, 900], $backoffTimes);
        $this->assertCount(3, $backoffTimes);
    }

    /** @test */
    public function it_logs_job_failure()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Job failed permanently', Mockery::type('array'));

        $job = new TestableBaseJob();
        $exception = new \Exception('Test failure');

        $job->failed($exception);
        
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    /** @test */
    public function it_logs_job_start()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Job started', Mockery::type('array'));

        $job = new TestableBaseJob();
        $job->testLogJobStart();
        
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    /** @test */
    public function it_logs_job_completion()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Job completed successfully', Mockery::type('array'));

        $job = new TestableBaseJob();
        $job->testLogJobComplete();
        
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    /** @test */
    public function it_logs_job_errors()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Job error occurred', Mockery::type('array'));

        $job = new TestableBaseJob();
        $exception = new \Exception('Test error');
        
        $job->testLogJobError($exception);
        
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    /** @test */
    public function it_includes_context_in_logs()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Job started', Mockery::on(function ($context) {
                return isset($context['job']) && 
                       isset($context['attempt']) && 
                       isset($context['custom_data']) &&
                       $context['custom_data'] === 'test_value';
            }));

        $job = new TestableBaseJob();
        $job->testLogJobStart(['custom_data' => 'test_value']);
        
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    /** @test */
    public function it_calls_handle_job_failure_on_failure()
    {
        $job = Mockery::mock(TestableBaseJob::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $job->shouldReceive('handleJobFailure')
            ->once()
            ->with(Mockery::type(\Throwable::class));

        Log::shouldReceive('error')->once();

        $exception = new \Exception('Test failure');
        $job->failed($exception);
        
        $this->assertTrue(true); // Assert that no exception was thrown
    }
}

/**
 * Testable implementation of BaseJob for testing purposes
 */
class TestableBaseJob extends BaseJob
{
    public function handle(): void
    {
        // Test implementation
    }

    public function testLogJobStart(array $context = []): void
    {
        $this->logJobStart($context);
    }

    public function testLogJobComplete(array $context = []): void
    {
        $this->logJobComplete($context);
    }

    public function testLogJobError(\Throwable $exception, array $context = []): void
    {
        $this->logJobError($exception, $context);
    }

    protected function handleJobFailure(\Throwable $exception): void
    {
        // Test implementation - can be overridden in tests
    }
}