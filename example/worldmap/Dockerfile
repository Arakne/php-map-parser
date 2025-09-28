FROM php:8.4-cli AS worldmap_example_php

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update
RUN apt-get install -y $PHPIZE_DEPS zlib1g-dev libpng-dev libjpeg-dev libwebp-dev libxpm-dev libgd-dev librsvg2-bin inkscape libzip-dev

RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-xpm
RUN docker-php-ext-install opcache pcntl sockets gd zip pdo pdo_mysql

# Install imagemagick
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions imagick

WORKDIR /srv

COPY php.ini "/usr/local/etc/php/conf.d/application.ini"
COPY src ./src
COPY composer.json ./
COPY index.php ./

RUN composer install --no-dev --optimize-autoloader

EXPOSE 80
CMD ["php", "index.php", "start"]
