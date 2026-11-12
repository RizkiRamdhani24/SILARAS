
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


# ==========================================
# INSTALL PHP DEPENDENCIES
# ==========================================
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


# ==========================================
# VALIDATE APACHE MPM
# ==========================================
# The official PHP Apache image configures prefork; validate loaded modules instead of editing symlinks.
RUN set -eux; \
    a2enmod rewrite headers; \
    APACHE_MODULES="$(apache2ctl -M)"; \
    printf '%s\n' "$APACHE_MODULES"; \
    MPM_COUNT="$(printf '%s\n' "$APACHE_MODULES" | awk '/mpm_(event|worker|prefork)_module/ { count++ } END { print count+0 }')"; \
    PREFORK_COUNT="$(printf '%s\n' "$APACHE_MODULES" | awk '/mpm_prefork_module/ { count++ } END { print count+0 }')"; \
    test "$MPM_COUNT" -eq 1; \
    test "$PREFORK_COUNT" -eq 1; \
    apache2ctl -t


# ==========================================
# INSTALL COMPOSER
# ==========================================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# ==========================================
# INSTALL LARAVEL DEPENDENCIES
# ==========================================
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts


# ==========================================
# COPY LARAVEL SOURCE
# ==========================================
COPY . .


# ==========================================
# COPY VITE BUILD
# ==========================================
COPY --from=frontend /app/public/build ./public/build


# ==========================================
# APACHE DOCUMENT ROOT
# ==========================================
RUN set -eux; \
    sed -ri 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/000-default.conf; \
    sed -ri 's/AllowOverride None/AllowOverride All/' \
        /etc/apache2/apache2.conf; \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf


# ==========================================
# LARAVEL PERMISSIONS & OPTIMIZATION
# ==========================================
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


# ==========================================
# RAILWAY PORT
# ==========================================
EXPOSE 8080


# ==========================================
# RUNTIME CONFIGURATION
# ==========================================
CMD ["sh", "-c", "set -eu; \
    PORT=${PORT:-8080}; \
    case \"${PORT}\" in ''|*[!0-9]*) echo \"Invalid PORT: ${PORT}\" >&2; exit 1 ;; esac; \
    test \"${PORT}\" -ge 1; \
    test \"${PORT}\" -le 65535; \
    echo \"Starting Apache on port ${PORT}\"; \
    sed -i -E \"s/^Listen [0-9]+$/Listen ${PORT}/\" /etc/apache2/ports.conf; \
    sed -i -E \"s#<VirtualHost \\*:([0-9]+)>#<VirtualHost *:${PORT}>#\" /etc/apache2/sites-available/000-default.conf; \
    APACHE_MODULES=$(apache2ctl -M); \
    printf '%s\\n' \"${APACHE_MODULES}\"; \
    MPM_COUNT=$(printf '%s\\n' \"${APACHE_MODULES}\" | awk '/mpm_(event|worker|prefork)_module/ { count++ } END { print count+0 }'); \
    PREFORK_COUNT=$(printf '%s\\n' \"${APACHE_MODULES}\" | awk '/mpm_prefork_module/ { count++ } END { print count+0 }'); \
    echo \"Loaded Apache MPM count: ${MPM_COUNT}\"; \
    test \"${MPM_COUNT}\" -eq 1; \
    test \"${PREFORK_COUNT}\" -eq 1; \
    apache2ctl -t; \
    exec apache2-foreground"]
