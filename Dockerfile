# Stage 1: Build frontend assets
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build


# Stage 2: Laravel application
FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    bcmath \
    zip \
    opcache \
    && rm -f /etc/apache2/mods-enabled/mpm_event.* \
             /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install Laravel dependencies
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Copy Laravel source
COPY . .

# Copy production frontend assets
COPY --from=frontend /app/public/build ./public/build

# Configure Apache to serve Laravel public directory
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' \
    /etc/apache2/sites-available/000-default.conf \
    && sed -ri '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
    /etc/apache2/apache2.conf

# Laravel permissions and package discovery
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && composer dump-autoload --no-dev --optimize \
    && php artisan package:discover --ansi

EXPOSE 10000

# Pakai port dari platform (Railway/Render) kalau ada, default 10000
CMD ["sh", "-c", "PORT=${PORT:-10000}; sed -ri \"s/Listen [0-9]+/Listen ${PORT}/\" /etc/apache2/ports.conf; sed -ri \"s/<VirtualHost \\*:[0-9]+>/<VirtualHost *:${PORT}>/\" /etc/apache2/sites-available/000-default.conf; exec apache2-foreground"]