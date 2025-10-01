#!/bin/bash

# Bail Mobilite Deployment Script
# Usage: ./deploy.sh [environment]
# Example: ./deploy.sh production

set -e  # Exit immediately if a command exits with a non-zero status

ENVIRONMENT=${1:-staging}
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEPLOY_DIR="/var/www/bail-mobilite"

# Log function
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

# Check if running as root (or with sudo)
if [[ $EUID -eq 0 ]]; then
   log "This script should not be run as root"
   exit 1
fi

# Check if environment is valid
if [[ "$ENVIRONMENT" != "staging" && "$ENVIRONMENT" != "production" ]]; then
    log "Invalid environment. Use 'staging' or 'production'"
    exit 1
fi

log "Starting deployment for $ENVIRONMENT environment"

# Backup current version
log "Creating backup of current version..."
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/backups/bail-mobilite_${TIMESTAMP}"
mkdir -p "$BACKUP_DIR"

# If application directory exists, backup important files
if [ -d "$DEPLOY_DIR" ]; then
    cp -r "$DEPLOY_DIR" "$BACKUP_DIR/"
    log "Backup created at $BACKUP_DIR"
else
    log "No previous installation found, creating new directory"
    sudo mkdir -p "$DEPLOY_DIR"
fi

# Get the latest code
log "Pulling latest code from repository..."
if [ -d "$DEPLOY_DIR/.git" ]; then
    cd "$DEPLOY_DIR"
    git fetch origin
    if [ "$ENVIRONMENT" = "production" ]; then
        git checkout main
        git pull origin main
    else
        git checkout develop
        git pull origin develop
    fi
else
    if [ "$ENVIRONMENT" = "production" ]; then
        git clone -b main https://github.com/your-username/bail-mobilite.git "$DEPLOY_DIR"
    else
        git clone -b develop https://github.com/your-username/bail-mobilite.git "$DEPLOY_DIR"
    fi
    cd "$DEPLOY_DIR"
fi

# Set proper ownership
log "Setting proper file permissions..."
sudo chown -R $USER:$USER "$DEPLOY_DIR"
sudo chmod -R 755 "$DEPLOY_DIR"
sudo chmod -R 775 "$DEPLOY_DIR/storage"
sudo chmod -R 775 "$DEPLOY_DIR/bootstrap/cache"

# Install PHP dependencies
log "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# Install and build frontend assets
log "Installing and building frontend assets..."
npm install --production
npm run build

# Copy environment file based on environment
log "Setting up environment configuration..."
if [ -f ".env.$ENVIRONMENT" ]; then
    cp ".env.$ENVIRONMENT" .env
else
    log "Environment file .env.$ENVIRONMENT not found. Using .env.example"
    cp .env.example .env
    log "Please configure your .env file before proceeding"
    exit 1
fi

# Generate application key if not exists
if ! grep -q "APP_KEY=" .env; then
    log "Generating application key..."
    php artisan key:generate --force
fi

# Run database migrations
log "Running database migrations..."
php artisan migrate --force

# Clear and cache configuration for performance
log "Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart services
log "Restarting services..."
if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl restart bail-mobilite-worker:*
    log "Supervisor queue workers restarted"
fi

if command -v systemctl &> /dev/null; then
    sudo systemctl reload nginx
    log "Nginx configuration reloaded"
fi

# Run tests to verify deployment
log "Running post-deployment tests..."
php artisan test --parallel --exclude-group=integration

log "Deployment completed successfully for $ENVIRONMENT environment!"
log "Application is now running at: $(grep APP_URL .env | cut -d '=' -f2)"

# Optional: Send deployment notification (requires configuration)
# curl -X POST -H 'Content-type: application/json' \
#   --data '{"text":"Deployment to '$ENVIRONMENT' completed successfully!"}' \
#   $SLACK_WEBHOOK_URL