FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    zip \
    libicu-dev \
    && docker-php-ext-install zip intl pdo pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN cp .env.example .env

RUN php artisan key:generate --force

RUN php artisan config:cache

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=10000