<?php

namespace App\Jobs;

use App\Services\ReportGenerationService;
use App\Services\EmailService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateScheduledReportJob extends BaseJob
{
    protected string $reportType;
    protected array $config;
    protected array $recipients;

    /**
     * Create a new job instance.
     */
    public function __construct(string $reportType, array $config = [], array $recipients = [])
    {
        parent::__construct();
        $this->reportType = $reportType;
        $this->config = $config;
        $this->recipients = $recipients;
    }

    /**
     * Execute the job.
     */
    public function handle(ReportGenerationService $reportService, EmailService $emailService): void
    {
        try {
            Log::info("Starting scheduled report generation", [
                'report_type' => $this->reportType,
                'config' => $this->config,
                'recipients_count' => count($this->recipients),
            ]);

            // Determine date range based on config
            $dateRange = $this->getDateRange();
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            // Generate the report
            $result = $this->generateReport($reportService, $startDate, $endDate);

            if (!$result['success']) {
                throw new \Exception("Report generation failed: " . ($result['message'] ?? 'Unknown error'));
            }

            Log::info("Report generated successfully", [
                'report_type' => $this->reportType,
                'filename' => $result['filename'],
                'size' => $result['size'],
            ]);

            // Send report to recipients if configured
            if (!empty($this->recipients)) {
                $this->sendReportToRecipients($emailService, $result);
            }

            Log::info("Scheduled report generation completed successfully", [
                'report_type' => $this->reportType,
                'filename' => $result['filename'],
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to generate scheduled report", [
                'report_type' => $this->reportType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate the report based on type
     */
    private function generateReport(ReportGenerationService $reportService, Carbon $startDate, Carbon $endDate): array
    {
        $format = $this->config['format'] ?? 'pdf';

        switch ($this->reportType) {
            case 'analytics':
                $sections = $this->config['sections'] ?? [];
                return $reportService->generateAnalyticsReport($format, $startDate, $endDate, $sections);

            case 'missions':
                $filters = $this->config['filters'] ?? [];
                return $reportService->generateMissionReport($format, $startDate, $endDate, $filters);

            case 'user_performance':
                $role = $this->config['role'] ?? null;
                return $reportService->generateUserPerformanceReport($format, $startDate, $endDate, $role);

            case 'maintenance':
                $filters = $this->config['filters'] ?? [];
                return $reportService->generateMaintenanceReport($format, $startDate, $endDate, $filters);

            default:
                throw new \InvalidArgumentException("Unsupported report type: {$this->reportType}");
        }
    }

    /**
     * Send report to configured recipients
     */
    private function sendReportToRecipients(EmailService $emailService, array $reportResult): void
    {
        try {
            $subject = $this->getEmailSubject();
            $body = $this->getEmailBody($reportResult);

            foreach ($this->recipients as $recipient) {
                $emailService->send(
                    to: $recipient['email'],
                    subject: $subject,
                    body: $body,
                    attachments: [
                        [
                            'path' => storage_path('app/' . $reportResult['path']),
                            'name' => $reportResult['filename'],
                        ]
                    ]
                );

                Log::info("Report sent to recipient", [
                    'recipient' => $recipient['email'],
                    'report_type' => $this->reportType,
                    'filename' => $reportResult['filename'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to send report to recipients", [
                'error' => $e->getMessage(),
                'report_type' => $this->reportType,
                'recipients_count' => count($this->recipients),
            ]);

            // Don't throw here as the report was generated successfully
            // Just log the email sending failure
        }
    }

    /**
     * Get date range based on configuration
     */
    private function getDateRange(): array
    {
        $period = $this->config['period'] ?? 'last_30_days';
        $now = Carbon::now();

        switch ($period) {
            case 'yesterday':
                return [
                    'start' => $now->copy()->subDay()->startOfDay(),
                    'end' => $now->copy()->subDay()->endOfDay(),
                ];

            case 'last_7_days':
                return [
                    'start' => $now->copy()->subDays(7)->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                ];

            case 'last_30_days':
                return [
                    'start' => $now->copy()->subDays(30)->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                ];

            case 'last_month':
                return [
                    'start' => $now->copy()->subMonth()->startOfMonth(),
                    'end' => $now->copy()->subMonth()->endOfMonth(),
                ];

            case 'this_month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                ];

            case 'last_quarter':
                return [
                    'start' => $now->copy()->subQuarter()->startOfQuarter(),
                    'end' => $now->copy()->subQuarter()->endOfQuarter(),
                ];

            case 'custom':
                return [
                    'start' => Carbon::parse($this->config['start_date']),
                    'end' => Carbon::parse($this->config['end_date']),
                ];

            default:
                return [
                    'start' => $now->copy()->subDays(30)->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                ];
        }
    }

    /**
     * Get email subject for the report
     */
    private function getEmailSubject(): string
    {
        $reportName = ucwords(str_replace('_', ' ', $this->reportType));
        $period = $this->config['period'] ?? 'last_30_days';
        $periodName = ucwords(str_replace('_', ' ', $period));
        
        return "Scheduled {$reportName} Report - {$periodName}";
    }

    /**
     * Get email body for the report
     */
    private function getEmailBody(array $reportResult): string
    {
        $reportName = ucwords(str_replace('_', ' ', $this->reportType));
        $dateRange = $this->getDateRange();
        
        return "
            <h2>Scheduled {$reportName} Report</h2>
            
            <p>Please find attached your scheduled {$reportName} report.</p>
            
            <p><strong>Report Details:</strong></p>
            <ul>
                <li>Report Type: {$reportName}</li>
                <li>Period: {$dateRange['start']->format('M j, Y')} - {$dateRange['end']->format('M j, Y')}</li>
                <li>Generated: " . Carbon::now()->format('M j, Y \a\t g:i A') . "</li>
                <li>File Size: " . $this->formatBytes($reportResult['size']) . "</li>
            </ul>
            
            <p>This report was automatically generated by the Property Management System.</p>
            
            <p>If you have any questions about this report, please contact your system administrator.</p>
        ";
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['reports', 'scheduled', $this->reportType];
    }
}