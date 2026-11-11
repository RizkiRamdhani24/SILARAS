
# ==========================================
# STAGE 1: BUILD FRONTEND ASSETS
# ==========================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build


# ==========================================
# STAGE 2: LARAVEL + APACHE
# ==========================================
FROM php:8.2-apache

# Install system dependencies and PHP extensions
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
    \
    # Remove ALL enabled Apache MPM modules
    && rm -f /etc/apache2/mods-enabled/mpm_*.load \
             /etc/apache2/mods-enabled/mpm_*.conf \
    \
    # Enable exactly ONE MPM: prefork
    && a2enmod mpm_prefork \
    && a2enmod rewrite headers \
    \
    # Verify active MPM
    && apache2ctl -M | grep mpm \
    \
    && rm -rf /var/lib/apt/lists/*


# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html


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
# COPY APPLICATION SOURCE
# ==========================================
COPY . .


# Copy built frontend assets
COPY --from=frontend /app/public/build ./public/build


# ==========================================
# APACHE CONFIGURATION
# ==========================================
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/000-default.conf \
    \
    && sed -ri 's/AllowOverride None/AllowOverride All/' \
        /etc/apache2/apache2.conf \
    \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf


# ==========================================
# LARAVEL PERMISSIONS & OPTIMIZATION
# ==========================================
RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    \
    && chown -R www-data:www-data storage bootstrap/cache \
    \
    && composer dump-autoload \
        --no-dev \
        --optimize \
    \
    && php artisan package:discover --ansi


# ==========================================
# RAILWAY PORT
# ==========================================
EXPOSE 10000

CMD ["sh", "-c", "\
    PORT=${PORT:-10000}; \
    sed -ri 's/Listen [0-9]+/Listen '${PORT}'/' /etc/apache2/ports.conf; \
    sed -ri 's/<VirtualHost \\*:[0-9]+>/<VirtualHost *:'${PORT}'>' /etc/apache2/sites-available/000-default.conf; \
    exec apache2-foreground \
"]
