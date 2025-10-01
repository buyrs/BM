<?php

namespace App\Services\Email;

use App\Contracts\EmailProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailgunEmailProvider implements EmailProviderInterface
{
    protected array $config;

    public function __construct()
    {
        $this->config = [
            'domain' => config('services.mailgun.domain'),
            'secret' => config('services.mailgun.secret'),
            'endpoint' => config('services.mailgun.endpoint', 'api.mailgun.net')
        ];
    }

    public function send(array $emailData, string $messageId): bool
    {
        try {
            $response = Http::withBasicAuth('api', $this->config['secret'])
                ->asForm()
                ->post("https://{$this->config['endpoint']}/v3/{$this->config['domain']}/messages", [
                    'from' => $emailData['from'] ?? config('mail.from.address'),
                    'to' => $emailData['to'],
                    'subject' => $emailData['subject'],
                    'text' => $emailData['body'] ?? '',
                    'html' => $emailData['html'] ?? null,
                    'o:tag' => 'laravel-app',
                    'o:tracking' => 'yes',
                    'h:X-Message-ID' => $messageId
                ]);

            if ($response->successful()) {
                Log::info('Mailgun email sent successfully', [
                    'message_id' => $messageId,
                    'mailgun_id' => $response->json('id')
                ]);
                return true;
            }

            Log::error('Mailgun email sending failed', [
                'message_id' => $messageId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Mailgun email provider error', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
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

        // Test API connection
        try {
            $response = Http::withBasicAuth('api', $this->config['secret'])
                ->get("https://{$this->config['endpoint']}/v3/domains/{$this->config['domain']}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Mailgun configuration validation failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getDeliveryStatus(string $messageId): string
    {
        try {
            // Query Mailgun events API for delivery status
            $response = Http::withBasicAuth('api', $this->config['secret'])
                ->get("https://{$this->config['endpoint']}/v3/{$this->config['domain']}/events", [
                    'message-id' => $messageId,
                    'event' => 'delivered OR failed OR rejected'
                ]);

            if ($response->successful()) {
                $events = $response->json('items', []);
                if (!empty($events)) {
                    return $events[0]['event'] ?? 'unknown';
                }
            }

            return 'pending';
        } catch (\Exception $e) {
            Log::error('Mailgun delivery status check failed', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);
            return 'unknown';
        }
    }

    public function getRequiredConfig(): array
    {
        return ['domain', 'secret'];
    }
}