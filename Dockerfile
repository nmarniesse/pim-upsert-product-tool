ARG DOCKER_UID=1000

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
        unzip \
    && echo "deb http://apt.postgresql.org/pub/repos/apt/ bookworm-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && curl "https://www.postgresql.org/media/keys/ACCC4CF8.asc" | apt-key add - \
    && apt-get update && apt-get install -y --no-install-recommends \
            libpq-dev \
    && rm -rf /var/lib/apt/lists/* \
    && pecl install -a apcu-5.1.22 \
    && docker-php-ext-install \
        bcmath \
        intl \
        curl \
        opcache mbstring \
        zip \
    && docker-php-ext-enable apcu \
    && apt clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && mv /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/rewrite.load

COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/000-docker.ini

ENV COMPOSER_HOME=/tmp

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer
COPY ./docker/composer/php.ini /usr/local/etc/php/conf.d/custom.ini

RUN pecl install \
        xdebug \
    && docker-php-ext-enable \
        xdebug

ARG DOCKER_UID
ARG USER=${DOCKER_UID}

RUN mkdir -p /srv/app && chown $USER /srv/app

WORKDIR /srv/app
USER $USER

