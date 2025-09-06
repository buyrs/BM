#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Print with color
print_message() {
    echo -e "${GREEN}[INSTALLER]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    print_error "Please run as root"
    exit 1
fi

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check system requirements
check_requirements() {
    print_message "Checking system requirements..."
    
    # Check PHP version
    if command_exists php; then
        PHP_VERSION=$(php -v | grep -oP '(?<=PHP )[0-9]+\.[0-9]+')
        if (( $(echo "$PHP_VERSION < 8.1" | bc -l) )); then
            print_error "PHP 8.1 or higher is required"
            exit 1
        fi
    else
        print_error "PHP is not installed"
        exit 1
    fi

    # Check Node.js version
    if command_exists node; then
        NODE_VERSION=$(node -v | cut -d'v' -f2)
        if (( $(echo "$NODE_VERSION < 16" | bc -l) )); then
            print_error "Node.js 16 or higher is required"
            exit 1
        fi
    else
        print_error "Node.js is not installed"
        exit 1
    fi

    # Check Composer
    if ! command_exists composer; then
        print_error "Composer is not installed"
        exit 1
    fi

    # Check required PHP extensions
    REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "mbstring" "xml" "curl" "gd" "zip")
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if ! php -m | grep -q "^$ext$"; then
            print_error "PHP extension $ext is not installed"
            exit 1
        fi
    done
}

# Install system dependencies
install_dependencies() {
    print_message "Installing system dependencies..."
    
    # Update package list
    apt-get update

    # Install required packages
    apt-get install -y \
        nginx \
        mysql-server \
        php8.1-fpm \
        php8.1-mysql \
        php8.1-mbstring \
        php8.1-xml \
        php8.1-curl \
        php8.1-gd \
        php8.1-zip \
        php8.1-bcmath \
        unzip \
        git \
        supervisor
}

# Configure Nginx
configure_nginx() {
    print_message "Configuring Nginx..."
    
    # Create Nginx configuration
    cat > /etc/nginx/sites-available/laravel << 'EOL'
server {
    listen 80;
    server_name _;
    root /var/www/html/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOL

    # Enable the site
    ln -sf /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default

    # Test Nginx configuration
    nginx -t

    # Restart Nginx
    systemctl restart nginx
}

# Configure MySQL
configure_mysql() {
    print_message "Configuring MySQL..."
    
    # Secure MySQL installation
    mysql_secure_installation
}

# Setup application
setup_application() {
    print_message "Setting up application..."
    
    # Create application directory
    mkdir -p /var/www/html
    chown -R www-data:www-data /var/www/html

    # Clone repository (replace with your repository URL)
    git clone https://github.com/your-repo/your-app.git /var/www/html

    # Set proper permissions
    chown -R www-data:www-data /var/www/html
    chmod -R 755 /var/w
    chmod -R 775 /var/www/html/storage
    chmod -R 775 /var/www/html/bootstrap/cache

    # Install PHP dependencies
    cd /var/www/html
    composer install --no-dev --optimize-autoloader

    # Install Node.js dependencies
    npm install
    npm run build

    # Copy environment file
    cp .env.example .env

    # Generate application key
    php artisan key:generate

    # Run migrations
    php artisan migrate --force

    # Optimize Laravel
    php artisan optimize
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
}

# Configure Supervisor for queue worker
configure_supervisor() {
    print_message "Configuring Supervisor..."
    
    # Create Supervisor configuration
    cat > /etc/supervisor/conf.d/laravel-worker.conf << 'EOL'
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
stopwaitsecs=3600
EOL

    # Reload Supervisor
    supervisorctl reread
    supervisorctl update
    supervisorctl start all
}

# Configure SSL with Let's Encrypt
configure_ssl() {
    print_message "Configuring SSL..."
    
    # Install Certbot
    apt-get install -y certbot python3-certbot-nginx

    # Get domain name
    read -p "Enter your domain name: " DOMAIN_NAME

    # Obtain SSL certificate
    certbot --nginx -d $DOMAIN_NAME
}

# Main installation process
main() {
    print_message "Starting installation..."
    
    check_requirements
    install_dependencies
    configure_nginx
    configure_mysql
    setup_application
    configure_supervisor
    
    # Ask if SSL should be configured
    read -p "Do you want to configure SSL with Let\'s Encrypt? (y/n): " SSL_CHOICE
    if [[ $SSL_CHOICE == "y" || $SSL_CHOICE == "Y" ]]; then
        configure_ssl
    fi

    print_message "Installation completed successfully!"
    print_message "Please update your .env file with the correct database credentials and other settings."
}

# Run main function
main 
