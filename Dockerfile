FROM joomla:5.2-php8.2-apache

RUN a2enmod rewrite \
 && apt-get update \
 && apt-get install -y --no-install-recommends git unzip \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.* /workspace/
WORKDIR /var/www/html
