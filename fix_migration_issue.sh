#!/bin/bash
# Fix for duplicate mission_id column in notifications table

echo "Fixing duplicate mission_id column issue..."

# Check if the problematic migration file exists
if ls database/migrations/*2025_09_05*notifications*.php 1> /dev/null 2>&1; then
    echo "Found duplicate migration file. Removing it..."
    rm database/migrations/*2025_09_05*notifications*.php
    echo "Duplicate migration file removed."
else
    echo "No duplicate migration file found."
fi

# Clear all caches
echo "Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check migration status
echo "Current migration status:"
php artisan migrate:status

# Try to run migrations
echo "Running migrations..."
php artisan migrate --force

echo "Fix completed!"