# Docker Setup Guide for BM Application

## Prerequisites

1. Install Docker Desktop for Windows:
   - Download from: https://www.docker.com/products/docker-desktop/
   - Install and start Docker Desktop

2. Install Docker Compose (usually included with Docker Desktop)

## Quick Start

### 1. Start the application
```bash
docker-compose up -d
```

### 2. Run database migrations
```bash
docker-compose exec app php artisan migrate
```

### 3. Generate application key
```bash
docker-compose exec app php artisan key:generate
```

### 4. Build frontend assets (if needed)
```bash
docker-compose exec node npm run build
```

## Access the Application

- **Web Application**: http://localhost:8000
- **PHPMyAdmin** (if added): http://localhost:8080
- **MySQL Database**: localhost:3306
- **Redis**: localhost:6379
- **Node.js Dev Server**: http://localhost:5173

## Docker Commands

### Start services
```bash
docker-compose up -d
```

### Stop services
```bash
docker-compose down
```

### View logs
```bash
docker-compose logs -f
```

### Run artisan commands
```bash
docker-compose exec app php artisan [command]
```

### Run npm commands
```bash
docker-compose exec node npm [command]
```

### SSH into container
```bash
docker-compose exec app bash
```

## Environment Configuration

1. Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

2. Update database configuration in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=bm_app
DB_USERNAME=bm_user
DB_PASSWORD=bm_password
```

3. Generate application key:
```bash
docker-compose exec app php artisan key:generate
```

## Production Deployment

### Build production image
```bash
docker build -t bm-app:production .
```

### Run production container
```bash
docker run -d \
  --name bm-app-production \
  -p 8000:80 \
  -v $(pwd)/.env:/var/www/html/.env \
  bm-app:production
```

## Troubleshooting

### Port already in use
If port 8000 is already in use, change it in `docker-compose.yml`:
```yaml
ports:
  - "8080:80"  # Change 8080 to any available port
```

### Permission issues
If you have file permission issues, run:
```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Database connection issues
Check if MySQL container is running:
```bash
docker-compose logs mysql
```

### Clear cache
```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear
```

## File Structure

```
docker/
├── nginx.conf          # Nginx configuration
├── supervisord.conf    # Process manager
├── php.ini            # PHP configuration
└── mysql/
    └── init.sql       # Database initialization
```

## Services Overview

- **app**: PHP-FPM with Laravel application
- **nginx**: Nginx web server
- **mysql**: MySQL database
- **redis**: Redis cache
- **node**: Node.js with hot reload for development

This setup provides a complete development environment with hot reloading and production-ready configuration.