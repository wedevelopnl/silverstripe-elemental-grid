#!/bin/sh
set -e

composer install --no-interaction

# FrankenPHP ships a default phpinfo() index.php. Replace it with the proper
# SilverStripe bootstrap after composer install makes the recipe available.
cp -f vendor/silverstripe/recipe-core/public/index.php /app/public/index.php

# vendor-plugin uses realpath() to resolve library paths, which follows the
# symlink from vendor/wedevelopnl/silverstripe-elemental-grid → /module.
# Because /module is outside /app, getRelativePath() produces a broken path
# and the exposed resources are never created. Create them manually.
_res=/app/public/_resources/vendor/wedevelopnl/silverstripe-elemental-grid
mkdir -p "$_res/client"
[ -d /module/client/dist ] && ln -sfn /module/client/dist "$_res/client/dist"
[ -d /module/client/images ] && ln -sfn /module/client/images "$_res/client/images"
[ -d /module/client/lang ] && ln -sfn /module/client/lang "$_res/client/lang"
[ -d /module/lang ] && ln -sfn /module/lang "$_res/lang"

vendor/bin/sake dev/build flush=1

exec frankenphp run --config /etc/caddy/Caddyfile
