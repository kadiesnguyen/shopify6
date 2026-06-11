#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
  echo "[entrypoint] Creating .env from .env.example..."
  cp .env.example .env
fi

# Docker MySQL service overrides (env vars may be empty in .env)
export DB_CONNECTION="${DB_CONNECTION:-mysql}"
export DB_HOST="${DB_HOST:-mysql}"
export DB_PORT="${DB_PORT:-3306}"
export DB_DATABASE="${DB_DATABASE:-shopefy}"
export DB_USERNAME="${DB_USERNAME:-shopefy}"
export DB_PASSWORD="${DB_PASSWORD:-shopefy}"

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
  if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo "[entrypoint] Generating APP_KEY..."
    php artisan key:generate --force --no-interaction
  fi
fi

echo "[entrypoint] Ensuring database is ready..."
php artisan db:ensure-ready

echo "[entrypoint] Ensuring public storage link..."
php artisan storage:link 2>/dev/null || true

echo "[entrypoint] Starting development server..."
exec php artisan serve --host=0.0.0.0 --port=8000
