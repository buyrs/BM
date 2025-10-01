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