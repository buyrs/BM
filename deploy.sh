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