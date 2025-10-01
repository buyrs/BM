<?php

namespace App\Jobs;

use App\Services\EmailService;
use Illuminate\Support\Facades\Log;

class SendEmailJob extends EmailJob
{
    protected array $emailData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $emailData)
    {
        parent::__construct();
        $this->emailData = $emailData;
    }

    /**
     * Execute the job.
     */
    public function handle(EmailService $emailService): void
    {
        try {
            $success = $emailService->sendNow($this->emailData);
            
            if (!$success) {
                throw new \Exception('Email sending failed');
            }

            Log::info('Email sent successfully via queue', [
                'to' => $this->emailData['to'],
                'subject' => $this->emailData['subject']
            ]);

        } catch (\Exception $e) {
            Log::error('Email job failed', [
                'error' => $e->getMessage(),
                'email_data' => $this->emailData,
                'attempt' => $this->attempts()
            ]);

            // If this is the final attempt, mark as permanently failed
            if ($this->attempts() >= $this->tries) {
                Log::error('Email job permanently failed after max retries', [
                    'email_data' => $this->emailData
                ]);
            }

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle email job failure
     */
    protected function handleJobFailure(\Throwable $exception): void
    {
        parent::handleJobFailure($exception);
        
        Log::error('Email job permanently failed', [
            'error' => $exception->getMessage(),
            'email_data' => $this->emailData
        ]);

        // Could implement additional failure handling here
        // such as storing failed emails for manual retry
    }
}