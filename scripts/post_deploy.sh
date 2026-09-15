#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

DEPLOY_PARENT="$(dirname "$APP_DIR")"
if [ "$(basename "$DEPLOY_PARENT")" = "releases" ]; then
  APP_BASE="$(dirname "$DEPLOY_PARENT")"
else
  APP_BASE="$DEPLOY_PARENT"
fi

SHARED_DIR="$APP_BASE/shared"
SHARED_STORAGE="$SHARED_DIR/storage"
SHARED_UPLOADS="$SHARED_DIR/user-uploads"

export COMPOSER_ALLOW_SUPERUSER=1

# Laravel runtime/cache directories can be removed by rsync --delete when empty.
mkdir -p bootstrap/cache
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chmod -R ug+rwX bootstrap/cache || true

# Keep runtime state and uploaded assets outside release directories. A release
# swap must not make Laravel caches or company branding files disappear.
if [ -d "$SHARED_DIR" ]; then
  mkdir -p "$SHARED_STORAGE" "$SHARED_UPLOADS"

  if [ ! -L "$APP_DIR/storage" ]; then
    cp -a "$APP_DIR/storage/." "$SHARED_STORAGE/" 2>/dev/null || true
    rm -rf "$APP_DIR/storage"
    ln -s "$SHARED_STORAGE" "$APP_DIR/storage"
  fi

  mkdir -p "$APP_DIR/public"
  if [ ! -L "$APP_DIR/public/user-uploads" ]; then
    if [ -d "$APP_DIR/public/user-uploads" ]; then
      cp -a "$APP_DIR/public/user-uploads/." "$SHARED_UPLOADS/" 2>/dev/null || true
      rm -rf "$APP_DIR/public/user-uploads"
    fi
    ln -s "$SHARED_UPLOADS" "$APP_DIR/public/user-uploads"
  fi

  if id www-data >/dev/null 2>&1; then
    chown -R www-data:www-data "$SHARED_STORAGE" "$SHARED_UPLOADS" || true
  fi
  chmod -R ug+rwX "$SHARED_STORAGE" "$SHARED_UPLOADS" || true
else
  mkdir -p public/user-uploads
  chmod -R ug+rwX public/user-uploads || true
fi

if [ -f composer.json ]; then
  composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader
fi

if [ -f artisan ]; then
  php artisan optimize:clear
  php artisan optimize
  php artisan queue:restart || true
fi
