FROM php:8.4-fpm

# System packages needed to build the PHP extensions this app requires
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip zip \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libonig-dev libzip-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mbstring bcmath gd zip intl exif pcntl \
    # composer.json requires ext-redis -> phpredis (not predis)
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer (copied from the official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Dev convenience: run the FPM worker pool as root so it can always write to the
# Windows/OneDrive bind mount (storage/logs, compiled views) regardless of
# whether files were created by a root-run `artisan` exec. Do NOT do this in
# production — use www-data there.
RUN printf '[www]\nuser = root\ngroup = root\n' > /usr/local/etc/php-fpm.d/zz-root.conf

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm", "--allow-to-run-as-root"]
