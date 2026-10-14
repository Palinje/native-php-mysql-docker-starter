FROM php:8.4-fpm-alpine

# PDO MySQL is used by the sample app; mysqli is also available for projects
# that prefer the mysqli API. OPcache is enabled for a production-like runtime.
RUN docker-php-ext-install pdo_mysql mysqli opcache

COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html
