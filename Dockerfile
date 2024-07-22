FROM php:8.1.1-fpm

RUN apt-get update && apt-get install -y git
RUN apt install sqlite3

RUN pecl install xdebug\
    && docker-php-ext-enable xdebug


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY ./docker/xdebug.ini "${PHP_INI_DIR}/conf.d"

WORKDIR /var/www