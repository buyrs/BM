<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Services\EmailService;
use Illuminate\Support\Facades\Log;
use Mockery;

class SendEmailJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_has_correct_email_job_configuration()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        $job = new SendEmailJob($emailData);

        $this->assertEquals('emails', $job->queue);
        $this->assertEquals(5, $job->tries);
        $this->assertEquals(120, $job->timeout);
    }

    /** @test */
    public function it_has_email_specific_backoff_strategy()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        $job = new SendEmailJob($emailData);
        $backoffTimes = $job->backoff();

        $this->assertEquals([30, 120, 300, 600, 1800], $backoffTimes);
        $this->assertCount(5, $backoffTimes);
    }

    /** @test */
    public function it_successfully_sends_email()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        $mockEmailService = Mockery::mock(EmailService::class);
        $mockEmailService->shouldReceive('sendNow')
            ->once()
            ->with($emailData)
            ->andReturn(true);

        Log::shouldReceive('info')
            ->once()
            ->with('Email sent successfully via queue', Mockery::type('array'));

        $job = new SendEmailJob($emailData);
        $job->handle($mockEmailService);

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_email_sending_failure()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        $mockEmailService = Mockery::mock(EmailService::class);
        $mockEmailService->shouldReceive('sendNow')
            ->once()
            ->with($emailData)
            ->andReturn(false);

        Log::shouldReceive('error')
            ->once()
            ->with('Email job failed', Mockery::type('array'));

        $job = new SendEmailJob($emailData);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email sending failed');

        $job->handle($mockEmailService);
    }

    /** @test */
    public function it_handles_email_service_exceptions()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        $mockEmailService = Mockery::mock(EmailService::class);
        $mockEmailService->shouldReceive('sendNow')
            ->once()
            ->with($emailData)
            ->andThrow(new \Exception('Service unavailable'));

        Log::shouldReceive('error')
            ->once()
            ->with('Email job failed', Mockery::on(function ($context) {
                return isset($context['error']) && 
                       $context['error'] === 'Service unavailable' &&
                       isset($context['email_data']) &&
                       isset($context['attempt']);
            }));

        $job = new SendEmailJob($emailData);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service unavailable');

        $job->handle($mockEmailService);
    }

    /** @test */
    public function it_logs_permanent_failure_after_max_retries()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        // Create a testable job class that doesn't call onQueue
        $job = new class($emailData) extends SendEmailJob {
            public function __construct(array $emailData) {
                $this->emailData = $emailData;
                // Skip parent constructor to avoid onQueue call
            }
            
            public function attempts(): int {
                return 5; // Simulate max attempts
            }
        };

        $mockEmailService = Mockery::mock(EmailService::class);
        $mockEmailService->shouldReceive('sendNow')
            ->once()
            ->andReturn(false);

        Log::shouldReceive('error')
            ->once()
            ->with('Email job failed', Mockery::type('array'));

        Log::shouldReceive('error')
            ->once()
            ->with('Email job permanently failed after max retries', Mockery::type('array'));

        $this->expectException(\Exception::class);

        $job->handle($mockEmailService);
    }

    /** @test */
    public function it_handles_job_failure_with_logging()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        $job = new SendEmailJob($emailData);
        $exception = new \Exception('Permanent failure');

        Log::shouldReceive('error')
            ->once()
            ->with('Job failed permanently', Mockery::type('array'));

        Log::shouldReceive('error')
            ->once()
            ->with('Job error occurred', Mockery::type('array'));

        Log::shouldReceive('error')
            ->once()
            ->with('Email job permanently failed', Mockery::on(function ($context) use ($emailData) {
                return isset($context['error']) && 
                       $context['error'] === 'Permanent failure' &&
                       isset($context['email_data']) &&
                       $context['email_data'] === $emailData;
            }));

        $job->failed($exception);
        
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    /** @test */
    public function it_includes_correct_email_data_in_logs()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        $mockEmailService = Mockery::mock(EmailService::class);
        $mockEmailService->shouldReceive('sendNow')
            ->once()
            ->andReturn(true);

        Log::shouldReceive('info')
            ->once()
            ->with('Email sent successfully via queue', Mockery::on(function ($context) {
                return $context['to'] === 'test@example.com' &&
                       $context['subject'] === 'Test Subject';
            }));

        $job = new SendEmailJob($emailData);
        $job->handle($mockEmailService);
        
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    /** @test */
    public function it_preserves_email_data_across_retries()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        $job = new SendEmailJob($emailData);

        // Access private property using reflection
        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('emailData');
        $property->setAccessible(true);
        $storedEmailData = $property->getValue($job);

        $this->assertEquals($emailData, $storedEmailData);
    }
}