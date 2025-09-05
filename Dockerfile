# Multi-stage build for production
FROM php:8.2-fpm-alpine AS backend

# Install system dependencies
RUN apk update && apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev \
    icu-dev \
    postgresql-dev \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install \
    pdo_mysql \
    pdo_pgsql \
    zip \
    gd \
    xml \
    mbstring \
    intl \
    opcache \
    exif

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create application directory
RUN mkdir -p /var/www/html
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage
RUN chown -R www-data:www-data /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage
RUN chmod -R 775 /var/www/html/bootstrap/cache

# Copy configuration files
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Frontend build stage
FROM node:22-alpine AS frontend

WORKDIR /var/www/html

# Copy package files
COPY package*.json ./
COPY vite.config.js ./

# Install Node.js dependencies with legacy peer deps to resolve conflicts
RUN npm ci --legacy-peer-deps

# Copy source files
COPY resources/ ./resources/
COPY tailwind.config.js ./

# Build assets
RUN npm run build

# Final production image
FROM backend

# Copy built assets from frontend stage
COPY --from=frontend /var/www/html/public/build/ /var/www/html/public/build/

# Expose port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:9000/health || exit 1

# Start PHP-FPM and supervisor
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]