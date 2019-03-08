#!/usr/bin/env bash

#installing tools
apt-get update && apt-get install -y openssh-server mysql-client net-tools curl wget git zip unzip mysql-client ca-certificates libcurl4-openssl-dev
docker-php-ext-install pdo pdo_mysql curl sockets mysqli

# XDebug
yes | pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

# Composer and Composer based cli tools
wget https://getcomposer.org/installer \
    && php installer \
    && mv composer.phar /usr/local/bin/composer \
    && echo 'export PATH=~/.composer/vendor/bin:$PATH' >> /root/.bashrc

#pulling packages
composer update
