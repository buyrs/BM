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