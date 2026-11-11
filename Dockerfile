
# ==========================================
# STAGE 1: BUILD VITE ASSETS
# ==========================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build


# ==========================================
# STAGE 2: LARAVEL BACKEND
# ==========================================
FROM php:8.2-apache

WORKDIR /var/www/html

# Install PHP dependencies
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
    && rm -rf /var/lib/apt/lists/*

# Aktifkan modul Apache yang diperlukan.
# Jangan mengubah MPM bawaan image resmi.
RUN a2enmod rewrite headers

# Verifikasi konfigurasi Apache bawaan
RUN apache2ctl -M && apache2ctl -t

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

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

# Copy hasil build Vite
COPY --from=frontend /app/public/build ./public/build

# Apache document root diarahkan ke public Laravel
RUN set -eux; \
    sed -ri 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/000-default.conf; \
    sed -ri 's/AllowOverride None/AllowOverride All/' \
        /etc/apache2/apache2.conf; \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Laravel permissions dan optimasi
RUN set -eux; \
    mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache; \
    chown -R www-data:www-data storage bootstrap/cache; \
    composer dump-autoload --no-dev --optimize; \
    php artisan package:discover --ansi

# Railway port
EXPOSE 8080

# Runtime: gunakan PORT dari Railway
CMD ["sh", "-c", "set -eu; PORT=${PORT:-8080}; sed -i \"s/^Listen 80$/Listen ${PORT}/\" /etc/apache2/ports.conf; sed -i \"s#<VirtualHost \\*:80>#<VirtualHost *:${PORT}>#\" /etc/apache2/sites-available/000-default.conf; echo \"Starting Apache on port ${PORT}\"; apache2ctl -t; exec apache2-foreground"]
