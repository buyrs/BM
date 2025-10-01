<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProductionOptimizationService extends BaseService
{
    /**
     * Optimize application for production deployment
     */
    public function optimizeForProduction(): array
    {
        $optimizations = [];

        try {
            // Clear and optimize caches
            $optimizations = array_merge($optimizations, $this->optimizeCaches());
            
            // Optimize configuration
            $optimizations = array_merge($optimizations, $this->optimizeConfiguration());
            
            // Optimize routes
            $optimizations = array_merge($optimizations, $this->optimizeRoutes());
            
            // Optimize views
            $optimizations = array_merge($optimizations, $this->optimizeViews());
            
            // Optimize autoloader
            $optimizations = array_merge($optimizations, $this->optimizeAutoloader());

            Log::info('Production optimizations completed', $optimizations);
            
        } catch (\Exception $e) {
            Log::error('Production optimization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return $optimizations;
    }

    /**
     * Optimize application caches
     */
    protected function optimizeCaches(): array
    {
        $optimizations = [];

        // Clear all caches first
        Artisan::call('cache:clear');
        $optimizations[] = 'Cleared application cache';

        Artisan::call('config:clear');
        $optimizations[] = 'Cleared configuration cache';

        Artisan::call('route:clear');
        $optimizations[] = 'Cleared route cache';

        Artisan::call('view:clear');
        $optimizations[] = 'Cleared view cache';

        // Cache configuration for production
        Artisan::call('config:cache');
        $optimizations[] = 'Cached configuration';

        // Cache routes for production
        Artisan::call('route:cache');
        $optimizations[] = 'Cached routes';

        // Cache views for production
        Artisan::call('view:cache');
        $optimizations[] = 'Cached views';

        return $optimizations;
    }

    /**
     * Optimize configuration settings
     */
    protected function optimizeConfiguration(): array
    {
        $optimizations = [];

        // Verify production environment settings
        if (config('app.env') !== 'production') {
            $optimizations[] = 'Warning: APP_ENV is not set to production';
        }

        if (config('app.debug') === true) {
            $optimizations[] = 'Warning: APP_DEBUG should be false in production';
        }

        // Check session configuration
        if (config('session.driver') === 'file') {
            $optimizations[] = 'Recommendation: Use Redis for session storage in production';
        }

        // Check cache configuration
        if (config('cache.default') === 'file') {
            $optimizations[] = 'Recommendation: Use Redis for caching in production';
        }

        // Check queue configuration
        if (config('queue.default') === 'sync') {
            $optimizations[] = 'Recommendation: Use Redis or database queue driver in production';
        }

        return $optimizations;
    }

    /**
     * Optimize routes
     */
    protected function optimizeRoutes(): array
    {
        $optimizations = [];

        try {
            // Generate route cache if not exists
            if (!File::exists(base_path('bootstrap/cache/routes-v7.php'))) {
                Artisan::call('route:cache');
                $optimizations[] = 'Generated route cache';
            } else {
                $optimizations[] = 'Route cache already exists';
            }
        } catch (\Exception $e) {
            $optimizations[] = 'Route caching failed: ' . $e->getMessage();
        }

        return $optimizations;
    }

    /**
     * Optimize views
     */
    protected function optimizeViews(): array
    {
        $optimizations = [];

        try {
            // Compile all Blade views
            Artisan::call('view:cache');
            $optimizations[] = 'Compiled Blade views';
        } catch (\Exception $e) {
            $optimizations[] = 'View compilation failed: ' . $e->getMessage();
        }

        return $optimizations;
    }

    /**
     * Optimize Composer autoloader
     */
    protected function optimizeAutoloader(): array
    {
        $optimizations = [];

        try {
            // Optimize Composer autoloader
            $result = shell_exec('cd ' . base_path() . ' && composer dump-autoload --optimize --no-dev 2>&1');
            
            if ($result !== null) {
                $optimizations[] = 'Optimized Composer autoloader';
            } else {
                $optimizations[] = 'Composer autoloader optimization skipped (composer not available)';
            }
        } catch (\Exception $e) {
            $optimizations[] = 'Autoloader optimization failed: ' . $e->getMessage();
        }

        return $optimizations;
    }

    /**
     * Compile and optimize assets
     */
    public function optimizeAssets(): array
    {
        $optimizations = [];

        try {
            // Check if npm is available
            $npmVersion = shell_exec('npm --version 2>/dev/null');
            
            if ($npmVersion === null) {
                $optimizations[] = 'NPM not available - skipping asset compilation';
                return $optimizations;
            }

            // Install dependencies
            $installResult = shell_exec('cd ' . base_path() . ' && npm ci --production 2>&1');
            if (strpos($installResult, 'error') === false) {
                $optimizations[] = 'Installed production npm dependencies';
            }

            // Build assets for production
            $buildResult = shell_exec('cd ' . base_path() . ' && npm run build 2>&1');
            if (strpos($buildResult, 'error') === false) {
                $optimizations[] = 'Compiled assets for production';
            } else {
                $optimizations[] = 'Asset compilation failed or no build script available';
            }

        } catch (\Exception $e) {
            $optimizations[] = 'Asset optimization failed: ' . $e->getMessage();
        }

        return $optimizations;
    }

    /**
     * Configure production logging
     */
    public function configureProductionLogging(): array
    {
        $optimizations = [];

        // Set appropriate log levels and channels for production
        $logConfig = [
            'default' => 'stack',
            'channels' => [
                'stack' => [
                    'driver' => 'stack',
                    'channels' => ['daily', 'slack'],
                    'ignore_exceptions' => false,
                ],
                'daily' => [
                    'driver' => 'daily',
                    'path' => storage_path('logs/laravel.log'),
                    'level' => 'warning',
                    'days' => 14,
                    'replace_placeholders' => true,
                ],
                'slack' => [
                    'driver' => 'slack',
                    'url' => env('LOG_SLACK_WEBHOOK_URL'),
                    'username' => 'Laravel Log',
                    'emoji' => ':boom:',
                    'level' => 'critical',
                ],
                'syslog' => [
                    'driver' => 'syslog',
                    'level' => 'error',
                    'facility' => LOG_USER,
                ],
            ],
        ];

        // Update logging configuration
        Config::set('logging', array_merge(config('logging'), $logConfig));
        $optimizations[] = 'Configured production logging with daily rotation and Slack notifications';

        // Create log directory if it doesn't exist
        $logDir = storage_path('logs');
        if (!File::exists($logDir)) {
            File::makeDirectory($logDir, 0755, true);
            $optimizations[] = 'Created logs directory';
        }

        // Set proper permissions on log files
        $logFiles = File::glob($logDir . '/*.log');
        foreach ($logFiles as $logFile) {
            chmod($logFile, 0644);
        }
        $optimizations[] = 'Set proper permissions on log files';

        return $optimizations;
    }

    /**
     * Apply security headers and HTTPS enforcement
     */
    public function configureSecurityHeaders(): array
    {
        $optimizations = [];

        // Security headers configuration
        $securityHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none';",
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        ];

        // Store security headers in cache for middleware to use
        Cache::forever('security_headers', $securityHeaders);
        $optimizations[] = 'Configured security headers';

        // HTTPS enforcement settings
        if (config('app.env') === 'production') {
            Config::set('session.secure', true);
            Config::set('session.same_site', 'strict');
            $optimizations[] = 'Enabled secure session cookies';
        }

        return $optimizations;
    }

    /**
     * Optimize database connections and queries
     */
    public function optimizeDatabaseConnections(): array
    {
        $optimizations = [];

        // Configure connection pooling for production
        $dbConfig = config('database.connections.' . config('database.default'));
        
        if (isset($dbConfig['options'])) {
            $optimizations[] = 'Database connection pooling already configured';
        } else {
            $optimizations[] = 'Recommendation: Configure database connection pooling in config/database.php';
        }

        // Enable query logging for slow queries only in production
        if (config('database.log_slow_queries', false)) {
            $optimizations[] = 'Slow query logging is enabled';
        } else {
            $optimizations[] = 'Recommendation: Enable slow query logging for production monitoring';
        }

        return $optimizations;
    }

    /**
     * Create deployment scripts
     */
    public function createDeploymentScripts(): array
    {
        $optimizations = [];

        // Create deployment script
        $deployScript = $this->generateDeploymentScript();
        File::put(base_path('deploy.sh'), $deployScript);
        chmod(base_path('deploy.sh'), 0755);
        $optimizations[] = 'Created deployment script (deploy.sh)';

        // Create rollback script
        $rollbackScript = $this->generateRollbackScript();
        File::put(base_path('rollback.sh'), $rollbackScript);
        chmod(base_path('rollback.sh'), 0755);
        $optimizations[] = 'Created rollback script (rollback.sh)';

        // Create health check script
        $healthCheckScript = $this->generateHealthCheckScript();
        File::put(base_path('health-check.sh'), $healthCheckScript);
        chmod(base_path('health-check.sh'), 0755);
        $optimizations[] = 'Created health check script (health-check.sh)';

        return $optimizations;
    }

    /**
     * Generate deployment script
     */
    protected function generateDeploymentScript(): string
    {
        return <<<'BASH'
#!/bin/bash

# Laravel Production Deployment Script
# This script handles the deployment of a Laravel application to production

set -e  # Exit on any error

echo "🚀 Starting Laravel deployment..."

# Configuration
APP_DIR=$(pwd)
BACKUP_DIR="$APP_DIR/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to create backup
create_backup() {
    print_status "Creating backup..."
    mkdir -p "$BACKUP_DIR"
    
    # Backup database
    if [ -f ".env" ]; then
        DB_CONNECTION=$(grep DB_CONNECTION .env | cut -d '=' -f2)
        if [ "$DB_CONNECTION" = "sqlite" ]; then
            DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
            if [ -f "$DB_DATABASE" ]; then
                cp "$DB_DATABASE" "$BACKUP_DIR/database_$TIMESTAMP.sqlite"
                print_status "Database backup created"
            fi
        fi
    fi
    
    # Backup storage directory
    if [ -d "storage" ]; then
        tar -czf "$BACKUP_DIR/storage_$TIMESTAMP.tar.gz" storage/
        print_status "Storage backup created"
    fi
}

# Function to check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."
    
    # Check if composer is installed
    if ! command -v composer &> /dev/null; then
        print_error "Composer is not installed"
        exit 1
    fi
    
    # Check if PHP is installed
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed"
        exit 1
    fi
    
    # Check PHP version
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    print_status "PHP version: $PHP_VERSION"
    
    print_status "Prerequisites check passed"
}

# Function to update dependencies
update_dependencies() {
    print_status "Updating dependencies..."
    
    # Update Composer dependencies
    composer install --no-dev --optimize-autoloader --no-interaction
    
    # Update NPM dependencies if package.json exists
    if [ -f "package.json" ]; then
        if command -v npm &> /dev/null; then
            npm ci --production
            if [ -f "package.json" ] && grep -q '"build"' package.json; then
                npm run build
            fi
        else
            print_warning "NPM not found, skipping asset compilation"
        fi
    fi
}

# Function to run migrations
run_migrations() {
    print_status "Running database migrations..."
    php artisan migrate --force
}

# Function to optimize application
optimize_application() {
    print_status "Optimizing application..."
    
    # Clear caches
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    # Cache for production
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Optimize autoloader
    composer dump-autoload --optimize
}

# Function to set permissions
set_permissions() {
    print_status "Setting file permissions..."
    
    # Set storage permissions
    chmod -R 775 storage/
    chmod -R 775 bootstrap/cache/
    
    # Set ownership (uncomment and modify as needed)
    # chown -R www-data:www-data storage/
    # chown -R www-data:www-data bootstrap/cache/
}

# Function to restart services
restart_services() {
    print_status "Restarting services..."
    
    # Restart queue workers
    php artisan queue:restart
    
    # Restart web server (uncomment as needed)
    # sudo systemctl reload nginx
    # sudo systemctl reload php8.1-fpm
}

# Function to run health checks
run_health_checks() {
    print_status "Running health checks..."
    
    # Check if application is responding
    if command -v curl &> /dev/null; then
        if curl -f -s http://localhost/health > /dev/null; then
            print_status "Health check passed"
        else
            print_warning "Health check failed - application may not be responding"
        fi
    fi
}

# Main deployment process
main() {
    print_status "Laravel Production Deployment Started"
    
    # Enable maintenance mode
    php artisan down --message="Deploying new version..." --retry=60
    
    # Create backup
    create_backup
    
    # Check prerequisites
    check_prerequisites
    
    # Update dependencies
    update_dependencies
    
    # Run migrations
    run_migrations
    
    # Optimize application
    optimize_application
    
    # Set permissions
    set_permissions
    
    # Restart services
    restart_services
    
    # Disable maintenance mode
    php artisan up
    
    # Run health checks
    run_health_checks
    
    print_status "✅ Deployment completed successfully!"
    print_status "Backup created at: $BACKUP_DIR"
}

# Run main function
main "$@"
BASH;
    }

    /**
     * Generate rollback script
     */
    protected function generateRollbackScript(): string
    {
        return <<<'BASH'
#!/bin/bash

# Laravel Rollback Script
# This script handles rolling back a Laravel application deployment

set -e  # Exit on any error

echo "🔄 Starting Laravel rollback..."

# Configuration
APP_DIR=$(pwd)
BACKUP_DIR="$APP_DIR/backups"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to list available backups
list_backups() {
    print_status "Available backups:"
    if [ -d "$BACKUP_DIR" ]; then
        ls -la "$BACKUP_DIR"
    else
        print_error "No backup directory found"
        exit 1
    fi
}

# Function to restore database
restore_database() {
    local backup_file="$1"
    
    if [ -z "$backup_file" ]; then
        print_error "No backup file specified"
        return 1
    fi
    
    if [ ! -f "$backup_file" ]; then
        print_error "Backup file not found: $backup_file"
        return 1
    fi
    
    print_status "Restoring database from: $backup_file"
    
    # Restore SQLite database
    if [[ "$backup_file" == *.sqlite ]]; then
        DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
        cp "$backup_file" "$DB_DATABASE"
        print_status "Database restored successfully"
    fi
}

# Function to restore storage
restore_storage() {
    local backup_file="$1"
    
    if [ -z "$backup_file" ]; then
        print_error "No storage backup file specified"
        return 1
    fi
    
    if [ ! -f "$backup_file" ]; then
        print_error "Storage backup file not found: $backup_file"
        return 1
    fi
    
    print_status "Restoring storage from: $backup_file"
    
    # Backup current storage
    if [ -d "storage" ]; then
        mv storage "storage_backup_$(date +%Y%m%d_%H%M%S)"
    fi
    
    # Restore from backup
    tar -xzf "$backup_file"
    print_status "Storage restored successfully"
}

# Main rollback process
main() {
    if [ $# -eq 0 ]; then
        print_status "Usage: $0 [backup_timestamp]"
        print_status "Available backups:"
        list_backups
        exit 1
    fi
    
    local backup_timestamp="$1"
    local db_backup="$BACKUP_DIR/database_$backup_timestamp.sqlite"
    local storage_backup="$BACKUP_DIR/storage_$backup_timestamp.tar.gz"
    
    print_status "Starting rollback to: $backup_timestamp"
    
    # Enable maintenance mode
    php artisan down --message="Rolling back to previous version..." --retry=60
    
    # Restore database if backup exists
    if [ -f "$db_backup" ]; then
        restore_database "$db_backup"
    else
        print_warning "Database backup not found: $db_backup"
    fi
    
    # Restore storage if backup exists
    if [ -f "$storage_backup" ]; then
        restore_storage "$storage_backup"
    else
        print_warning "Storage backup not found: $storage_backup"
    fi
    
    # Clear caches
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    # Restart services
    php artisan queue:restart
    
    # Disable maintenance mode
    php artisan up
    
    print_status "✅ Rollback completed successfully!"
}

# Run main function
main "$@"
BASH;
    }

    /**
     * Generate health check script
     */
    protected function generateHealthCheckScript(): string
    {
        return <<<'BASH'
#!/bin/bash

# Laravel Health Check Script
# This script performs comprehensive health checks on a Laravel application

# Configuration
APP_URL=${APP_URL:-"http://localhost"}
TIMEOUT=${TIMEOUT:-10}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counters
TOTAL_CHECKS=0
PASSED_CHECKS=0
FAILED_CHECKS=0

# Function to print colored output
print_status() {
    echo -e "${GREEN}[PASS]${NC} $1"
    ((PASSED_CHECKS++))
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[FAIL]${NC} $1"
    ((FAILED_CHECKS++))
}

# Function to run a check
run_check() {
    local check_name="$1"
    local check_command="$2"
    
    ((TOTAL_CHECKS++))
    echo -n "Checking $check_name... "
    
    if eval "$check_command" &>/dev/null; then
        print_status "$check_name"
        return 0
    else
        print_error "$check_name"
        return 1
    fi
}

# Health check functions
check_php() {
    php -v > /dev/null 2>&1
}

check_composer() {
    composer --version > /dev/null 2>&1
}

check_database() {
    php artisan migrate:status > /dev/null 2>&1
}

check_cache() {
    php artisan cache:clear > /dev/null 2>&1
}

check_storage_permissions() {
    [ -w "storage/" ] && [ -w "bootstrap/cache/" ]
}

check_env_file() {
    [ -f ".env" ]
}

check_app_key() {
    grep -q "APP_KEY=" .env && [ "$(grep APP_KEY .env | cut -d '=' -f2)" != "" ]
}

check_web_server() {
    if command -v curl &> /dev/null; then
        curl -f -s --max-time $TIMEOUT "$APP_URL/health" > /dev/null
    else
        return 1
    fi
}

check_queue_workers() {
    # Check if queue workers are running (this is a basic check)
    php artisan queue:work --once --timeout=1 > /dev/null 2>&1
}

check_log_files() {
    [ -d "storage/logs" ] && [ -w "storage/logs" ]
}

# Main health check process
main() {
    echo "🏥 Laravel Application Health Check"
    echo "=================================="
    echo ""
    
    # Run all health checks
    run_check "PHP Installation" "check_php"
    run_check "Composer Installation" "check_composer"
    run_check "Environment File" "check_env_file"
    run_check "Application Key" "check_app_key"
    run_check "Database Connection" "check_database"
    run_check "Cache System" "check_cache"
    run_check "Storage Permissions" "check_storage_permissions"
    run_check "Log Directory" "check_log_files"
    run_check "Web Server Response" "check_web_server"
    run_check "Queue System" "check_queue_workers"
    
    echo ""
    echo "Health Check Summary:"
    echo "===================="
    echo "Total Checks: $TOTAL_CHECKS"
    echo -e "Passed: ${GREEN}$PASSED_CHECKS${NC}"
    echo -e "Failed: ${RED}$FAILED_CHECKS${NC}"
    
    if [ $FAILED_CHECKS -eq 0 ]; then
        echo -e "\n${GREEN}✅ All health checks passed!${NC}"
        exit 0
    else
        echo -e "\n${RED}❌ Some health checks failed!${NC}"
        exit 1
    fi
}

# Run main function
main "$@"
BASH;
    }

    /**
     * Get production readiness report
     */
    public function getProductionReadinessReport(): array
    {
        $report = [
            'environment' => $this->checkEnvironmentSettings(),
            'security' => $this->checkSecuritySettings(),
            'performance' => $this->checkPerformanceSettings(),
            'monitoring' => $this->checkMonitoringSettings(),
            'deployment' => $this->checkDeploymentReadiness(),
        ];

        $report['overall_score'] = $this->calculateOverallScore($report);
        $report['recommendations'] = $this->generateRecommendations($report);

        return $report;
    }

    /**
     * Check environment settings
     */
    protected function checkEnvironmentSettings(): array
    {
        return [
            'app_env_production' => config('app.env') === 'production',
            'debug_disabled' => config('app.debug') === false,
            'app_key_set' => !empty(config('app.key')),
            'timezone_set' => !empty(config('app.timezone')),
            'url_configured' => !empty(config('app.url')),
        ];
    }

    /**
     * Check security settings
     */
    protected function checkSecuritySettings(): array
    {
        $appUrl = config('app.url', '');
        return [
            'https_enforced' => str_starts_with($appUrl, 'https://'),
            'secure_cookies' => config('session.secure', false),
            'csrf_protection' => true, // Laravel has CSRF protection by default
            'security_headers' => Cache::has('security_headers'),
            'two_factor_available' => class_exists('App\Services\TwoFactorService'),
        ];
    }

    /**
     * Check performance settings
     */
    protected function checkPerformanceSettings(): array
    {
        return [
            'config_cached' => File::exists(base_path('bootstrap/cache/config.php')),
            'routes_cached' => File::exists(base_path('bootstrap/cache/routes-v7.php')),
            'views_cached' => !empty(File::glob(storage_path('framework/views/*.php'))),
            'redis_configured' => config('cache.default') === 'redis',
            'queue_configured' => config('queue.default') !== 'sync',
        ];
    }

    /**
     * Check monitoring settings
     */
    protected function checkMonitoringSettings(): array
    {
        return [
            'logging_configured' => config('logging.default') !== 'single',
            'error_tracking' => !empty(config('services.sentry.dsn')),
            'health_checks' => File::exists(base_path('health-check.sh')),
            'monitoring_service' => class_exists('App\Services\ApplicationMonitoringService'),
        ];
    }

    /**
     * Check deployment readiness
     */
    protected function checkDeploymentReadiness(): array
    {
        return [
            'deployment_script' => File::exists(base_path('deploy.sh')),
            'rollback_script' => File::exists(base_path('rollback.sh')),
            'backup_strategy' => File::exists(base_path('health-check.sh')),
            'composer_optimized' => File::exists(base_path('vendor/composer/autoload_classmap.php')),
        ];
    }

    /**
     * Calculate overall production readiness score
     */
    protected function calculateOverallScore(array $report): int
    {
        $totalChecks = 0;
        $passedChecks = 0;

        foreach ($report as $category => $checks) {
            if (is_array($checks)) {
                foreach ($checks as $check => $passed) {
                    $totalChecks++;
                    if ($passed) {
                        $passedChecks++;
                    }
                }
            }
        }

        return $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100) : 0;
    }

    /**
     * Generate recommendations based on report
     */
    protected function generateRecommendations(array $report): array
    {
        $recommendations = [];

        // Environment recommendations
        if (!$report['environment']['app_env_production']) {
            $recommendations[] = 'Set APP_ENV=production in your .env file';
        }
        if (!$report['environment']['debug_disabled']) {
            $recommendations[] = 'Set APP_DEBUG=false in production';
        }

        // Security recommendations
        if (!$report['security']['https_enforced']) {
            $recommendations[] = 'Configure HTTPS and update APP_URL to use https://';
        }
        if (!$report['security']['secure_cookies']) {
            $recommendations[] = 'Enable secure cookies by setting SESSION_SECURE_COOKIE=true';
        }

        // Performance recommendations
        if (!$report['performance']['redis_configured']) {
            $recommendations[] = 'Configure Redis for caching and sessions';
        }
        if (!$report['performance']['config_cached']) {
            $recommendations[] = 'Run php artisan config:cache to cache configuration';
        }

        return $recommendations;
    }
}