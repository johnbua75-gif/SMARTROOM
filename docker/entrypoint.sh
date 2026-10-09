#!/bin/sh
set -eu

port="${PORT:-10000}"
sed -ri "s/^Listen 80$/Listen ${port}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

php artisan route:clear
php artisan config:clear
php artisan config:cache
php artisan migrate --force

exec apache2-foreground
