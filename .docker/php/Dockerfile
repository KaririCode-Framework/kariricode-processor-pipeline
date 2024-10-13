ARG PHP_VERSION=8.3

FROM php:${PHP_VERSION}-alpine

# Install system dependencies
RUN apk update && apk add --no-cache \
    $PHPIZE_DEPS \
    linux-headers \
    zlib-dev \
    libmemcached-dev \
    cyrus-sasl-dev

RUN pecl install xdebug redis memcached \
    && docker-php-ext-enable xdebug redis memcached

# Copy custom PHP configuration
COPY .docker/php/kariricode-php.ini /usr/local/etc/php/conf.d/

# Instalação do Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apk del --purge $PHPIZE_DEPS && rm -rf /var/cache/apk/*

# Mantém o contêiner ativo sem fazer nada
CMD tail -f /dev/null
