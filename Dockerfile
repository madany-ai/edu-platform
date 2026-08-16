# PHP 8.4 — required minimum for Laravel 13 (see PRD Section 6)
FROM php:8.4-fpm-alpine

# System dependencies + PHP extensions Laravel needs
RUN apk add --no-cache \
    ffmpeg \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-client \
    postgresql-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers \
    libjpeg-turbo-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Composer (matches the version bundled with Laravel 13 tooling)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Recommended php.ini settings for an LMS handling file uploads (PDFs, assignment submissions)
RUN { \
    echo 'upload_max_filesize=2G'; \
    echo 'post_max_size=2G'; \
    echo 'memory_limit=512M'; \
    echo 'max_execution_time=120'; \
    echo 'opcache.enable=1'; \
    echo 'opcache.validate_timestamps=1'; \
    echo 'zend.exception_ignore_args=Off'; \
    } > /usr/local/etc/php/conf.d/lms-settings.ini

COPY src/composer.json src/composer.lock* ./
RUN if [ -f composer.json ]; then composer install --no-dev --no-scripts --no-autoloader --prefer-dist; fi

COPY src/ .

RUN if [ -f composer.json ]; then \
    composer dump-autoload --optimize; \
    fi

RUN mkdir -p /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/testing \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/storage/app/public \
    /var/www/html/bootstrap/cache

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Recreate storage symlink — Docker COPY dereferences symlinks, so public/storage
# becomes a stale regular directory. This ensures it points to the actual storage
# volume used in production (storage_data:/var/www/html/storage/app/public).
RUN rm -rf /var/www/html/public/storage && \
    ln -s ../storage/app/public /var/www/html/public/storage

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER www-data
EXPOSE 9000
ENTRYPOINT ["docker-entrypoint.sh"]