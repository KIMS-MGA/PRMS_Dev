#!/bin/sh
set -e

# Create storage symlink if missing (safe to re-run)
php artisan storage:link --force 2>/dev/null || true

# Regenerate package cache — clears any stale host-baked cache from COPY
rm -f /var/www/bootstrap/cache/packages.php /var/www/bootstrap/cache/services.php
php artisan package:discover --ansi 2>/dev/null || true

# Fix storage permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

exec php-fpm
