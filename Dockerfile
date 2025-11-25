ARG DOCKER_UID=1000
ARG DOCKER_GID=1000

FROM php:8.3-cli

RUN apt update \
    && apt install -y \
        libicu-dev \
        libonig-dev \
        libcurl4-openssl-dev \
        ca-certificates \
        gnupg \
        libzip-dev \
        zip \
        git \
        unzip

RUN pecl install -a apcu-5.1.22 \
    && docker-php-ext-install \
        bcmath \
        intl \
        curl \
        opcache \
        mbstring \
        zip \
    && docker-php-ext-enable apcu \
    && apt clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/000-docker.ini

ENV COMPOSER_HOME=/tmp

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer
COPY ./docker/composer/php.ini /usr/local/etc/php/conf.d/custom.ini

RUN pecl install \
        xdebug \
    && docker-php-ext-enable \
        xdebug

RUN mkdir -p /srv/app && chown $DOCKER_UID:$DOCKER_GID /srv/app

WORKDIR /srv/app
USER $DOCKER_UID

