# syntax=docker/dockerfile:1.7

# ---------- 1. Dependencias PHP ----------
FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
# Si todavía no hay composer.lock versionado se resuelve en la construcción.
RUN if [ -f composer.lock ]; then \
        composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs; \
    else \
        composer update --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs; \
    fi
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# ---------- 2. Assets (Vite + Tailwind 3; el preset de WireUI vive en vendor/) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY --from=vendor /app/vendor ./vendor
COPY resources ./resources
COPY public ./public
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build

# ---------- 3. Aplicación (PHP-FPM) ----------
FROM php:8.3-fpm-alpine AS app

RUN apk add --no-cache icu-libs libzip libpng libjpeg-turbo freetype libpq \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip gd bcmath opcache pcntl \
    && apk del .build-deps

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-xnova.ini
COPY docker/entrypoint.sh /usr/local/bin/xnova-entrypoint

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

RUN chmod +x /usr/local/bin/xnova-entrypoint \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
EXPOSE 9000
ENTRYPOINT ["xnova-entrypoint"]
CMD ["php-fpm"]

# ---------- 4. Servidor web (Nginx con los archivos públicos) ----------
FROM nginx:1.27-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
