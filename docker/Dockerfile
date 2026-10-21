# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1: front-end assets (Webpack Encore)
# ---------------------------------------------------------------------------
FROM node:24-slim AS assets
ARG RUN_TYPE=build
WORKDIR /app/managechart

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY webpack.config.js ./
COPY assets ./assets
COPY public/js ./public/js
RUN npm run ${RUN_TYPE}

# ---------------------------------------------------------------------------
# Stage 2: PHP dependencies (Composer)
# ---------------------------------------------------------------------------
FROM php:8.5-cli AS vendor
ARG APP_ENV=prod
WORKDIR /app/managechart

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev \
    && docker-php-ext-install -j"$(nproc)" intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock symfony.lock ./
RUN if [ "$APP_ENV" = "prod" ]; then NO_DEV="--no-dev"; else NO_DEV=""; fi \
    && composer install --no-interaction --no-scripts --no-autoloader $NO_DEV

COPY . .
RUN if [ "$APP_ENV" = "prod" ]; then NO_DEV="--no-dev"; else NO_DEV=""; fi \
    && composer dump-autoload --no-interaction --no-scripts --optimize --classmap-authoritative $NO_DEV

# ---------------------------------------------------------------------------
# Stage 3: runtime (Apache + PHP)
# ---------------------------------------------------------------------------
FROM php:8.5-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libicu-dev curl \
    && docker-php-ext-install -j"$(nproc)" intl pdo pdo_pgsql pdo_mysql pgsql mysqli \
    && rm -rf /var/lib/apt/lists/*

ENV TZ=Europe/Paris
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/^memory_limit = .*/memory_limit = 512M/' "$PHP_INI_DIR/php.ini" \
    && sed -i "s#^;date.timezone =.*#date.timezone = $TZ#" "$PHP_INI_DIR/php.ini"

# Apache: listen on 8080 (non-root), no 000-default, hardened global conf and vhost.
# No X-Frame-Options / CSP frame-ancestors: charts are embedded in iframes on third-party sites.
RUN a2enmod rewrite headers \
    && a2dissite 000-default \
    && sed -i 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i '/^\s*Listen 443/d' /etc/apache2/ports.conf \
    && cat > /etc/apache2/conf-available/zz-managechart.conf <<'APACHE_GLOBAL'
ServerName localhost
ServerTokens Prod
ServerSignature Off
TraceEnable Off
APACHE_GLOBAL
RUN a2enconf zz-managechart \
    && cat > /etc/apache2/sites-available/managechart.conf <<'APACHE_VHOST'
<VirtualHost *:8080>
    DocumentRoot /app/managechart/public
    DirectoryIndex /index.php

    <Directory /app/managechart/public>
        AllowOverride None
        Require all granted
        FallbackResource /index.php
    </Directory>

    <Directory /app/managechart/public/bundles>
        DirectoryIndex disabled
        FallbackResource disabled
    </Directory>

    Header always set X-Content-Type-Options nosniff
    Header always set Referrer-Policy strict-origin-when-cross-origin

    ErrorLog /dev/stderr
    CustomLog /dev/stdout combined
</VirtualHost>
APACHE_VHOST
RUN a2ensite managechart

WORKDIR /app/managechart

# Code is owned by root (read-only for the web server).
COPY --from=vendor /app/managechart /app/managechart
COPY --from=assets /app/managechart/public/build /app/managechart/public/build

# Only var/ and Apache run/log/lock dirs are writable by www-data.
# iv.txt / key.txt are NOT baked in: they are mounted as volumes.
RUN mkdir -p var/cache var/log Resources/privateKey \
    && mkdir -p /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
    && chown -R www-data:www-data var /var/run/apache2 /var/lock/apache2 /var/log/apache2

USER www-data
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS -o /dev/null http://localhost:8080/ || exit 1

CMD ["apache2-foreground"]
