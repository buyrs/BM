# Local Development & Cloud Production Deployment Guide

## Local Development Setup

For local development, the installer is configured to use SQLite by default, which requires no additional setup:

1. Run the installer at `http://localhost:8000/install`
2. Choose "SQLite (Recommended for Local Development)" as your database type
3. Enter a database filename (e.g., `database.sqlite`)
4. Complete the installation

This approach eliminates the need to install and configure MySQL locally.

## Cloud Production Deployment

For cloud deployment with your MariaDB server, you have two options:

### Option 1: Use the Installer (if you have direct database access)
1. During installation, choose "MySQL (For Production)"
2. Enter your cloud MariaDB credentials:
   - Host: Your MariaDB server hostname/IP
   - Port: 3306 (default MariaDB port)
   - Database: Your production database name
   - Username: Your MariaDB username
   - Password: Your MariaDB password
3. Complete the installation

### Option 2: Environment Variables (Recommended)
Instead of using the installer for production, configure your cloud environment variables:

```
DB_CONNECTION=mysql
DB_HOST=your-mariadb-host
DB_PORT=3306
DB_DATABASE=your-production-database-name
DB_USERNAME=your-mariadb-username
DB_PASSWORD=your-mariadb-password
```

Then run these commands on your cloud environment:
```bash
php artisan migrate --force
php artisan db:seed --force
php artisan key:generate --force
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Benefits of This Approach

1. **Local Development**: Fast setup with SQLite, no database server required
2. **Production Deployment**: Flexible database configuration through environment variables
3. **Security**: Sensitive database credentials are never stored in code repositories
4. **Scalability**: Easy to switch between different database configurations

## Troubleshooting

If you encounter database connection issues:
1. Verify your MariaDB credentials are correct
2. Ensure your cloud MariaDB allows connections from your application server
3. Check that the database exists and the user has proper permissions
4. For SQLite issues, ensure the `database` directory is writable
5. Note that MariaDB is fully compatible with the MySQL driver, so `DB_CONNECTION=mysql` is correct