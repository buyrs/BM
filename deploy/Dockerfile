FROM php:8.2-fpm-alpine

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

# Install Node.js dependencies and build assets
RUN npm ci && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage
RUN chown -R www-data:www-data /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage
RUN chmod -R 775 /var/www/html/bootstrap/cache

# Copy configuration files
COPY deploy/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY deploy/php.ini /usr/local/etc/php/conf.d/custom.ini

# Expose port
EXPOSE 9000

# Start PHP-FPM and supervisor
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]