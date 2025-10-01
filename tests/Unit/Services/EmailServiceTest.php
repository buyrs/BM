<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\EmailService;
use App\Contracts\EmailProviderInterface;
use App\Models\EmailDeliveryStatus;
use App\Jobs\SendEmailJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Mockery;

class EmailServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EmailService $emailService;
    protected $mockProvider;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the email provider
        $this->mockProvider = Mockery::mock(EmailProviderInterface::class);
        
        // Create email service instance
        $this->emailService = new EmailService();
        
        // Use reflection to inject mock provider
        $reflection = new \ReflectionClass($this->emailService);
        $providerProperty = $reflection->getProperty('provider');
        $providerProperty->setAccessible(true);
        $providerProperty->setValue($this->emailService, $this->mockProvider);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_send_email_via_queue()
    {
        Queue::fake();
        Config::set('mail.queue_emails', true);

        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        $result = $this->emailService->send($emailData);

        $this->assertTrue($result);
        Queue::assertPushed(SendEmailJob::class);
    }

    /** @test */
    public function it_can_send_email_immediately()
    {
        Config::set('mail.queue_emails', false);
        
        $this->mockProvider
            ->shouldReceive('send')
            ->once()
            ->andReturn(true);

        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        $result = $this->emailService->send($emailData);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_creates_delivery_status_record_when_sending()
    {
        $this->mockProvider
            ->shouldReceive('send')
            ->once()
            ->andReturn(true);

        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        $this->emailService->sendNow($emailData);

        $this->assertDatabaseHas('email_delivery_statuses', [
            'to_email' => 'test@example.com',
            'subject' => 'Test Subject',
            'status' => 'sent'
        ]);
    }

    /** @test */
    public function it_handles_email_sending_failure()
    {
        $this->mockProvider
            ->shouldReceive('send')
            ->once()
            ->andReturn(false);

        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        $result = $this->emailService->sendNow($emailData);

        $this->assertFalse($result);
        $this->assertDatabaseHas('email_delivery_statuses', [
            'to_email' => 'test@example.com',
            'status' => 'failed'
        ]);
    }

    /** @test */
    public function it_handles_provider_exceptions()
    {
        Log::shouldReceive('error')->once();

        $this->mockProvider
            ->shouldReceive('send')
            ->once()
            ->andThrow(new \Exception('Provider error'));

        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        $result = $this->emailService->sendNow($emailData);

        $this->assertFalse($result);
        $this->assertDatabaseHas('email_delivery_statuses', [
            'to_email' => 'test@example.com',
            'status' => 'failed',
            'error_message' => 'Provider error'
        ]);
    }

    /** @test */
    public function it_can_get_delivery_status()
    {
        $deliveryStatus = EmailDeliveryStatus::create([
            'message_id' => 'test_message_123',
            'to_email' => 'test@example.com',
            'subject' => 'Test Subject',
            'status' => 'sent',
            'provider' => 'SmtpEmailProvider',
            'attempts' => 1
        ]);

        $result = $this->emailService->getDeliveryStatus('test_message_123');

        $this->assertInstanceOf(EmailDeliveryStatus::class, $result);
        $this->assertEquals('test@example.com', $result->to_email);
    }

    /** @test */
    public function it_validates_email_configuration()
    {
        $this->mockProvider
            ->shouldReceive('validateConfiguration')
            ->once()
            ->andReturn(true);

        $result = $this->emailService->validateConfiguration();

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_configuration_validation_failure()
    {
        Log::shouldReceive('error')->once();

        $this->mockProvider
            ->shouldReceive('validateConfiguration')
            ->once()
            ->andThrow(new \Exception('Config error'));

        $result = $this->emailService->validateConfiguration();

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_retry_failed_email()
    {
        // Create a failed email delivery status
        $deliveryStatus = EmailDeliveryStatus::create([
            'message_id' => 'failed_message_123',
            'to_email' => 'test@example.com',
            'subject' => 'Test Subject',
            'status' => 'failed',
            'provider' => 'SmtpEmailProvider',
            'attempts' => 1
        ]);

        $this->mockProvider
            ->shouldReceive('send')
            ->once()
            ->andReturn(true);

        $result = $this->emailService->retryFailedEmail('failed_message_123');

        $this->assertTrue($result);
        
        // Check that attempts were incremented
        $deliveryStatus->refresh();
        $this->assertEquals(2, $deliveryStatus->attempts);
        $this->assertEquals('retrying', $deliveryStatus->status);
    }

    /** @test */
    public function it_prevents_retry_after_max_attempts()
    {
        Log::shouldReceive('warning')->once();
        Config::set('mail.max_retries', 3);

        // Create a failed email with max attempts
        $deliveryStatus = EmailDeliveryStatus::create([
            'message_id' => 'max_attempts_123',
            'to_email' => 'test@example.com',
            'subject' => 'Test Subject',
            'status' => 'failed',
            'provider' => 'SmtpEmailProvider',
            'attempts' => 3
        ]);

        $result = $this->emailService->retryFailedEmail('max_attempts_123');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_generates_unique_message_ids()
    {
        Config::set('app.url', 'https://example.com');

        $reflection = new \ReflectionClass($this->emailService);
        $method = $reflection->getMethod('generateMessageId');
        $method->setAccessible(true);

        $messageId1 = $method->invoke($this->emailService);
        $messageId2 = $method->invoke($this->emailService);

        $this->assertNotEquals($messageId1, $messageId2);
        $this->assertStringContainsString('@https://example.com', $messageId1);
        $this->assertStringContainsString('email_', $messageId1);
    }

    /** @test */
    public function it_handles_send_exceptions_gracefully()
    {
        Log::shouldReceive('error')->once();
        Config::set('mail.queue_emails', false);

        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];

        // Mock provider to throw exception
        $this->mockProvider
            ->shouldReceive('send')
            ->once()
            ->andThrow(new \Exception('Network error'));

        $result = $this->emailService->send($emailData);

        $this->assertFalse($result);
    }
}