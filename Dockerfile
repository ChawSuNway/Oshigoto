# syntax=docker/dockerfile:1

# =============================================================================
# Stage 1 — Build front-end assets (Vite + Tailwind + Alpine)
# Without this, public/build is missing and @vite() throws at runtime.
# =============================================================================
FROM node:20-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build


# =============================================================================
# Stage 2 — Application (PHP 8.2) for Render
# =============================================================================
FROM php:8.2-fpm

# System libraries required by the PHP extensions below.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl ca-certificates \
        libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libicu-dev \
    && update-ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo pdo_mysql mbstring bcmath zip gd intl opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies first for better layer caching (scripts run later,
# once the full app is present, so package discovery works).
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

# Application source + the compiled front-end assets from stage 1.
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev \
    && composer run-script post-autoload-dump || true

# Writable runtime directories.
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Render provides $PORT (defaults to 10000). Config/route/view caches are built
# at startup so Render's environment variables are respected, then the app runs.
EXPOSE 10000
# Startup: cache config/routes/views against the runtime env, always run pending
# migrations, seed only when RUN_SEED=true (set it once for the first deploy),
# then serve on Render's $PORT.
CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force || true; [ \"${RUN_SEED:-false}\" = \"true\" ] && php artisan db:seed --force || true; php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
