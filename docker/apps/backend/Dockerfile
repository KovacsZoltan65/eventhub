# apps/backend Dockerfile
FROM php:8.3-fpm-alpine

# System deps
RUN apk add --no-cache bash git curl libpq-dev oniguruma-dev icu-dev tzdata

# PHP extensions (pgsql, pdo_pgsql, mbstring, intl)
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring intl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Workdir
WORKDIR /var/www/html

# Copy a minimal nginx for reference (we run php artisan serve for simplicity)
# (If you prefer Nginx + PHP-FPM, provide your own nginx service & config.)
COPY docker/apps/backend/entrypoint.sh /docker/entrypoint.sh
RUN chmod +x /docker/entrypoint.sh

# Set env to avoid interactive prompts
ENV COMPOSER_ALLOW_SUPERUSER=1

# Expose Laravel dev server port
EXPOSE 8000
