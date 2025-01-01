# Declara os argumentos enviados pelo docker-compose.yml
ARG PHP_VERSION

# Usa uma imagem do PHP baseado em Apache
FROM php:${PHP_VERSION:-8.3}-apache

# Redeclara os argumentos enviados pelo docker-compose.yml
ARG PHP_VERSION
ARG PHP_PORT

# Tag do Autor
LABEL author="Nickson Jeanmerson (nickson.jeanmerson@gmail.com)"

# Echo para verificar os valores
RUN \
  echo "PHP_VERSION=${PHP_VERSION}, PHP_PORT=${PHP_PORT}"

# Definindo variáveis de ambiente
ENV \
  ACCEPT_EULA='Y' \
  LANGUAGE='en_US:en' \
  LC_CTYPE=C.UTF-8 \
  LC_ALL=C.UTF-8 \
  LANG=C.UTF-8 \
  TZ='America/Sao_Paulo'

# Atualizar o sistema e instalar dependências
RUN \
  apt-get update && apt-get install -y \
  libpq-dev \
  libsqlite3-dev \
  unixodbc-dev \
  libyaml-dev \
  default-libmysqlclient-dev \
  libmcrypt-dev \
  firebird-dev \
  freetds-dev \
  freetds-bin \
  tdsodbc \
  odbc-postgresql \
  odbcinst \
  odbcinst1debian2 \
  libodbc1 \
  unixodbc \
  wget \
  unzip \
  libxml2-dev \
  libxslt-dev \
  libaio1 && \
  apt-get clean -y && \
  rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
  rm /var/log/lastlog /var/log/faillog

# Install general extensions
RUN \
  docker-php-ext-install simplexml iconv && \
  docker-php-ext-enable simplexml iconv

# Install zlib and fix config file
RUN \
  docker-php-ext-install zlib; exit 0 && \
  cp /usr/src/php/ext/zlib/config0.m4 /usr/src/php/ext/zlib/config.m4 && \
  docker-php-ext-install zlib

# Install pecl extensions
RUN \
  if [ "$PHP_VERSION" = "7.3" ] || [ "$PHP_VERSION" = "7.4" ] || [ "$PHP_VERSION" = "8.0" ]; then \
    ( curl -sSLf https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - || echo 'return 1' ) | sh -s \
    xdebug yaml pcov mcrypt; \
  elif [ "$PHP_VERSION" = "8.1" ] || [ "$PHP_VERSION" = "8.2" ] || [ "$PHP_VERSION" = "8.3" ]; then \
    pecl install xdebug yaml pcov mcrypt && \
    docker-php-ext-enable xdebug yaml pcov mcrypt; \
  else \
    echo "Unsupported PHP version: ${PHP_VERSION}"; \
    exit 1; \
  fi

# Install interbase extension only for PHP 7.3
RUN \
  if [ "$PHP_VERSION" = "7.3" ]; then \
    ( curl -sSLf https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - || echo 'return 1' ) | sh -s \
    interbase; \
  fi

# Install pdo and pdo_sqlite
RUN \
  docker-php-ext-install pdo pdo_sqlite

# Install pdo_dblib
RUN \
  if [ "$PHP_VERSION" = "7.3" ] || [ "$PHP_VERSION" = "7.4" ] || [ "$PHP_VERSION" = "8.0" ]; then \
    ( curl -sSLf https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - || echo 'return 1' ) | sh -s \
    pdo_dblib; \
  elif [ "$PHP_VERSION" = "8.1" ] || [ "$PHP_VERSION" = "8.2" ] || [ "$PHP_VERSION" = "8.3" ]; then \
    docker-php-ext-install pdo_dblib; \
  else \
    echo "Unsupported PHP version: ${PHP_VERSION}"; \
    exit 1; \
  fi

# Install pdo_firebird
RUN \
  docker-php-ext-install pdo_firebird && \
  docker-php-ext-enable pdo_firebird

# Install mysqli and pdo_mysql
RUN \
  docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd && \
  docker-php-ext-install pdo_mysql && \
  docker-php-ext-enable pdo_mysql && \
  docker-php-ext-configure mysqli --with-mysqli=mysqlnd && \
  docker-php-ext-install mysqli && \
  docker-php-ext-enable mysqli

# Install pgsql and pdo_pgsql
RUN \
  docker-php-ext-install pdo_pgsql pgsql

# Install odbc and pdo_odbc
RUN \
  apt-get --allow-releaseinfo-change update && \
  apt-get install -y unixodbc-dev --force-yes && \
  docker-php-source extract && \
  docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr && \
  docker-php-ext-install pdo_odbc && \
  cd /usr/src/php/ext/odbc && \
  phpize && \
  sed -ri 's@^ *test +"\$PHP_.*" *= *"no" *&& *PHP_.*=yes *$@#&@g' configure && \
  docker-php-ext-configure odbc --with-unixODBC=shared,/usr && \
  docker-php-ext-install odbc

# Download and Install sqlsrv and pdo_sqlsrv
RUN \
  if [ "$PHP_VERSION" = "7.3" ] || [ "$PHP_VERSION" = "7.4" ] || [ "$PHP_VERSION" = "8.0" ]; then \
    ( curl -sSLf https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - || echo 'return 1' ) | sh -s \
    sqlsrv pdo_sqlsrv; \
  elif [ "$PHP_VERSION" = "8.1" ] || [ "$PHP_VERSION" = "8.2" ] || [ "$PHP_VERSION" = "8.3" ]; then \
    pecl install sqlsrv pdo_sqlsrv && \
    docker-php-ext-enable sqlsrv pdo_sqlsrv; \
  else \
    echo "Unsupported PHP version: ${PHP_VERSION}"; \
    exit 1; \
  fi

# Download and Install MSSQL Connector for ODBC and MDBTools for Access
RUN \
  apt-get update && \
  apt-get install -y gnupg2 && \
  curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - && \
  curl https://packages.microsoft.com/config/ubuntu/20.04/prod.list > /etc/apt/sources.list.d/mssql-release.list && \
  apt-get update && \
  apt-get -y --no-install-recommends install msodbcsql17 odbc-mdbtools && \
  apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Download and Unzip OCI Instant Client
RUN \
  cd /tmp/ && wget https://download.oracle.com/otn_software/linux/instantclient/216000/instantclient-basic-linux.x64-21.6.0.0.0dbru.zip && \
  wget https://download.oracle.com/otn_software/linux/instantclient/216000/instantclient-sdk-linux.x64-21.6.0.0.0dbru.zip && \
  unzip '*.zip' && \
  rm *.zip

# Configure oci8 & pdo_oci
RUN \
  docker-php-ext-configure oci8 --with-oci8=instantclient,/tmp/instantclient_21_6/ && \
  docker-php-ext-configure pdo_oci --with-pdo-oci=instantclient,/tmp/instantclient_21_6/

# Install oci8 & pdo_oci
RUN \
  docker-php-ext-install oci8 pdo_oci && \
  echo /tmp/instantclient_21_6 > /etc/ld.so.conf.d/oracle.conf && \
  ldconfig

# Definir diretório de trabalho
WORKDIR \
  /var/www/html

# Definir permissões para o diretório
RUN \
  chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Copy composer.json
COPY \
  ./composer.json /var/www/html/composer.json

# PHPInfo for Test PHP Libs
COPY \
  ./phpinfo.php /var/www/html/phpinfo.php

# Test Database Connections
COPY \
  ./db-test.php /var/www/html/db-test.php

# Copy environment variables
COPY \
  ./.env.docker /var/www/html/.env

# Copy .htaccess for hidden files in directory list
COPY \
  ./.htaccess /var/www/html/.htaccess

# Copy apache settings
COPY \
  ./docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY \
  ./docker/apache/my-site.conf /etc/apache2/sites-available/my-site.conf

# Download and Install Composer
RUN \
  curl -sS https://getcomposer.org/installer | php --  --install-dir=/usr/bin --filename=composer

# Configuração de timezone
RUN \
  echo "date.timezone=UTC" > /usr/local/etc/php/conf.d/timezone.ini

# Configuração de XDebug
RUN \
  echo "xdebug.var_display_max_depth=-1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
  echo "xdebug.var_display_max_children=-1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
  echo "xdebug.var_display_max_data=-1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Apache Configurations, Mod Rewrite
RUN \
  ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/rewrite.load

# Write environment variables
RUN \
  echo 'SetEnv MYSQL_HOST ${MYSQL_HOST}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv MYSQL_PORT ${MYSQL_PORT}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv MYSQL_DATABASE ${MYSQL_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv MYSQL_USER ${MYSQL_USER}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv MYSQL_PASSWORD ${MYSQL_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv MYSQL_CHARSET ${MYSQL_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv PGSQL_HOST ${PGSQL_HOST}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv PGSQL_PORT ${PGSQL_PORT}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv PGSQL_DATABASE ${PGSQL_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv PGSQL_USER ${PGSQL_USER}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv PGSQL_PASSWORD ${PGSQL_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv PGSQL_CHARSET ${PGSQL_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLSRV_HOST ${SQLSRV_HOST}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLSRV_PORT ${SQLSRV_PORT}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLSRV_DATABASE ${SQLSRV_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLSRV_USER ${SQLSRV_USER}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLSRV_PASSWORD ${SQLSRV_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLSRV_CHARSET ${SQLSRV_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv OCI_HOST ${OCI_HOST}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv OCI_PORT ${OCI_PORT}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv OCI_DATABASE ${OCI_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv OCI_USER ${OCI_USER}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv OCI_PASSWORD ${OCI_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv OCI_CHARSET ${OCI_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv FBIRD_HOST ${FBIRD_HOST}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv FBIRD_PORT ${FBIRD_PORT}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv FBIRD_DATABASE ${FBIRD_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv FBIRD_USER ${FBIRD_USER}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv FBIRD_PASSWORD ${FBIRD_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv FBIRD_CHARSET ${FBIRD_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLITE_DATABASE ${SQLITE_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLITE_DATABASE_MEMORY ${SQLITE_DATABASE_MEMORY}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLITE_CHARSET ${SQLITE_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf

# Run apache and composer install
RUN \
  echo "ServerName localhost" >> /etc/apache2/apache2.conf && \
  a2enmod headers && \
  a2enmod rewrite && \
  a2dissite 000-default && \
  a2ensite my-site && \
  service apache2 stop && \
  service apache2 start && \
  composer install -n --ignore-platform-reqs
