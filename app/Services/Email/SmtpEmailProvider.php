<?php

namespace App\Services\Email;

use App\Contracts\EmailProviderInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Message;

class SmtpEmailProvider implements EmailProviderInterface
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('mail.mailers.smtp');
    }

    public function send(array $emailData, string $messageId): bool
    {
        try {
            Mail::raw($emailData['body'] ?? '', function (Message $message) use ($emailData, $messageId) {
                $message->to($emailData['to'])
                       ->subject($emailData['subject'])
                       ->getHeaders()
                       ->addTextHeader('X-Message-ID', $messageId);

                if (isset($emailData['from'])) {
                    $message->from($emailData['from']);
                }

                if (isset($emailData['cc'])) {
                    $message->cc($emailData['cc']);
                }

                if (isset($emailData['bcc'])) {
                    $message->bcc($emailData['bcc']);
                }

                if (isset($emailData['attachments'])) {
                    foreach ($emailData['attachments'] as $attachment) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? null,
                            'mime' => $attachment['mime'] ?? null
                        ]);
                    }
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error('SMTP email sending failed', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
                'to' => $emailData['to'] ?? 'unknown'
            ]);
            return false;
        }
    }

    public function validateConfiguration(): bool
    {
        $required = $this->getRequiredConfig();
        
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                return false;
            }
        }

        // Test connection
        try {
            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $this->config['host'],
                $this->config['port'],
                $this->config['encryption'] ?? null
            );

            if ($this->config['username']) {
                $transport->setUsername($this->config['username']);
                $transport->setPassword($this->config['password']);
            }

            $transport->start();
            $transport->stop();
            
            return true;
        } catch (\Exception $e) {
            Log::error('SMTP configuration validation failed', [
                'error' => $e->getMessage(),
                'host' => $this->config['host'],
                'port' => $this->config['port']
            ]);
            return false;
        }
    }

    public function getDeliveryStatus(string $messageId): string
    {
        // SMTP doesn't provide delivery status tracking
        // This would need to be implemented with webhooks or bounce handling
        return 'unknown';
    }

    public function getRequiredConfig(): array
    {
        return ['host', 'port'];
    }
}