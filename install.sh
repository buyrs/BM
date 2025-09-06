#!/bin/bash
# BM Application Installation Script for Cloud Server

echo "BM Application Installation Script"
echo "==================================="

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo "Error: .env file not found. Please create a .env file with your database configuration."
    echo "You can use .env.cloud as a template."
    exit 1
fi

# Check PHP and Composer
if ! command -v php &> /dev/null; then
    echo "Error: PHP is not installed."
    exit 1
fi

if ! command -v composer &> /dev/null; then
    echo "Error: Composer is not installed."
    exit 1
fi

echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# Generate application key if not exists
echo "Generating application key..."
php artisan key:generate --force

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Run seeders
echo "Seeding database..."
php artisan db:seed --force

# Optimize Laravel
echo "Optimizing application..."
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
echo "Creating storage link..."
php artisan storage:link

echo "Installation completed successfully!"
echo ""
echo "Next steps:"
echo "1. Configure your web server to point to the public directory"
echo "2. Make sure storage and bootstrap/cache directories are writable"
echo "3. Set up a cron job for Laravel scheduler:"
echo "   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1"
echo "4. Set up queue worker if using queues:"
echo "   php artisan queue:work --daemon"