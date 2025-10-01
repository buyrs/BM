<?php

namespace App\Services\Email;

use App\Contracts\EmailProviderInterface;
use Illuminate\Support\Facades\Log;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

class SesEmailProvider implements EmailProviderInterface
{
    protected SesClient $sesClient;
    protected array $config;

    public function __construct()
    {
        $this->config = [
            'key' => config('services.ses.key'),
            'secret' => config('services.ses.secret'),
            'region' => config('services.ses.region', 'us-east-1')
        ];

        $this->sesClient = new SesClient([
            'version' => 'latest',
            'region' => $this->config['region'],
            'credentials' => [
                'key' => $this->config['key'],
                'secret' => $this->config['secret']
            ]
        ]);
    }

    public function send(array $emailData, string $messageId): bool
    {
        try {
            $result = $this->sesClient->sendEmail([
                'Source' => $emailData['from'] ?? config('mail.from.address'),
                'Destination' => [
                    'ToAddresses' => [$emailData['to']]
                ],
                'Message' => [
                    'Subject' => [
                        'Data' => $emailData['subject'],
                        'Charset' => 'UTF-8'
                    ],
                    'Body' => [
                        'Text' => [
                            'Data' => $emailData['body'] ?? '',
                            'Charset' => 'UTF-8'
                        ]
                    ]
                ],
                'Tags' => [
                    [
                        'Name' => 'MessageId',
                        'Value' => $messageId
                    ],
                    [
                        'Name' => 'Application',
                        'Value' => 'laravel-property-management'
                    ]
                ]
            ]);

            Log::info('SES email sent successfully', [
                'message_id' => $messageId,
                'ses_message_id' => $result['MessageId']
            ]);

            return true;

        } catch (AwsException $e) {
            Log::error('SES email sending failed', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
                'aws_error_code' => $e->getAwsErrorCode()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('SES email provider error', [
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

        // Test SES connection
        try {
            $this->sesClient->getSendQuota();
            return true;
        } catch (AwsException $e) {
            Log::error('SES configuration validation failed', [
                'error' => $e->getMessage(),
                'aws_error_code' => $e->getAwsErrorCode()
            ]);
            return false;
        }
    }

    public function getDeliveryStatus(string $messageId): string
    {
        // SES doesn't provide a direct API to query delivery status by custom message ID
        // This would typically be handled via SNS notifications and webhooks
        return 'unknown';
    }

    public function getRequiredConfig(): array
    {
        return ['key', 'secret', 'region'];
    }
}