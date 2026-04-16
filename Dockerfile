FROM php:8.4-fpm-alpine

ARG user
ARG uid

RUN apk update && apk add --no-cache \
    $PHPIZE_DEPS \
    curl \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    libjpeg-turbo-dev \
    icu-dev \
    oniguruma-dev \
    zip \
    unzip \
    shadow \
    nodejs \
    npm

RUN pecl install redis && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip intl

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

WORKDIR /var/www
USER $user
