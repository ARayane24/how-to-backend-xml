FROM php:8.2-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git unzip zip curl libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql

CMD ["php-fpm"]
