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
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip curl

# Install MongoDB and Redis PHP extensions
RUN pecl install mongodb redis \
    && docker-php-ext-enable mongodb redis

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
RUN chmod +x /start.sh

# Expose port 8000
EXPOSE 8000

# Run the startup script
CMD ["/start.sh"]