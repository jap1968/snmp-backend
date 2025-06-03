FROM ubuntu:24.04
LABEL maintainer=jap1968
ARG DEBIAN_FRONTEND=noninteractive
RUN apt update && apt install -y \
    composer \
    php-mbstring \
    php-snmp \
    php-curl \
    apache2 \
    libapache2-mod-php \
    && apt-get clean

RUN mkdir -p \
    /var/local/www/public/ \
    /var/local/www/src/ \
    /var/local/www/vendor/ \
    /var/local/www/logs/
RUN chown www-data:www-data /var/local/www/logs/

COPY composer.json /var/local/www/
WORKDIR /var/local/www/
RUN composer install

COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

COPY ./public/ /var/local/www/public/
COPY ./src/ /var/local/www/src/

CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
