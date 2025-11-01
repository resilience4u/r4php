FROM php:8.3-cli
WORKDIR /app

RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip zip \
 && pecl install apcu \
 && docker-php-ext-enable apcu \
 && echo "apc.enable_cli=1" > /usr/local/etc/php/conf.d/apcu.ini \
 && rm -rf /var/lib/apt/lists/*

RUN pecl install pcov \
 && docker-php-ext-enable pcov \
 && echo "pcov.enabled=1" > /usr/local/etc/php/conf.d/pcov.ini \
 && echo "pcov.directory=/app/src" >> /usr/local/etc/php/conf.d/pcov.ini \
 && echo "pcov.exclude=\"~(vendor)~\"" >> /usr/local/etc/php/conf.d/pcov.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY src ./src
COPY examples ./examples
COPY phpunit.xml.dist ./

CMD ["php", "-v"]
