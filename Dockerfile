FROM php:8.1-apache

LABEL author="Nickson Jeanmerson (nickson.jeanmerson@gmail.com)"

ENV ACCEPT_EULA='Y' \
  LANG='en_US.UTF-8' \
  LANGUAGE='en_US:en' \
  LC_ALL='en_US.UTF-8' \
  TZ='America/Sao_Paulo'

# Installing Required Dependencies
RUN \
  apt-get update && \
  apt-get upgrade -y && \
  apt-get install -y --no-install-recommends --fix-missing \
  apt-utils \
  build-essential \
  autoconf automake \
  curl \
  tar \
  zip \
  git \
  unzip \  
  wget \
  gnupg gnupg2 \
  locales \
  g++ gcc \
  freetds-dev \
  apt-transport-https \ 
  make cmake \
  uuid-dev \
  default-jdk \
  pdftk \
  pkg-config \
  libenchant-2-dev \
  libsqlite3-0 libsqlite3-dev sqlite3 \
  libicu-dev zlib1g-dev \
  libtool \
  libbz2-dev \
  libtidy-dev \
  libpspell-dev \
  libsnmp-dev \
  libmemcached-dev \
  libz-dev \
  libpq-dev \
  libuv1-dev \
  libssl-dev libcurl4-openssl-dev openssl \
  libmcrypt-dev \
  libsodium-dev \
  libaio-dev \
  libaio1 \
  libzip-dev \
  libyaml-dev \
  libgmp-dev \
  libldap2-dev \
  libonig-dev \
  librdkafka-dev \
  libmsgpack-dev \
  libfbclient2 libib-util firebird-dev firebird3.0-server firebird3.0-common firebird3.0-common-doc firebird3.0-utils firebird3.0-doc \
  libc-client-dev libkrb5-dev \
  libxml2-dev libxslt-dev libjpeg-dev libpng-dev libwebp-dev libxpm-dev libfreetype6-dev libjpeg62-turbo-dev \
  libmagickwand-dev imagemagick \
  librabbitmq-dev \
  libpcre3-dev mlocate \
  libpython3-dev libpython3.11 libpython3.11-dev python3-dev python3-pkg-resources python3-setuptools python3-wheel python3.11-dev python-setuptools-doc \
  && apt-get clean -y \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
  && rm /var/log/lastlog /var/log/faillog

# Set locale to utf-8
RUN \
  echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && locale-gen

# Install zlib and fix config file
RUN \
  docker-php-ext-install zlib; exit 0 \
  && cp /usr/src/php/ext/zlib/config0.m4 /usr/src/php/ext/zlib/config.m4 \
  && docker-php-ext-install zlib

# Install ldap
RUN \
  docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
  && docker-php-ext-install ldap \
  && docker-php-ext-enable ldap

# Install imap
RUN \
  docker-php-ext-configure imap --with-kerberos --with-imap-ssl  \
  && docker-php-ext-install imap \
  && docker-php-ext-enable imap  

# Install gd
RUN \
  docker-php-ext-configure gd \
  --prefix=/usr \
  --with-png \
  --with-jpeg \
  --with-webp \
  --with-xpm \
  --with-freetype \
  --enable-gd-native-ttf; \
  docker-php-ext-install gd

# Install general extensions
RUN \
  docker-php-ext-install bcmath bz2 calendar ctype dba dom fileinfo exif ftp gettext gmp intl mbstring snmp filter xml xsl opcache pcntl pspell tidy zip phar posix session shmop simplexml soap sockets sodium sysvmsg sysvsem sysvshm iconv ffi enchant \
  && docker-php-ext-enable bcmath bz2 calendar ctype dba dom fileinfo exif ftp gettext gmp intl mbstring snmp filter xml xsl opcache pcntl pspell tidy zip phar posix session shmop simplexml soap sockets sodium sysvmsg sysvsem sysvshm iconv ffi enchant 

# Install pecl extensions
RUN \
  pecl install xdebug yaml pcov mcrypt ds imagick igbinary memcached apcu uuid openswoole rdkafka msgpack \
  && docker-php-ext-enable xdebug yaml pcov mcrypt ds imagick igbinary memcached apcu uuid openswoole rdkafka msgpack 

# Install swoole -- Duplicate with openswoole, choose one them
# RUN \
#   pecl channel-update https://pecl.php.net/channel.xml \
#   && pecl install swoole \
#   && docker-php-ext-enable swoole \
#   && pecl clear-cache \
#   && rm -rf /tmp/* /var/tmp/*

# Install XMLRPC
RUN \
  pecl install channel://pecl.php.net/xmlrpc-1.0.0RC3 xmlrpc \
  && docker-php-ext-enable xmlrpc

# Install GRPC -- Too slow to install
# RUN \
#   pecl install grpc \
#   && docker-php-ext-enable grpc

# Install Protobuf
RUN \
  pecl install protobuf \
  && docker-php-ext-enable protobuf

# Install amqp for RabbitMQ
RUN \
  pecl install amqp \
  && docker-php-ext-enable amqp   

# Install redis
RUN \
  pecl install redis \
  && docker-php-ext-enable redis

# Install mongodb
RUN \
  pecl install mongodb \
  && docker-php-ext-enable mongodb

# Install pdo, pdo_dblib and pdo_sqlite
RUN \ 
  docker-php-ext-install pdo pdo_dblib pdo_sqlite

# Install pdo_firebird
RUN \
  docker-php-ext-install pdo_firebird \
  && docker-php-ext-enable pdo_firebird

# Install mysqli and pdo_mysql
RUN \
  docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd \
  && docker-php-ext-install pdo_mysql \
  && docker-php-ext-enable pdo_mysql \
  && docker-php-ext-configure mysqli --with-mysqli=mysqlnd \
  && docker-php-ext-install mysqli \
  && docker-php-ext-enable mysqli

# Install pgsql and pdo_pgsql
RUN \
  docker-php-ext-configure pdo_pgsql --with-pdo-pgsql=pgsql \
  && docker-php-ext-install pdo_pgsql \
  && docker-php-ext-enable pdo_pgsql \
  && docker-php-ext-configure pgsql --with-pgsql=pgsql \
  && docker-php-ext-install pgsql \
  && docker-php-ext-enable pgsql

# Install prerequisites for the sqlsrv and pdo_sqlsrv PHP extensions.
RUN \
  curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
  && curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list \
  && apt-get update \
  && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 unixodbc-dev \
  && rm -rf /var/lib/apt/lists/*

# Install sqlsrv & pdo_sqlsrv
RUN \
  pecl install sqlsrv pdo_sqlsrv \
  && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Install odbc and pdo_odbc
RUN \
  apt-get --allow-releaseinfo-change update \
  && docker-php-source extract \
  && docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr \
  && docker-php-ext-install pdo_odbc \
  && cd /usr/src/php/ext/odbc \
  && phpize \
  && sed -ri 's@^ *test +"\$PHP_.*" *= *"no" *&& *PHP_.*=yes *$@#&@g' configure \
  && docker-php-ext-configure odbc --with-unixODBC=shared,/usr \
  && docker-php-ext-install odbc

# Download OCI Instant Client
ADD https://download.oracle.com/otn_software/linux/instantclient/211000/instantclient-basic-linux.x64-21.1.0.0.0.zip /tmp/
ADD https://download.oracle.com/otn_software/linux/instantclient/211000/instantclient-sdk-linux.x64-21.1.0.0.0.zip /tmp/
ADD https://download.oracle.com/otn_software/linux/instantclient/211000/instantclient-sqlplus-linux.x64-21.1.0.0.0.zip /tmp/

# Unzip OCI Instant Client
RUN \
  unzip /tmp/instantclient-basic-linux.x64-*.zip -d /usr/local/ \
  && unzip /tmp/instantclient-sdk-linux.x64-*.zip -d /usr/local/ \
  && unzip /tmp/instantclient-sqlplus-linux.x64-*.zip -d /usr/local/

# Install Instant Client
RUN \
  ln -s /usr/local/instantclient_*_1 /usr/local/instantclient \
  && ln -s /usr/local/instantclient/sqlplus /usr/bin/sqlplus 

# Install oci8 & pdo_oci
RUN \
  docker-php-ext-configure oci8 --with-oci8=instantclient,/usr/local/instantclient \
  && docker-php-ext-install oci8 \
  && docker-php-ext-enable oci8 \
  && docker-php-ext-configure pdo_oci --with-pdo-oci=instantclient,/usr/local/instantclient \
  && docker-php-ext-install pdo_oci \
  && docker-php-ext-enable pdo_oci \
  && echo /usr/local/instantclient/ > /etc/ld.so.conf.d/oracle-insantclient.conf \
  && ldconfig

# Copy project structure, folders and files
ADD ./assets/ /var/www/html/assets/
ADD ./build/ /var/www/html/build/
ADD ./readme/ /var/www/html/readme/
ADD ./resources/ /var/www/html/resources/
ADD ./samples/ /var/www/html/samples/
ADD ./src/ /var/www/html/src/
ADD ./tests/ /var/www/html/tests/
ADD ./composer.json /var/www/html/composer.json
ADD ./phpcs.xml /var/www/html/phpcs.xml
ADD ./phpmd.xml /var/www/html/phpmd.xml
ADD ./phpstan.neon /var/www/html/phpstan.neon
ADD ./phpunit.xml /var/www/html/phpunit.xml
ADD ./grumphp.yml /var/www/html/grumphp.yml
ADD ./config.php /var/www/html/config.php

# PHPInfo for Test PHP Libs
COPY ./phpinfo.php /var/www/html/phpinfo.php

# Test Database Connections
COPY ./db-test.php /var/www/html/db-test.php
COPY ./odbc.php /var/www/html/odbc.php

# Copy environment variables
COPY ./.env.docker /var/www/html/.env

# Copy .htaccess for hidden files in directory list
COPY ./.htaccess /var/www/html/.htaccess

# Copy apache settings
COPY ./docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./docker/my-site.conf /etc/apache2/sites-available/my-site.conf

# PHP composer
RUN \
  curl -sS https://getcomposer.org/installer | php --  --install-dir=/usr/bin --filename=composer

# Apache Configurations, Mod Rewrite
RUN \
  ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/rewrite.load

# Write environment variables
RUN \
  echo 'SetEnv MYSQL_HOST ${MYSQL_HOST}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv MYSQL_PORT ${MYSQL_PORT}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv MYSQL_DATABASE ${MYSQL_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv MYSQL_USER ${MYSQL_USER}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv MYSQL_PASSWORD ${MYSQL_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv MYSQL_CHARSET ${MYSQL_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv POSTGRESQL_HOST ${POSTGRESQL_HOST}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv POSTGRESQL_PORT ${POSTGRESQL_PORT}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv POSTGRESQL_DATABASE ${POSTGRESQL_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv POSTGRESQL_USER ${POSTGRESQL_USER}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv POSTGRESQL_PASSWORD ${POSTGRESQL_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv POSTGRESQL_CHARSET ${POSTGRESQL_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv SQLSERVER_HOST ${SQLSERVER_HOST}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv SQLSERVER_PORT ${SQLSERVER_PORT}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv SQLSERVER_DATABASE ${SQLSERVER_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv SQLSERVER_USER ${SQLSERVER_USER}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv SQLSERVER_PASSWORD ${SQLSERVER_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv SQLSERVER_CHARSET ${SQLSERVER_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv ORACLE_HOST ${ORACLE_HOST}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv ORACLE_PORT ${ORACLE_PORT}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv ORACLE_DATABASE ${ORACLE_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv ORACLE_USER ${ORACLE_USER}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv ORACLE_PASSWORD ${ORACLE_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv ORACLE_CHARSET ${ORACLE_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv FIREBIRD_HOST ${FIREBIRD_HOST}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv FIREBIRD_PORT ${FIREBIRD_PORT}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv FIREBIRD_DATABASE ${FIREBIRD_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv FIREBIRD_USER ${FIREBIRD_USER}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv FIREBIRD_PASSWORD ${FIREBIRD_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv FIREBIRD_CHARSET ${FIREBIRD_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv SQLITE_DATABASE ${SQLITE_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv SQLITE_DATABASE_MEMORY ${SQLITE_DATABASE_MEMORY}' >> /etc/apache2/conf-enabled/environment.conf \
  && echo 'SetEnv SQLITE_CHARSET ${SQLITE_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf

WORKDIR /var/www/html
RUN chmod -R 775 /var/www/html
RUN chown -R www-data:www-data /var/www/html
RUN chgrp -R www-data /var/www/html
RUN find /var/www/html -type d -exec chmod g+rx {} +
RUN find /var/www/html -type f -exec chmod g+r {} +

# Run apache and composer install
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf && \
  a2enmod headers && \
  a2enmod rewrite && \
  a2dissite 000-default && \
  a2ensite my-site && \
  service apache2 stop && \
  service apache2 start && \
  composer install -n --ignore-platform-reqs