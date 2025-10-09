#!/bin/bash
# filepath: server/start.sh
set -e

echo "🚀 Starting Laravel Application..."

# Start PHP-FPM in the background
php-fpm -D

# Wait for Redis
echo "⏳ Waiting for Redis..."
timeout=60
while ! redis-cli -h redis ping > /dev/null 2>&1; do
  timeout=$((timeout - 1))
  if [ $timeout -eq 0 ]; then
    echo "❌ Error: Timed out waiting for Redis."
    exit 1
  fi
  sleep 1
done
echo "✅ Redis is ready!"

# Go to app directory
cd /var/www/html

# ✅ FIX: Ensure .env exists (symlink should already be created in Dockerfile)
if [ ! -f .env ]; then
    echo "⚠️  Warning: .env not found, copying from .env.docker"
    if [ -f .env.docker ]; then
        cp .env.docker .env
    else
        echo "❌ Error: .env.docker not found!"
        exit 1
    fi
fi

# ✅ FIX: Verify .env is readable
if [ ! -r .env ]; then
    echo "❌ Error: .env is not readable"
    exit 1
fi

echo "✅ .env file verified"

# Clear all Laravel caches
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Rebuild caches
echo "🔄 Rebuilding Laravel caches..."
php artisan config:cache
php artisan route:cache

# ✅ FIX: Generate APP_KEY if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
    echo "✅ Application key generated"
else
    echo "✅ Application key already exists"
fi

# ✅ FIX: Verify JWT_SECRET
if grep -q "JWT_SECRET=" .env && [ -n "$(grep "^JWT_SECRET=" .env | cut -d '=' -f2)" ]; then
    echo "✅ JWT secret configured"
else
    echo "⚠️  Warning: JWT_SECRET not set in .env"
    echo "Run: php artisan jwt:secret --force"
fi

echo "✅ Laravel setup complete!"

# Start Nginx in foreground
echo "🌐 Starting Nginx..."
nginx -g "daemon off;"