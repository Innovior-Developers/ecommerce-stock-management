FROM php:8.3-fpm

# Set environment variables to prevent interactive prompts
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies including SSL, networking tools, and redis-cli
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
    && rm -rf /var/lib/apt/lists/* \
    ca-certificates

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip curl

# Install MongoDB and Redis PHP extensions
RUN pecl install mongodb redis \
    && docker-php-ext-enable mongodb redis

# ✅ ADD: Configure PHP for large file uploads
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_file_uploads = 10" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY ./ecommerce-stock-management /var/www/html

# Install Composer dependencies
# This ensures aws-sdk-php is installed inside the container
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Set correct permissions for storage and bootstrap cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy Nginx config and startup script
COPY ./nginx.conf /etc/nginx/nginx.conf
COPY ./start.sh /start.sh
RUN dos2unix /start.sh
RUN chmod +x /start.sh

# Expose port 8000
EXPOSE 8000

# Run the startup script
CMD ["/start.sh"]