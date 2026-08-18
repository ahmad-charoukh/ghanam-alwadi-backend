#!/usr/bin/env sh
set -e

php artisan package:discover --ansi
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
php artisan storage:link || true
php artisan config:cache

exec apache2-foreground