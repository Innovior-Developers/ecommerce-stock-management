FROM php:8.3-fpm

# Install system dependencies including SSL certificates
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
    openssl

# Update SSL certificates
RUN update-ca-certificates

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (including zip)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install MongoDB PHP extension with SSL support
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Install Redis PHP extension
RUN pecl install redis && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY ./ecommerce-stock-management/ /var/www/html

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy nginx config
COPY ./nginx.conf /etc/nginx/nginx.conf

# Expose port 8000
EXPOSE 8000

# Start script
COPY ./start.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]