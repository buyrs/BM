<?php

namespace App\Providers;

use App\Services\EmailService;
use App\Contracts\EmailProviderInterface;
use App\Services\Email\SmtpEmailProvider;
use App\Services\Email\MailgunEmailProvider;
use App\Services\Email\SendGridEmailProvider;
use App\Services\Email\SesEmailProvider;
use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(EmailService::class, function ($app) {
            return new EmailService();
        });

        // Register email providers
        $this->app->bind('email.provider.smtp', SmtpEmailProvider::class);
        $this->app->bind('email.provider.mailgun', MailgunEmailProvider::class);
        $this->app->bind('email.provider.sendgrid', SendGridEmailProvider::class);
        $this->app->bind('email.provider.ses', SesEmailProvider::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Validate email configuration on boot in production
        if ($this->app->environment('production')) {
            $this->validateProductionEmailConfig();
        }
    }

    /**
     * Validate production email configuration
     */
    protected function validateProductionEmailConfig(): void
    {
        $emailService = $this->app->make(EmailService::class);
        
        if (!$emailService->validateConfiguration()) {
            \Log::warning('Production email configuration validation failed');
        }
    }
}