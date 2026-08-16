#!/bin/sh
set -e

echo "==> Rebuilding Laravel caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting PHP-FPM..."
exec php-fpm
