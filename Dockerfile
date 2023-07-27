ARG ALPINE_VERSION=3.16

ARG NODE_VERSION=18

FROM node:$NODE_VERSION-alpine AS node
FROM php:8.1-cli-alpine$ALPINE_VERSION AS php-cli

RUN apk add php yarn make perl git --no-cache

WORKDIR /app

RUN apk add --no-cache libstdc++
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

COPY package.json yarn.lock webpack.config.js .eslintrc.js ./
RUN yarn install
RUN rm -rf node_modules

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json ./
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN composer install --prefer-install=source --no-progress --no-suggest --no-interaction --no-plugins --ignore-platform-reqs

COPY dev/docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]

CMD ["php"]
