FROM php:8.4-cli

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y --no-install-recommends unzip git

RUN install-php-extensions sockets pcntl ev

WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

ENV DATABASE_URL="root:secret@mysql/app"
ENV X_LISTEN="0.0.0.0:8080"

EXPOSE 8080

CMD ["php", "public/index.php"]
