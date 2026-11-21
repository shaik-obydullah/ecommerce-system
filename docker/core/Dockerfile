# syntax=docker/dockerfile:1.7

ARG PHP_VERSION=8.4

# ---------------------------------------------------------------------------
# base: PHP-FPM with every extension the core needs at runtime.
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-bookworm AS base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    COMPOSER_NO_INTERACTION=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        posix \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/core/php.ini /usr/local/etc/php/conf.d/zz-ecommerce.ini
COPY docker/core/entrypoint.sh /usr/local/bin/core-entrypoint
RUN chmod +x /usr/local/bin/core-entrypoint

ENTRYPOINT ["core-entrypoint"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# dev: application code + dev dependencies. Compose bind-mounts source over it.
# ---------------------------------------------------------------------------
FROM base AS dev

ENV APP_ENV=local

COPY apps/core/composer.json apps/core/composer.lock ./
RUN composer install --prefer-dist --no-scripts --no-autoloader

COPY apps/core/ ./
RUN mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && composer dump-autoload --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && php artisan filament:assets --ansi

# ---------------------------------------------------------------------------
# prod: no dev dependencies, non-root runtime, warmed caches.
# ---------------------------------------------------------------------------
FROM base AS prod

ENV APP_ENV=production

COPY apps/core/composer.json apps/core/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-scripts --no-autoloader

COPY apps/core/ ./
RUN mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && composer dump-autoload --optimize --no-dev --no-scripts \
    && php artisan package:discover --ansi \
    && php artisan filament:assets --ansi \
    && chown -R www-data:www-data bootstrap/cache storage

USER www-data

# ---------------------------------------------------------------------------
# nginx: static front door for the core (api/admin/storage/Blade).
# ---------------------------------------------------------------------------
FROM nginx:1.27-alpine AS nginx

COPY docker/core/nginx.conf /etc/nginx/conf.d/default.conf
COPY apps/core/public /var/www/html/public
