FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    tesseract-ocr \
    tesseract-ocr-deu \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        bcmath \
        gd \
        intl \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# The repo is bind-mounted from the host, so it's owned by the host's UID,
# not whatever user runs inside the container — git refuses to touch a
# repository it doesn't consider itself the owner of unless told otherwise.
# Composer's own version-detection shells out to git during `composer
# install`, which is where this first surfaced.
RUN git config --global --add safe.directory /var/www/html

WORKDIR /var/www/html
