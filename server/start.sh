#!/bin/bash
set -e

echo "Starting Laravel setup..."

# Start PHP-FPM in the background
php-fpm -D

# Wait for Redis (if using Redis)
if [ -n "$REDIS_HOST" ]; then
    echo "Waiting for Redis..."
    timeout=60
    while ! redis-cli -h ${REDIS_HOST} ping > /dev/null 2>&1; do
        timeout=$((timeout - 1))
        if [ $timeout -eq 0 ]; then
            echo "Warning: Redis not available, continuing without it"
            break
        fi
        sleep 1
    done
    if [ $timeout -gt 0 ]; then
        echo "Redis is ready!"
    fi
fi

# Go to the app directory
cd /var/www/html

# Clear and cache Laravel configs
echo "Optimizing Laravel..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Cache configurations for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Laravel optimization complete."

# Start Nginx in the foreground
echo "Starting Nginx..."
nginx -g "daemon off;"