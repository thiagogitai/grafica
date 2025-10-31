#!/usr/bin/env bash
set -e

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env || true
fi

# Generate key if missing
php artisan key:generate --force || true

# Optimize config/routes
php artisan config:cache || true
php artisan route:cache || true

# Run migrations and seed
php artisan migrate --force || true
php artisan db:seed --force || true

# Storage symlink
php artisan storage:link || true

chown -R www-data:www-data storage bootstrap/cache || true

exec "$@"

