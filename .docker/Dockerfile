FROM dunglas/frankenphp:php8.3

RUN install-php-extensions \
    intl \
    gd \
    mysqli \
    pdo_mysql \
    zip \
    bcmath \
    exif \
    pcov

RUN echo 'pcov.directory=/module/src' > /usr/local/etc/php/conf.d/99-pcov.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY app/composer.json /app/composer.json

# SilverStripe requires Page and PageController classes in the project.
# These are generated here (not stored in the repo) to avoid duplicate
# class detection when the module is symlinked into vendor/.
# SilverStripe discovers modules by looking for _config/ or _config.php.
# Without this, /app/src/ is not scanned and Page/PageController are invisible
# to the class manifest (which breaks temp database creation in tests).
RUN mkdir -p /app/src /app/_config \
    && printf '<?php\n\nuse SilverStripe\\CMS\\Model\\SiteTree;\n\nclass Page extends SiteTree\n{\n}\n' > /app/src/Page.php \
    && printf '<?php\n\nuse SilverStripe\\CMS\\Controllers\\ContentController;\n\nclass PageController extends ContentController\n{\n}\n' > /app/src/PageController.php

COPY app/_config/ /app/_config/
COPY app/templates/ /app/templates/
COPY app/infection.json5 /app/infection.json5
COPY app/phpunit.xml.dist /app/phpunit.xml.dist
COPY app/phpstan.neon.dist /app/phpstan.neon.dist

COPY Caddyfile /etc/caddy/Caddyfile
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
