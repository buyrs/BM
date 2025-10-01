<?php

namespace App\Services\Email;

use App\Contracts\EmailProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendGridEmailProvider implements EmailProviderInterface
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.sendgrid.api_key');
    }

    public function send(array $emailData, string $messageId): bool
    {
        try {
            $payload = [
                'personalizations' => [
                    [
                        'to' => [
                            ['email' => $emailData['to']]
                        ],
                        'subject' => $emailData['subject']
                    ]
                ],
                'from' => [
                    'email' => $emailData['from'] ?? config('mail.from.address'),
                    'name' => $emailData['from_name'] ?? config('mail.from.name')
                ],
                'content' => [
                    [
                        'type' => 'text/plain',
                        'value' => $emailData['body'] ?? ''
                    ]
                ],
                'custom_args' => [
                    'message_id' => $messageId,
                    'app' => 'laravel-property-management'
                ]
            ];

            if (isset($emailData['html'])) {
                $payload['content'][] = [
                    'type' => 'text/html',
                    'value' => $emailData['html']
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.sendgrid.com/v3/mail/send', $payload);

            if ($response->successful()) {
                Log::info('SendGrid email sent successfully', [
                    'message_id' => $messageId,
                    'sendgrid_id' => $response->header('X-Message-Id')
                ]);
                return true;
            }

            Log::error('SendGrid email sending failed', [
                'message_id' => $messageId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('SendGrid email provider error', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function validateConfiguration(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        // Test API key validity
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey
            ])->get('https://api.sendgrid.com/v3/user/profile');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SendGrid configuration validation failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getDeliveryStatus(string $messageId): string
    {
        try {
            // SendGrid doesn't provide a direct API to query by custom message ID
            // This would typically be handled via webhooks
            // For now, return unknown status
            return 'unknown';
        } catch (\Exception $e) {
            Log::error('SendGrid delivery status check failed', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);
            return 'unknown';
        }
    }

    public function getRequiredConfig(): array
    {
        return ['api_key'];
    }
}