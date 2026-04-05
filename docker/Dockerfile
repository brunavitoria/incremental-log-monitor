FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        mbstring \
        exif \
        pcntl \
        xml \
        dom \
        gd \
        zip \
    && pecl install xdebug-3.3.2 || true \
    && docker-php-ext-enable xdebug || true \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock artisan ./
RUN composer install --prefer-dist --no-progress --no-interaction --no-scripts

COPY . .

RUN composer dump-autoload --optimize && \
    php artisan package:discover --ansi

EXPOSE 9000
CMD ["php-fpm"]
