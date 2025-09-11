FROM php:8.3-fpm

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
    && update-ca-certificates \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install MongoDB and Redis extensions
RUN pecl install mongodb redis \
    && docker-php-ext-enable mongodb redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first
COPY ./ecommerce-stock-management/composer.json ./

# Install dependencies without scripts first
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs

# Copy the rest of the application
COPY ./ecommerce-stock-management/ /var/www/html

# Generate autoloader (without running post-install scripts)
RUN composer dump-autoload --optimize --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy nginx config
COPY ./nginx.conf /etc/nginx/nginx.conf

# Expose port 8000
EXPOSE 8000

# Copy and set start script permissions
COPY ./start.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]