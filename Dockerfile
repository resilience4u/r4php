FROM php:8.3-cli
WORKDIR /app

RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip zip \
 && pecl install apcu \
 && docker-php-ext-enable apcu \
 && echo "apc.enable_cli=1" > /usr/local/etc/php/conf.d/apcu.ini \
 && rm -rf /var/lib/apt/lists/*

COPY src ./src
COPY examples ./examples
COPY phpunit.xml.dist ./

CMD ["php", "-v"]
