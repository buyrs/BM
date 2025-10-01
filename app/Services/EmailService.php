<?php

namespace App\Services;

use App\Contracts\EmailProviderInterface;
use App\Services\Email\SmtpEmailProvider;
use App\Services\Email\MailgunEmailProvider;
use App\Services\Email\SendGridEmailProvider;
use App\Services\Email\SesEmailProvider;
use App\Models\EmailDeliveryStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendEmailJob;

class EmailService extends BaseService
{
    protected EmailProviderInterface $provider;

    public function __construct()
    {
        parent::__construct();
        $this->provider = $this->createProvider();
    }

    protected function getDefaultConfig(): array
    {
        return config('mail');
    }

    /**
     * Send an email message
     */
    public function send(array $emailData): bool
    {
        try {
            // Queue email for background processing
            if (config('mail.queue_emails', true)) {
                Queue::push(new SendEmailJob($emailData));
                return true;
            }

            // Send immediately
            return $this->sendNow($emailData);
        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'error' => $e->getMessage(),
                'email_data' => $emailData
            ]);
            return false;
        }
    }

    /**
     * Send email immediately without queuing
     */
    public function sendNow(array $emailData): bool
    {
        $messageId = $this->generateMessageId();
        
        try {
            // Create delivery status record
            $deliveryStatus = EmailDeliveryStatus::create([
                'message_id' => $messageId,
                'to_email' => $emailData['to'],
                'subject' => $emailData['subject'],
                'status' => 'sending',
                'provider' => $this->getProviderName(),
                'attempts' => 1
            ]);

            $result = $this->provider->send($emailData, $messageId);

            // Update delivery status
            $deliveryStatus->update([
                'status' => $result ? 'sent' : 'failed',
                'sent_at' => $result ? now() : null,
                'error_message' => $result ? null : 'Provider send failed'
            ]);

            return $result;
        } catch (\Exception $e) {
            // Update delivery status with error
            if (isset($deliveryStatus)) {
                $deliveryStatus->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }

            Log::error('Email provider error', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
                'provider' => $this->getProviderName()
            ]);

            return false;
        }
    }

    /**
     * Get delivery status for a message
     */
    public function getDeliveryStatus(string $messageId): ?EmailDeliveryStatus
    {
        return EmailDeliveryStatus::where('message_id', $messageId)->first();
    }

    /**
     * Validate current email configuration
     */
    public function validateConfiguration(): bool
    {
        try {
            return $this->provider->validateConfiguration();
        } catch (\Exception $e) {
            Log::error('Email configuration validation failed', [
                'provider' => $this->getProviderName(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create appropriate email provider based on configuration
     */
    protected function createProvider(): EmailProviderInterface
    {
        $mailer = config('mail.default');
        
        // Use production mailer if in production environment
        if (app()->environment('production')) {
            $mailer = config('mail.production_mailer', $mailer);
        }

        return match ($mailer) {
            'mailgun' => new MailgunEmailProvider(),
            'sendgrid' => new SendGridEmailProvider(),
            'ses' => new SesEmailProvider(),
            'smtp' => new SmtpEmailProvider(),
            default => new SmtpEmailProvider()
        };
    }

    /**
     * Get current provider name
     */
    protected function getProviderName(): string
    {
        return class_basename($this->provider);
    }

    /**
     * Generate unique message ID
     */
    protected function generateMessageId(): string
    {
        return uniqid('email_', true) . '@' . config('app.url');
    }

    /**
     * Retry failed email
     */
    public function retryFailedEmail(string $messageId): bool
    {
        $deliveryStatus = $this->getDeliveryStatus($messageId);
        
        if (!$deliveryStatus || $deliveryStatus->status !== 'failed') {
            return false;
        }

        if ($deliveryStatus->attempts >= config('mail.max_retries', 3)) {
            Log::warning('Email max retries exceeded', ['message_id' => $messageId]);
            return false;
        }

        // Increment attempts
        $deliveryStatus->increment('attempts');
        $deliveryStatus->update(['status' => 'retrying']);

        // Reconstruct email data and retry
        $emailData = [
            'to' => $deliveryStatus->to_email,
            'subject' => $deliveryStatus->subject,
            // Note: We'd need to store more email data to fully reconstruct
            // This is a simplified version
        ];

        return $this->sendNow($emailData);
    }
}