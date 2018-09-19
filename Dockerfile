FROM composer:1.7.2

WORKDIR /app

# Grab the composer.* files first so we can cache this layer when
# the dependencies haven't changed
COPY composer.json /app/composer.json
RUN composer install

COPY . /app/

CMD vendor/bin/phpunit
