# Cloud Server Installation Guide

This guide will help you install and configure the Laravel + Vue.js PWA application on your cloud server.

## Prerequisites

- Ubuntu 20.04 LTS or higher
- Root access to the server
- Domain name (for SSL configuration)
- Git repository access

## Installation Steps

1. **Connect to your server**
   ```bash
   ssh root@your-server-ip
   ```

2. **Download the installer**
   ```bash
   wget https://raw.githubusercontent.com/your-repo/your-app/main/install.sh
   chmod +x install.sh
   ```

3. **Run the installer**
   ```bash
   ./install.sh
   ```

4. **Follow the prompts**
   - The installer will check system requirements
   - Install necessary dependencies
   - Configure Nginx
   - Set up MySQL
   - Deploy the application
   - Configure SSL (optional)

## Post-Installation Configuration

1. **Update Environment Variables**
   ```bash
   nano /var/www/html/.env
   ```
   Update the following variables:
   ```
   APP_URL=https://your-domain.com
   DB_HOST=localhost
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

2. **Configure Queue Worker**
   The installer sets up Supervisor for queue processing. Check its status:
   ```bash
   supervisorctl status
   ```

3. **Set up Cron Jobs**
   ```bash
   crontab -e
   ```
   Add the following line:
   ```
   * * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
   ```

## Security Considerations

1. **Firewall Configuration**
   ```bash
   ufw allow 80/tcp
   ufw allow 443/tcp
   ufw enable
   ```

2. **File Permissions**
   ```bash
   chown -R www-data:www-data /var/www/html
   chmod -R 755 /var/www/html
   chmod -R 775 /var/www/html/storage
   chmod -R 775 /var/www/html/bootstrap/cache
   ```

## Maintenance

1. **Update Application**
   ```bash
   cd /var/www/html
   git pull
   composer install --no-dev --optimize-autoloader
   npm install
   npm run build
   php artisan migrate --force
   php artisan optimize
   ```

2. **Clear Cache**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

3. **View Logs**
   ```bash
   tail -f /var/www/html/storage/logs/laravel.log
   ```

## Troubleshooting

1. **Nginx Issues**
   ```bash
   nginx -t
   systemctl status nginx
   ```

2. **PHP-FPM Issues**
   ```bash
   systemctl status php8.1-fpm
   ```

3. **Queue Worker Issues**
   ```bash
   supervisorctl status
   tail -f /var/www/html/storage/logs/worker.log
   ```

## Backup

1. **Database Backup**
   ```bash
   mysqldump -u your_username -p your_database > backup.sql
   ```

2. **Application Backup**
   ```bash
   tar -czf app_backup.tar.gz /var/www/html
   ```

## Support

For any issues or questions, please:
1. Check the logs in `/var/www/html/storage/logs`
2. Review the Laravel documentation
3. Contact the development team

## License

This installation script and guide are provided under the MIT License. 