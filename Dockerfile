FROM php:8.5-fpm

# Системные зависимости
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libssl-dev \
    libzip-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# PHP расширения
# openssl и mbstring уже встроены в PHP — не требуют установки
RUN docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    zip

# Redis через pecl (php-redis)
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
