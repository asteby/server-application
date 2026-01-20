#!/bin/sh
set -e

# Link storage (safe to run multiple times)
echo "🔗 Linking storage..."
php artisan storage:link

# Wait for database connection
echo "⏳ Waiting for database..."
# Simple wait loop or rely on Docker depends_on (usually enough for modern setups, but wait-for-it is safer)

# Run migrations
echo "📦 Running migrations..."
php artisan migrate --force

# Clear/Cache config
echo "🧹 Optimizing..."
php artisan optimize:clear
# php artisan config:cache # Optional: be careful with env vars if not consistent
# php artisan route:cache

# Execute the passed command (usually starts php-fpm or apache)
echo "🚀 Starting application..."
exec "$@"
