# BM Application Deployment Guide

## Prerequisites

1. A server with PHP 8.1+ installed
2. MariaDB or MySQL database server
3. Composer
4. Git (optional, for deployment)

## Deployment Steps

### 1. Upload Application Files

Upload all application files to your server. You can use:
- Git clone
- SFTP/FTP
- rsync
- Any other file transfer method

### 2. Configure Environment

1. Copy `.env.cloud` to `.env`:
   ```bash
   cp .env.cloud .env
   ```

2. Edit `.env` with your actual configuration:
   ```bash
   nano .env
   ```
   
   Update these values:
   ```
   APP_URL=https://your-domain.com
   DB_HOST=your-mariadb-host
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password
   ```

### 3. Install Dependencies

Run the installation script:
```bash
./install.sh
```

Or manually run the commands:
```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Generate application key
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Run seeders
php artisan db:seed --force

# Optimize Laravel
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
php artisan storage:link
```

### 4. Configure Web Server

#### For Apache:
Create a virtual host pointing to the `public` directory:
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/your/project/public
    
    <Directory /path/to/your/project/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### For Nginx:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your/project/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. Set Permissions

Ensure these directories are writable by the web server:
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

### 6. Set Up Cron Job

Add this cron job for Laravel's task scheduler:
```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

### 7. Set Up Queue Worker (if using queues)

Start a queue worker:
```bash
php artisan queue:work --daemon
```

Or set up a process manager like Supervisor to keep the worker running.

## Troubleshooting

### Database Connection Issues
- Verify database credentials in `.env`
- Ensure database server is running and accessible
- Check firewall settings

### Permission Issues
- Ensure `storage/` and `bootstrap/cache/` directories are writable
- Check web server user permissions

### Application Issues
- Check Laravel logs in `storage/logs/laravel.log`
- Ensure `APP_KEY` is set in `.env`

## Updating the Application

To update the application:

1. Upload new files
2. Run migrations:
   ```bash
   php artisan migrate --force
   ```
3. Clear caches:
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```
4. Re-optimize:
   ```bash
   php artisan optimize
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```