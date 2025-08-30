#!/bin/sh
set -e

cd /var/www/html

# Ensure vendor is installed
if [ ! -d "vendor" ]; then
  echo ">> Running composer install"
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Copy .env if missing
if [ ! -f ".env" ] && [ -f ".env.example" ]; then
  echo ">> Copying .env from .env.example"
  cp .env.example .env
fi

# Generate key if empty
if ! grep -q "APP_KEY=base64:" .env && [ -z "$APP_KEY" ]; then
  echo ">> Generating APP_KEY"
  php artisan key:generate
elif [ -n "$APP_KEY" ]; then
  sed -i "s|^APP_KEY=.*|APP_KEY=$APP_KEY|g" .env || true
fi

# Optimize
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true

# Run migrations (safe to fail in dev)
php artisan migrate --force || true
php artisan db:seed --force || true

# Run Laravel dev server (for simple setup)
php artisan serve --host=0.0.0.0 --port=8000
