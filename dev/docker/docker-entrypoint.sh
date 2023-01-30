#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- php "$@"
fi

if [ "$1" = 'php' ]; then
    composer install --prefer-install=source --no-progress --no-suggest --no-interaction --no-plugins --ignore-platform-reqs
    cd /app/vendor/silverstripe/admin && yarn install
    cd /app && yarn install
    echo "Container is ready!"
fi

exec docker-php-entrypoint "$@"
