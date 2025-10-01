# Bail Mobilite Platform Administration Guide

This guide provides comprehensive information for system administrators responsible for maintaining and configuring the Bail Mobilite Platform.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [User Management](#user-management)
5. [Role-Based Access Control](#role-based-access-control)
6. [Database Management](#database-management)
7. [Caching and Performance](#caching-and-performance)
8. [Security](#security)
9. [Monitoring and Logging](#monitoring-and-logging)
10. [Backup and Recovery](#backup-and-recovery)
11. [Updates and Maintenance](#updates-and-maintenance)
12. [Troubleshooting](#troubleshooting)

## System Requirements

### Server Requirements

**Minimum Specifications:**
- CPU: 2 cores
- RAM: 4 GB
- Storage: 50 GB SSD
- Bandwidth: 100 Mbps

**Recommended Specifications:**
- CPU: 4+ cores
- RAM: 8+ GB
- Storage: 100+ GB SSD
- Bandwidth: 1 Gbps

### Software Requirements

**Operating System:**
- Ubuntu 20.04+ LTS
- CentOS 8+
- Debian 10+

**Web Server:**
- Nginx 1.18+
- Apache 2.4+ (with mod_rewrite)

**Database:**
- MySQL 8.0+
- MariaDB 10.4+
- PostgreSQL 12+

**PHP:**
- Version 8.1+
- Extensions: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, Intl, GD, Zip

**Additional Services:**
- Redis 6.0+ (for caching and sessions)
- Node.js 16+ (for asset compilation)
- Composer 2.0+ (PHP dependency manager)

## Installation

### Fresh Installation

1. **Clone Repository:**
```bash
git clone https://github.com/your-organization/bail-mobilite.git
cd bail-mobilite
```

2. **Install Dependencies:**
```bash
composer install --no-dev --optimize-autoloader
npm install --production
npm run build
```

3. **Configure Environment:**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database Setup:**
```bash
# Configure database in .env file
php artisan migrate --force
php artisan db:seed
```

5. **Storage Link:**
```bash
php artisan storage:link
```

6. **Optimize Application:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Docker Installation

1. **Build Images:**
```bash
docker-compose build
```

2. **Start Services:**
```bash
docker-compose up -d
```

3. **Run Migrations:**
```bash
docker-compose exec app php artisan migrate --force
```

## Configuration

### Environment Variables

Key configuration variables in `.env` file:

```env
# Application
APP_NAME="Bail Mobilite"
APP_ENV=production
APP_KEY=base64:your-app-key
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bail_mobilite
DB_USERNAME=db_user
DB_PASSWORD=secure_password

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Queue
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Application Configuration

Key configuration files in `config/` directory:

**auth.php:** Authentication settings
**cache.php:** Cache driver configuration
**database.php:** Database connection settings
**filesystems.php:** Storage and file system configuration
**logging.php:** Log channel settings
**mail.php:** Email configuration
**queue.php:** Queue system settings
**services.php:** Third-party service configurations

### Performance Configuration

**Caching:**
- Configure Redis for optimal performance
- Enable opcode caching (OPcache)
- Configure HTTP caching headers

**Database:**
- Optimize database indexes
- Configure connection pooling
- Enable query caching

**HTTP Server:**
- Enable Gzip compression
- Configure static file caching
- Set appropriate cache headers

## User Management

### Creating Administrative Users

```bash
php artisan make:user --admin
```

Follow the prompts to create an administrative user with full system access.

### Role Management

**View Existing Roles:**
```bash
php artisan permission:show-roles
```

**Create New Role:**
```bash
php artisan permission:create-role "role_name"
```

**Assign Permissions to Role:**
```bash
php artisan permission:assign-role-permissions "role_name" "permission1,permission2"
```

### Permission Management

**View Permissions:**
```bash
php artisan permission:show-permissions
```

**Create Custom Permission:**
```bash
php artisan permission:create-permission "custom_permission"
```

**Assign Permission Directly to User:**
```bash
php artisan permission:assign-user-permission user_id "permission_name"
```

## Role-Based Access Control

### Default Roles

**Administrator (admin):**
- Full system access
- User management
- Mission oversight
- System configuration

**Operations Staff (ops):**
- Mission creation and management
- Property management
- Checker assignment
- Report generation

**Property Checker (checker):**
- Checklist completion
- Photo upload
- Signature capture
- Mission updates

### Customizing Roles

**Extending Role Capabilities:**
1. Create new permissions in the database
2. Assign permissions to existing roles
3. Or create entirely new roles with specific permissions

**Permission Inheritance:**
Higher-level roles automatically inherit permissions from lower-level roles:
- Admin inherits Ops permissions
- Ops inherits Checker permissions

### Permission Checking in Code

**Controller Level:**
```php
public function __construct()
{
    $this->middleware('can:manage users')->only(['create', 'store', 'edit', 'update', 'destroy']);
}
```

**View Level:**
```blade
@can('manage users')
    <a href="{{ route('admin.users.create') }}">Create User</a>
@endcan
```

**Model Level:**
```php
if (Auth::user()->can('edit', $user)) {
    // Allow user editing
}
```

## Database Management

### Database Maintenance

**Optimizing Tables:**
```bash
php artisan db:optimize
```

**Checking Database Health:**
```bash
php artisan db:check-health
```

**Database Statistics:**
```bash
php artisan db:stats
```

### Backing Up Database

**Manual Backup:**
```bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

**Automated Backup Script:**
```bash
#!/bin/bash
# backup-db.sh
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > /backups/db_backup_$DATE.sql
gzip /backups/db_backup_$DATE.sql
```

### Database Replication

**Master-Slave Setup:**
1. Configure master database for replication
2. Set up slave database servers
3. Configure Laravel to use read/write connections

**Load Balancing:**
Configure multiple database connections in `config/database.php`:
```php
'connections' => [
    'mysql-write' => [...],
    'mysql-read' => [...],
],
```

## Caching and Performance

### Cache Configuration

**Redis Configuration:**
Ensure Redis is properly configured in `config/database.php`:
```php
'redis' => [
    'client' => 'predis',
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => 0,
    ],
],
```

### Cache Management Commands

**Clear All Caches:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

**Clear Specific Cache:**
```bash
php artisan cache:forget key_name
```

**Monitor Cache Usage:**
```bash
php artisan cache:monitor
```

### Performance Optimization

**Database Query Optimization:**
- Use eager loading to prevent N+1 queries
- Add appropriate database indexes
- Use query caching for frequently accessed data

**Asset Optimization:**
- Enable asset compression in `webpack.mix.js`
- Use CDN for static assets
- Implement HTTP/2 for faster asset loading

**Code-Level Optimizations:**
- Use database indexes appropriately
- Implement lazy loading where beneficial
- Cache expensive computations
- Use database connection pooling

## Security

### Security Best Practices

**Application Security:**
- Keep all dependencies updated
- Use HTTPS exclusively in production
- Implement proper input validation
- Sanitize all user inputs
- Use prepared statements for database queries

**Authentication Security:**
- Enforce strong password policies
- Implement two-factor authentication
- Use secure password hashing (bcrypt)
- Implement account lockout after failed attempts
- Regularly rotate session keys

**Data Security:**
- Encrypt sensitive data at rest
- Use secure transport protocols (TLS)
- Implement proper access controls
- Regularly audit user permissions
- Backup data with encryption

### Security Monitoring

**Vulnerability Scanning:**
Regularly scan for known vulnerabilities:
```bash
# Using OWASP ZAP
docker run -t owasp/zap2docker-stable zap-baseline.py -t https://yourdomain.com

# Using Nuclei
nuclei -u https://yourdomain.com
```

**Security Auditing:**
```bash
php artisan security:audit
```

### Compliance

**GDPR Compliance:**
- Implement data protection by design
- Provide data portability features
- Enable right to erasure functionality
- Maintain detailed audit logs
- Implement privacy by default

**PCI DSS Compliance:**
If processing payments:
- Use PCI-compliant payment processors
- Never store sensitive payment data
- Implement network security controls
- Regularly test security systems
- Maintain information security policy

## Monitoring and Logging

### Application Monitoring

**Laravel Telescope:**
Enable for detailed application insights:
```bash
php artisan telescope:install
php artisan migrate
```

**Horizon Monitoring:**
For queue monitoring:
```bash
php artisan horizon:install
```

### Log Management

**Log Configuration:**
Configure logging in `config/logging.php`:
```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'slack'],
    ],
],
```

**Centralized Logging:**
Consider using centralized logging solutions:
- ELK Stack (Elasticsearch, Logstash, Kibana)
- Graylog
- Splunk
- AWS CloudWatch

### Performance Monitoring

**APM Solutions:**
Integrate with Application Performance Monitoring:
- New Relic
- Datadog
- AppDynamics
- Prometheus + Grafana

**Custom Metrics:**
Implement custom performance metrics:
```php
// Record custom business metrics
StatsD::timing('mission.creation.time', $creationTime);
StatsD::increment('checklist.submitted.count');
```

## Backup and Recovery

### Backup Strategy

**3-2-1 Backup Rule:**
- 3 copies of data
- 2 different media types
- 1 offsite copy

**Backup Types:**
1. **Full Backups:** Complete system snapshot
2. **Incremental Backups:** Changes since last backup
3. **Differential Backups:** Changes since last full backup

### Backup Automation

**Daily Backup Script:**
```bash
#!/bin/bash
# daily-backup.sh

# Database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > /backups/db_$(date +%Y%m%d).sql.gz

# File backup
tar -czf /backups/files_$(date +%Y%m%d).tar.gz -C /var/www/bail-mobilite storage

# Clean old backups (keep 30 days)
find /backups -name "*.gz" -mtime +30 -delete
```

**Weekly Full Backup:**
```bash
#!/bin/bash
# weekly-full-backup.sh

# Full system backup
tar -czf /backups/full_$(date +%Y%m%d).tar.gz /var/www/bail-mobilite
```

### Disaster Recovery

**Recovery Plan:**
1. **Assessment:** Determine extent of damage
2. **Isolation:** Prevent further data loss
3. **Restoration:** Restore from most recent backup
4. **Validation:** Verify system integrity
5. **Communication:** Notify stakeholders

**Recovery Time Objectives (RTO):**
- Critical systems: < 4 hours
- Important systems: < 24 hours
- Secondary systems: < 72 hours

**Recovery Point Objectives (RPO):**
- Transactional data: < 1 hour
- Operational data: < 24 hours
- Archival data: < 1 week

## Updates and Maintenance

### System Updates

**Scheduled Maintenance Windows:**
- Weekly maintenance windows
- Quarterly major updates
- Annual platform upgrades

**Update Process:**
1. **Testing:** Apply updates to staging environment
2. **Validation:** Test all critical functionality
3. **Deployment:** Apply to production during maintenance window
4. **Monitoring:** Observe system behavior post-update

**Emergency Updates:**
For critical security patches:
1. Immediate assessment of risk
2. Expedited testing in isolated environment
3. Coordinated deployment with rollback plan
4. Post-deployment monitoring

### Dependency Management

**Composer Updates:**
```bash
composer update --dry-run  # Preview updates
composer update             # Apply updates
```

**NPM Updates:**
```bash
npm outdated  # Check for outdated packages
npm update    # Update packages
```

**Security Updates:**
Regularly check for security advisories:
```bash
composer audit
npm audit
```

## Troubleshooting

### Common Issues

**Database Connection Issues:**
```bash
# Check database connectivity
mysql -u username -p -h hostname database_name

# Verify database configuration
php artisan tinker
>>> DB::connection()->getPdo();
```

**Cache Issues:**
```bash
# Clear all caches
php artisan cache:clear-all

# Check Redis connectivity
redis-cli ping
```

**Permission Issues:**
```bash
# Check file permissions
ls -la storage/
ls -la bootstrap/cache/

# Fix permissions
sudo chown -R www-data:www-data storage/ bootstrap/cache/
sudo chmod -R 775 storage/ bootstrap/cache/
```

### Diagnostic Commands

**System Health Check:**
```bash
php artisan health:check
```

**Database Diagnostics:**
```bash
php artisan db:diagnose
```

**Cache Diagnostics:**
```bash
php artisan cache:diagnose
```

**Queue Diagnostics:**
```bash
php artisan queue:diagnose
```

### Log Analysis

**Common Log Locations:**
- Application logs: `storage/logs/laravel.log`
- Web server logs: `/var/log/nginx/` or `/var/log/apache2/`
- Database logs: `/var/log/mysql/` or configured database log path
- System logs: `/var/log/syslog`

**Log Analysis Tools:**
```bash
# Search for specific errors
grep "ERROR" storage/logs/laravel.log

# Monitor logs in real-time
tail -f storage/logs/laravel.log

# Count error occurrences
grep -c "ERROR" storage/logs/laravel.log
```

---

*This administration guide should be reviewed regularly and updated to reflect changes in the platform. Last updated: {{ date('Y-m-d') }}*

For support inquiries, contact: admin@bailmobilite.com