# syntax=docker/dockerfile:1

# ---------- Base: PHP + ekstensi + composer ----------
FROM php:8.3-cli-alpine AS php-base
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql pdo_sqlite redis pcntl bcmath zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# ---------- Stage: TEST ----------
FROM php-base AS test
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --no-interaction \
 && cp .env.example .env \
 && php artisan key:generate --no-interaction \
 && php artisan test --compact

# ---------- Stage: vendor produksi ----------
FROM php-base AS vendor-prod
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist

# ---------- Stage: build aset Vite/Tailwind ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm install --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------- Stage: RUNTIME ----------
FROM php-base AS runtime
ENV APP_ENV=production \
    APP_DEBUG=false \
    PHP_CLI_SERVER_WORKERS=4
COPY --from=vendor-prod /var/www/html/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && composer dump-autoload --no-dev --optimize --no-scripts --no-interaction \
 && php artisan package:discover --ansi \
 && chown -R www-data:www-data storage bootstrap/cache
USER www-data
EXPOSE 8000
HEALTHCHECK --interval=15s --timeout=5s --start-period=60s --retries=5 \
  CMD wget -q --spider http://127.0.0.1:8000/up || exit 1
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
