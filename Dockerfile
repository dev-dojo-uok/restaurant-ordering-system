FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update \
  && apt-get install -y --no-install-recommends libpq-dev \
  && docker-php-ext-install pdo pdo_pgsql \
  && docker-php-ext-enable pdo_pgsql \
  && sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/000-default.conf \
  && printf "<Directory \"${APACHE_DOCUMENT_ROOT}\">\n    AllowOverride All\n    Require all granted\n</Directory>\n" > /etc/apache2/conf-enabled/app.conf \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html
