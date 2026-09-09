# syntax=docker/dockerfile:1

# -----------------------------
# 1) Frontend build (Vite/Tailwind)
# -----------------------------
FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# -----------------------------
# 2) Laravel runtime
# -----------------------------
FROM php:8.4-cli-bookworm AS app

ENV APP_ENV=production \
    APP_DEBUG=false \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        git \
        unzip \
        libicu-dev \
        libonig-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --optimize-autoloader \
    && mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chmod +x docker/render-start.sh

EXPOSE 10000

CMD ["./docker/render-start.sh"]
