#!/bin/sh
set -e

# First arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

if [ "$1" = 'php-fpm' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
    # Install dependencies if needed
    if [ "$APP_ENV" != 'prod' ]; then
        composer install --prefer-dist --no-progress --no-interaction
    fi

    # Wait for the database to be ready
    until nc -z postgres 5432; do
        echo "Waiting for PostgreSQL to be ready..."
        sleep 1
    done

    # Wait for RabbitMQ to be ready
    until nc -z rabbitmq 5672; do
        echo "Waiting for RabbitMQ to be ready..."
        sleep 1
    done

    # Permissions hack for Symfony cache
    mkdir -p var/cache var/log
    setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var
    setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var

    # Create database if it doesn't exist
    if [ "$APP_ENV" != 'prod' ]; then
        php bin/console doctrine:database:create --if-not-exists --no-interaction
        php bin/console doctrine:migrations:migrate --no-interaction
    fi

    # Clear cache for non-dev environments
    if [ "$APP_ENV" != 'dev' ]; then
        php bin/console cache:clear --no-warmup
        php bin/console cache:warmup
    fi
fi

exec "$@"