# syntax=docker/dockerfile:1

# =============================================================================
# Stage 1 — Build front-end assets (Vite + Tailwind + Alpine)
# =============================================================================
FROM node:20-alpine AS assets

WORKDIR /app

# Install JS deps first (better layer caching).
COPY package.json package-lock.json ./
RUN npm ci

# Copy the sources Vite needs, then build to public/build.
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build


# =============================================================================
# Stage 2 — Application image (PHP 8.2 + Apache)
# =============================================================================
FROM php:8.2-apache AS app

# --- System libraries required by the PHP extensions below --------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libonig-dev libicu-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mysqli mbstring bcmath zip gd exif pcntl intl opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# --- Opcache tuned for production --------------------------------------------
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini \
    && echo 'upload_max_filesize=25M' > /usr/local/etc/php/conf.d/uploads.ini \
    && echo 'post_max_size=25M' >> /usr/local/etc/php/conf.d/uploads.ini

# --- Apache: serve Laravel's public/ dir and enable pretty URLs ---------------
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN a2enmod rewrite \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# --- Composer -----------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first (cached unless composer files change). Skip scripts
# until the full source is present, since package discovery needs the app.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

# --- Application source -------------------------------------------------------
COPY . .

# Bring in the compiled front-end assets from the build stage.
COPY --from=assets /app/public/build ./public/build

# Finish the composer install now that the whole app is here.
RUN composer dump-autoload --optimize --no-dev \
    && composer run-script post-autoload-dump || true

# --- Permissions --------------------------------------------------------------
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# --- Entrypoint: prep runtime, then start Apache -----------------------------
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 80
ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]
