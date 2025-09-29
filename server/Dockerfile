FROM php:8.3-fpm
ENV DEBIAN_FRONTEND=noninteractive

# System + build deps + DNS tools
RUN apt-get update && apt-get install -y \
    git curl zip unzip ca-certificates \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev \
    nginx supervisor redis-tools \
    dnsutils iputils-ping net-tools \
    && rm -rf /var/lib/apt/lists/*

# Configure GD with jpeg/freetype
RUN docker-php-ext-configure gd --with-jpeg --with-freetype

# Install PHP extensions (curl already compiled in base image)
RUN docker-php-ext-install -j$(nproc) gd pdo_mysql mbstring exif pcntl bcmath zip

# MongoDB & Redis
RUN pecl install mongodb redis && docker-php-ext-enable mongodb redis

# PHP ini tweaks
RUN { \
    echo "upload_max_filesize=50M"; \
    echo "post_max_size=100M"; \
    echo "max_file_uploads=20"; \
    echo "memory_limit=256M"; \
    echo "max_execution_time=300"; \
    echo "max_input_time=300"; \
    echo "display_errors=On"; \
    echo "error_reporting=E_ALL"; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY ./ecommerce-stock-management /var/www/html

# (Skip config:cache here; done at container start so runtime env vars are used)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

COPY nginx.conf /etc/nginx/nginx.conf
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8000
CMD ["/start.sh"]