FROM node:22-bookworm AS node

FROM php:8.4-apache-bookworm AS php-base

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype6-dev \
    libicu-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libpng-dev \
    libzip-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

FROM php-base AS build

COPY --from=node /usr/local/ /usr/local/
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --classmap-authoritative \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --no-scripts

COPY . .

RUN composer dump-autoload \
    --no-dev \
    --classmap-authoritative \
    --no-interaction \
    --no-scripts \
    && php artisan package:discover --ansi \
    && corepack pnpm@10.28.0 install --frozen-lockfile \
    && corepack pnpm@10.28.0 run build \
    && rm -rf node_modules

FROM php-base AS production

COPY . .
COPY --from=build /var/www/html/vendor ./vendor
COPY --from=build /var/www/html/public/build ./public/build
COPY --from=build /var/www/html/bootstrap/cache ./bootstrap/cache
COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf

RUN sed -i 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf \
    && a2enmod rewrite headers \
    && mkdir -p \
        storage/app/private \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

CMD ["apache2-foreground"]
