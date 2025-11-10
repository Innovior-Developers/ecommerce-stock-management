FROM php:8.3-fpm

# Set environment variables
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    pkg-config \
    zip \
    unzip \
    nginx \
    libzip-dev \
    ca-certificates \
    openssl \
    libcurl4-openssl-dev \
    redis-tools \
    dos2unix \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip curl

# ✅ FIX: Install MongoDB extension 2.3.0 (latest stable, compatible with mongodb/mongodb 2.1.1)
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# ✅ FIX: Verify MongoDB extension is enabled
RUN php -m | grep mongodb

# Configure PHP for large file uploads
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_file_uploads = 10" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# ✅ FIX 1: Copy .env.docker FIRST (before app files)
COPY ./.env.docker /var/www/html/.env.docker

# ✅ FIX 2: Then copy the application
COPY ./ecommerce-stock-management /var/www/html

# ✅ FIX 3: Create symlink from .env.docker to .env
RUN ln -sf /var/www/html/.env.docker /var/www/html/.env

# Install ALL dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Run composer dump-autoload
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# ✅ FIX 4: Cache Laravel configuration (now .env exists)
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Copy Nginx config
COPY ./nginx.conf /etc/nginx/nginx.conf

# Copy and prepare startup script
COPY ./start.sh /start.sh
RUN dos2unix /start.sh && chmod +x /start.sh

# Expose port
EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:8000/api/health || exit 1

# Start
CMD ["/start.sh"]