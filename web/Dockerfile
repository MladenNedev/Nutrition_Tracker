FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql
RUN a2enmod rewrite

# set docroot to /var/www/html/public if you haven't already
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# 👇 THIS is the important part
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . /var/www/html
