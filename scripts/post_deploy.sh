#!/usr/bin/env bash
set -euo pipefail

cd /var/www/aimexe

export COMPOSER_ALLOW_SUPERUSER=1

# Laravel runtime/cache directories can be removed by rsync --delete when empty.
mkdir -p bootstrap/cache
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chmod -R ug+rwX bootstrap/cache storage

if [ -f composer.json ]; then
  composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader
fi

if [ -f artisan ]; then
  php artisan optimize:clear
  php artisan optimize
  php artisan queue:restart || true
fi
