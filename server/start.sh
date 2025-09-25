#// filepath: server/start.sh
#!/bin/bash
set -e

# Start PHP-FPM in the background
php-fpm -D

# Wait for Redis
echo "Waiting for Redis..."
timeout=60
while ! redis-cli -h redis ping > /dev/null 2>&1; do
  timeout=$((timeout - 1))
  if [ $timeout -eq 0 ]; then
    echo "Error: Timed out waiting for Redis."
    exit 1
  fi
  sleep 1
done
echo "Redis is ready!"

# Go to the app directory
cd /var/www/html

# Run Laravel setup
echo "Running Laravel setup..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
echo "Laravel setup complete."

# Start Nginx in the foreground
echo "Starting Nginx..."
nginx -g "daemon off;"