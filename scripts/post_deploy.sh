#!/usr/bin/env bash
set -euo pipefail

cd /var/www/aimexe

export COMPOSER_ALLOW_SUPERUSER=1

if [ -f composer.json ]; then
  composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader
fi

if [ -f artisan ]; then
  php artisan optimize:clear
  php artisan optimize
  php artisan queue:restart || true
fi
