#!/usr/bin/env bash
set -e

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

git config --global --add safe.directory /var/www/html || true

mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwx storage bootstrap/cache || true

until mysqladmin ping -h"${DB_HOST:-mysql}" -u"${DB_USERNAME:-laravel}" -p"${DB_PASSWORD:-secret}" --silent; do
    sleep 2
done

composer install --no-interaction --prefer-dist

if [ ! -f package-lock.json ] || [ ! -d node_modules ] || [ ! -x node_modules/.bin/vite ]; then
    npm install
fi

npm run build

php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan filament:assets
php artisan storage:link || true

exec php-fpm
