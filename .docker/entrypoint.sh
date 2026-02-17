#!/bin/sh
set -e

composer install --no-interaction

vendor/bin/sake dev/build flush=1

exec frankenphp run --config /etc/caddy/Caddyfile
