<?php

namespace App\Console\Commands;

use App\Services\ProductionOptimizationService;
use Illuminate\Console\Command;

class ProductionOptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:optimize 
                            {action : The optimization action (all|cache|assets|security|deploy|report)}
                            {--force : Force optimization even in non-production environment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize Laravel application for production deployment';

    protected ProductionOptimizationService $optimizationService;

    public function __construct(ProductionOptimizationService $optimizationService)
    {
        parent::__construct();
        $this->optimizationService = $optimizationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $force = $this->option('force');

        // Check if we're in production or force flag is used
        if (!$force && config('app.env') !== 'production') {
            $this->warn('This command is intended for production environments.');
            if (!$this->confirm('Do you want to continue?')) {
                return 1;
            }
        }

        switch ($action) {
            case 'all':
                return $this->optimizeAll();
            case 'cache':
                return $this->optimizeCache();
            case 'assets':
                return $this->optimizeAssets();
            case 'security':
                return $this->configureSecurity();
            case 'deploy':
                return $this->createDeploymentScripts();
            case 'report':
                return $this->showProductionReport();
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: all, cache, assets, security, deploy, report');
                return 1;
        }
    }

    /**
     * Run all optimizations
     */
    protected function optimizeAll(): int
    {
        $this->info('🚀 Running complete production optimization...');
        $this->newLine();

        try {
            // Run all optimization steps
            $this->optimizeCache();
            $this->optimizeAssets();
            $this->configureSecurity();
            $this->createDeploymentScripts();

            $this->newLine();
            $this->info('✅ All production optimizations completed successfully!');
            
            // Show production readiness report
            $this->showProductionReport();
            
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Production optimization failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Optimize caches and configuration
     */
    protected function optimizeCache(): int
    {
        $this->info('🔧 Optimizing caches and configuration...');

        try {
            $optimizations = $this->optimizationService->optimizeForProduction();
            
            foreach ($optimizations as $optimization) {
                $this->line("  ✓ {$optimization}");
            }

            $this->info('Cache optimization completed.');
            return 0;

        } catch (\Exception $e) {
            $this->error('Cache optimization failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Optimize assets
     */
    protected function optimizeAssets(): int
    {
        $this->info('📦 Optimizing assets...');

        try {
            $optimizations = $this->optimizationService->optimizeAssets();
            
            foreach ($optimizations as $optimization) {
                $this->line("  ✓ {$optimization}");
            }

            $this->info('Asset optimization completed.');
            return 0;

        } catch (\Exception $e) {
            $this->error('Asset optimization failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Configure security settings
     */
    protected function configureSecurity(): int
    {
        $this->info('🔒 Configuring security settings...');

        try {
            $securityOptimizations = $this->optimizationService->configureSecurityHeaders();
            $loggingOptimizations = $this->optimizationService->configureProductionLogging();
            
            $optimizations = array_merge($securityOptimizations, $loggingOptimizations);
            
            foreach ($optimizations as $optimization) {
                $this->line("  ✓ {$optimization}");
            }

            $this->info('Security configuration completed.');
            return 0;

        } catch (\Exception $e) {
            $this->error('Security configuration failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Create deployment scripts
     */
    protected function createDeploymentScripts(): int
    {
        $this->info('📜 Creating deployment scripts...');

        try {
            $optimizations = $this->optimizationService->createDeploymentScripts();
            
            foreach ($optimizations as $optimization) {
                $this->line("  ✓ {$optimization}");
            }

            $this->info('Deployment scripts created.');
            $this->newLine();
            $this->info('Available scripts:');
            $this->line('  • ./deploy.sh - Deploy application to production');
            $this->line('  • ./rollback.sh [timestamp] - Rollback to previous version');
            $this->line('  • ./health-check.sh - Run application health checks');
            
            return 0;

        } catch (\Exception $e) {
            $this->error('Deployment script creation failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show production readiness report
     */
    protected function showProductionReport(): int
    {
        $this->info('📊 Production Readiness Report');
        $this->info('==============================');

        try {
            $report = $this->optimizationService->getProductionReadinessReport();
            
            // Show overall score
            $score = $report['overall_score'];
            $scoreColor = $score >= 80 ? 'green' : ($score >= 60 ? 'yellow' : 'red');
            $this->newLine();
            $this->line("Overall Production Readiness: <fg={$scoreColor}>{$score}%</>");
            $this->newLine();

            // Show category results
            foreach (['environment', 'security', 'performance', 'monitoring', 'deployment'] as $category) {
                $this->showCategoryResults(ucfirst($category), $report[$category]);
            }

            // Show recommendations
            if (!empty($report['recommendations'])) {
                $this->newLine();
                $this->warn('Recommendations:');
                foreach ($report['recommendations'] as $recommendation) {
                    $this->line("  • {$recommendation}");
                }
            }

            $this->newLine();
            if ($score >= 80) {
                $this->info('🎉 Your application is ready for production!');
            } elseif ($score >= 60) {
                $this->warn('⚠️  Your application needs some improvements before production.');
            } else {
                $this->error('❌ Your application is not ready for production.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to generate production report: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show category results
     */
    protected function showCategoryResults(string $category, array $checks): void
    {
        $this->info("{$category}:");
        
        foreach ($checks as $check => $passed) {
            $status = $passed ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $checkName = str_replace('_', ' ', ucwords($check, '_'));
            $this->line("  {$status} {$checkName}");
        }
        
        $this->newLine();
    }
}
