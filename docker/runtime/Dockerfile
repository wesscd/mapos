FROM php:8.4-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libfreetype6-dev libjpeg62-turbo-dev libpng-dev libzip-dev libicu-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 gd mysqli pdo_mysql mbstring zip intl bcmath opcache exif \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/mapos.ini
WORKDIR /var/www/html
