<?php

namespace App\Console\Commands;

use App\Services\ConfigurationService;
use Illuminate\Console\Command;

class ProductionConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:production 
                            {action : Action to perform (check, apply, validate)}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage production configuration settings';

    protected ConfigurationService $configService;

    /**
     * Create a new command instance.
     */
    public function __construct(ConfigurationService $configService)
    {
        parent::__construct();
        $this->configService = $configService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'check' => $this->checkConfiguration(),
            'apply' => $this->applyConfiguration(),
            'validate' => $this->validateConfiguration(),
            default => $this->handleInvalidAction($action)
        };
    }

    /**
     * Check current configuration
     */
    protected function checkConfiguration(): int
    {
        $validation = $this->configService->validateProductionConfig();

        if ($this->option('json')) {
            $this->line(json_encode($validation, JSON_PRETTY_PRINT));
        } else {
            $this->displayValidationResults($validation);
        }

        return $validation['status'] === 'ready' ? Command::SUCCESS : 1;
    }

    /**
     * Apply production configuration
     */
    protected function applyConfiguration(): int
    {
        if (!app()->environment('production')) {
            $this->warn('Not in production environment. Configuration will be applied but may not take full effect.');
        }

        $this->info('Applying production configuration optimizations...');

        try {
            $applied = $this->configService->applyProductionOptimizations();

            $this->info('✅ Production configuration applied successfully!');
            
            foreach ($applied as $optimization) {
                $this->line("  • {$optimization}");
            }

            // Re-validate after applying
            $validation = $this->configService->validateProductionConfig();
            $this->line('');
            $this->info('Post-application validation:');
            $this->displayValidationResults($validation);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to apply production configuration: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Validate configuration
     */
    protected function validateConfiguration(): int
    {
        return $this->checkConfiguration();
    }

    /**
     * Display validation results
     */
    protected function displayValidationResults(array $validation): void
    {
        $statusColor = $validation['status'] === 'ready' ? 'green' : 'yellow';
        $this->line("Status: <fg={$statusColor}>" . strtoupper($validation['status']) . '</fg>');

        if (!empty($validation['issues'])) {
            $this->line('');
            $this->warn('Issues Found:');
            foreach ($validation['issues'] as $issue) {
                $this->line("  • {$issue}");
            }
        }

        if (!empty($validation['recommendations'])) {
            $this->line('');
            $this->info('Recommendations:');
            foreach ($validation['recommendations'] as $recommendation) {
                $this->line("  • {$recommendation}");
            }
        }

        if (empty($validation['issues']) && empty($validation['recommendations'])) {
            $this->line('');
            $this->info('✅ Configuration is production-ready!');
        }
    }

    /**
     * Handle invalid action
     */
    protected function handleInvalidAction(string $action): int
    {
        $this->error("Invalid action: {$action}");
        $this->info('Available actions: check, apply, validate');
        return Command::FAILURE;
    }
}