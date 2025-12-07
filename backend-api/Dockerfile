# syntax=docker/dockerfile:1
FROM php:8.3-fpm

# Устанавливаем зависимости
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libicu-dev libzip-dev zip \
    && docker-php-ext-install intl pdo_pgsql opcache zip \
    && docker-php-ext-enable opcache

# Устанавливаем Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Копируем код приложения
WORKDIR /var/www/html
COPY . .

# Настройки прав (для dev)
RUN chown -R www-data:www-data /var/www/html/var

CMD ["php-fpm"]
