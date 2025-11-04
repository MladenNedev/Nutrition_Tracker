# api/Dockerfile
FROM php:8.3-apache

# PHP extensions
RUN docker-php-ext-install pdo_mysql

# Apache config
RUN a2enmod rewrite

# Copy API source into the web root

WORKDIR /var/www/html
COPY . /var/www/html

# Optional: tighten perms a bit
RUN chown -R www-data:www-data /var/www/html
