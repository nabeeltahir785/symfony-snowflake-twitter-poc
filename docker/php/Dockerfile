FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    acl \
    fcgi \
    file \
    gettext \
    git \
    gnu-libiconv \
    postgresql-dev

# Install PHP extensions
RUN set -eux; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        zlib-dev \
        gmp-dev \
    ; \
    \
    docker-php-ext-configure zip; \
    docker-php-ext-install -j$(nproc) \
        intl \
        pdo_pgsql \
        zip \
        gmp \
    ; \
    pecl install \
        apcu \
        amqp \
    ; \
    pecl clear-cache; \
    docker-php-ext-enable \
        apcu \
        amqp \
        opcache \
        gmp \
    ; \
    \
    runDeps="$( \
        scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
            | tr ',' '\n' \
            | sort -u \
            | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
    )"; \
    apk add --no-cache --virtual .api-phpexts-rundeps $runDeps; \
    \
    apk del .build-deps

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create a non-root user to run the application
RUN addgroup -g 1000 symfony && \
    adduser -u 1000 -G symfony -h /home/symfony -s /bin/sh -D symfony

# Set working directory
WORKDIR /var/www/html

# Copy the application
COPY --chown=symfony:symfony . /var/www/html

# Use the non-root user
USER symfony

# Set up Composer environment
ENV COMPOSER_ALLOW_SUPERUSER=0 \
    COMPOSER_HOME=/home/symfony/.composer

# Set up the entrypoint
COPY --chmod=755 docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

# Configure security headers
ENV PHP_MEMORY_LIMIT=512M \
    PHP_UPLOAD_MAX_FILESIZE=20M \
    PHP_POST_MAX_SIZE=20M \
    PHP_EXPOSE=Off \
    PHP_DISPLAY_ERRORS=Off

# Configure OPcache for production
ENV PHP_OPCACHE_ENABLE=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=256