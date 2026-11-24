#!/usr/bin/env bash
set -e

PORT_TO_USE="${PORT:-10000}"

# Bind Apache to Render's dynamic $PORT
sed -i "s/Listen 80/Listen ${PORT_TO_USE}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT_TO_USE}/g" /etc/apache2/sites-available/000-default.conf

# Cache Laravel configuration, routes, and views for production
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations safely
php artisan migrate --force

exec apache2-foreground
