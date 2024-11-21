FROM php:8.2-fpm

# Instalace základních balíčků a PHP rozšíření
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    zlib1g-dev \
    unzip \
    git \
    && docker-php-ext-install \
    intl \
    pdo_mysql \
    zip \
    opcache \
    mbstring \
    iconv \
    simplexml \
    dom \
    && docker-php-ext-enable intl opcache

# Nastavení pracovního adresáře
WORKDIR /var/www/html
