# Bail Mobilite Platform - Production Deployment

This document outlines the steps to deploy the Bail Mobilite Platform to a production environment.

## Prerequisites

- Server with Ubuntu 20.04+ or CentOS 8+
- Docker and Docker Compose
- Nginx (if not using the provided Docker setup)
- SSL certificate (Let's Encrypt or purchased)

## Production Environment Setup

### Option 1: Docker Deployment (Recommended)

1. **Clone the repository:**
```bash
git clone https://github.com/your-username/bail-mobilite.git
cd bail-mobilite
```

2. **Set up environment variables:**
```bash
cp .env.production .env
```
Edit the `.env` file with your production settings.

3. **Generate application key:**
```bash
docker-compose run --rm app php artisan key:generate
```

4. **Build and start services:**
```bash
docker-compose up -d
```

5. **Run database migrations:**
```bash
docker-compose exec app php artisan migrate --force
```

6. **Seed initial data (optional):**
```bash
docker-compose exec app php artisan db:seed
```

7. **Optimize the application:**
```bash
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### Option 2: Traditional Server Deployment

1. **Set up the server:**
   - Install PHP 8.2+ with required extensions
   - Install MySQL 8.0+ or MariaDB 10.4+
   - Install Redis
   - Install Nginx

2. **Deploy the application:**
```bash
git clone https://github.com/your-username/bail-mobilite.git /var/www/bail-mobilite
cd /var/www/bail-mobilite
```

3. **Install dependencies:**
```bash
composer install --no-dev --optimize-autoloader
npm install --production
npm run build
```

4. **Configure environment:**
```bash
cp .env.production .env
php artisan key:generate
```

5. **Set permissions:**
```bash
chown -R www-data:www-data /var/www/bail-mobilite
chmod -R 755 /var/www/bail-mobilite
chmod -R 775 /var/www/bail-mobilite/storage
chmod -R 775 /var/www/bail-mobilite/bootstrap/cache
```

6. **Configure Nginx:**
Copy the nginx configuration from `config/nginx.conf` to `/etc/nginx/sites-available/bail-mobilite` and enable it.

7. **Run database migrations:**
```bash
php artisan migrate --force
```

## Performance Optimizations

### Caching
The application uses Redis for caching. Ensure Redis is properly configured and running:
```bash
# Check Redis status
systemctl status redis

# Monitor Redis performance
redis-cli monitor
```

### Queue Processing
Set up queue workers for background job processing:
```bash
# Using Supervisor (recommended)
sudo nano /etc/supervisor/conf.d/bail-mobilite-worker.conf
```

Add the following configuration:
```
[program:bail-mobilite-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/bail-mobilite/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/bail-mobilite/storage/logs/queue-worker.log
```

Start the queue workers:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start bail-mobilite-worker:*
```

## Security Considerations

1. **Environment Security:**
   - Never commit `.env` files to version control
   - Use strong, unique passwords for database and services
   - Regularly rotate API keys and secrets

2. **HTTPS:** 
   - Always use HTTPS in production
   - Implement HSTS headers
   - Use strong TLS configuration

3. **Database Security:**
   - Use strong passwords for database users
   - Limit database user privileges
   - Backup regularly with encryption

4. **File Upload Security:**
   - Validate file types and sizes
   - Store uploaded files outside the public directory
   - Scan uploaded files for malware

## Monitoring and Logging

### Log Rotation
Set up log rotation to prevent log files from consuming too much space:
```bash
sudo nano /etc/logrotate.d/bail-mobilite
```

Add:
```
/var/www/bail-mobilite/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php8.2-fpm > /dev/null 2>&1 || true
    endscript
}
```

### Health Checks
The application provides health check endpoints:
- `/health` - Basic health check
- `/health/ready` - Readiness check
- `/health/live` - Liveness check
- `/metrics` - Application metrics

## Backup Strategy

### Database Backup
```bash
# Daily backup script
mysqldump -u your_db_user -p your_database_name > /backups/bailmobilite_$(date +%Y%m%d).sql
```

### File Backup
Backup the following directories:
- `/var/www/bail-mobilite/storage/` - Contains uploaded files and logs
- `/var/www/bail-mobilite/.env` - Environment configuration
- Database dump files

## Updating the Application

### Docker Deployment
```bash
# Pull latest changes
git pull origin main

# Rebuild containers
docker-compose build --no-cache
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate
```

### Traditional Deployment
```bash
# Pull latest changes
git pull origin main

# Install new dependencies (if any)
composer install --no-dev

# Run migrations
php artisan migrate

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild optimized caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Troubleshooting

### Common Issues

1. **Permission Errors:**
```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/bail-mobilite
sudo chmod -R 755 /var/www/bail-mobilite
sudo chmod -R 775 /var/www/bail-mobilite/storage
sudo chmod -R 775 /var/www/bail-mobilite/bootstrap/cache
```

2. **Cache Issues:**
```bash
# Clear and rebuild caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

3. **Queue Worker Issues:**
```bash
# Restart queue workers
sudo supervisorctl restart bail-mobilite-worker:*
```

## Support

For support, please contact the development team or refer to the application documentation.