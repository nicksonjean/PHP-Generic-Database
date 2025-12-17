# Declara os argumentos enviados pelo docker-compose.yml
ARG PHP_VERSION

# Usa uma imagem do PHP baseado em Apache
FROM php:${PHP_VERSION:-8.3}-apache-bookworm

# Redeclara os argumentos enviados pelo docker-compose.yml
ARG PHP_VERSION
ARG PHP_PORT

# Define o driver de ODBC do MySQL
ARG MYSQL_ODBC_DRIVER=mariadb

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
  TZ='America/Sao_Paulo' \
  LD_LIBRARY_PATH=/usr/lib/x86_64-linux-gnu/odbc:/tmp/instantclient_21_6:${LD_LIBRARY_PATH}

# Atualizar o sistema e instalar dependências
RUN \
  apt-get update && apt-get install -y \
  libpq-dev \
  libsqlite3-dev \
  libsqliteodbc \
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
  if [ "$PHP_VERSION" = "7.3" ] || [ "$PHP_VERSION" = "7.4" ]; then \
    ( curl -sSLf https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - || echo 'return 1' ) | sh -s \
    xdebug yaml pcov mcrypt; \
  elif [ "$PHP_VERSION" = "8.0" ] || [ "$PHP_VERSION" = "8.1" ] || [ "$PHP_VERSION" = "8.2" ] || [ "$PHP_VERSION" = "8.3" ]; then \
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

# Prepare directories and install dependencies
RUN \
  mkdir -p /usr/lib/x86_64-linux-gnu/odbc && \
  mkdir -p /usr/local/lib/x86_64-linux-gnu && \
  apt-get update -y && apt-get update && \
  apt-get install -y --no-install-recommends gcc g++ gnupg

# Install ODBC Firebird
RUN \
  apt-get update && \
  apt-get install -y libfbclient2 firebird3.0-common && \
  cd /tmp/ && wget https://github.com/user-attachments/files/18010867/linux_libs.zip && \
  unzip linux_libs.zip && \
  cp -r /tmp/Release_x86_64/* /usr/lib/x86_64-linux-gnu/odbc/ && \
  chmod 755 /usr/lib/x86_64-linux-gnu/odbc/libOdbcFb.so && \
  ln -sf /usr/lib/x86_64-linux-gnu/odbc/libOdbcFb.so /usr/lib/libOdbcFb.so && \
  ln -sf /usr/lib/x86_64-linux-gnu/odbc/libOdbcFb.so /usr/local/lib/libOdbcFb.so && \
  ln -sf /usr/lib/x86_64-linux-gnu/odbc/libOdbcFb.so /usr/lib/x86_64-linux-gnu/libOdbcFb.so && \
  ln -sf /usr/lib/x86_64-linux-gnu/odbc/libOdbcFb.so /usr/local/lib/x86_64-linux-gnu/libOdbcFb.so && \
  (LIBODBCINST=$(find /usr/lib* -name "libodbcinst.so.1.0.0" 2>/dev/null | head -1) && \
   if [ -n "$LIBODBCINST" ]; then \
     ln -sf "$LIBODBCINST" /usr/lib/x86_64-linux-gnu/libodbcinst.so && \
     ln -sf "$LIBODBCINST" /usr/lib/x86_64-linux-gnu/odbc/libodbcinst.so; \
   fi) && \
  echo "[Firebird ODBC Driver]\nDescription=Firebird ODBC Driver\nDriver=/usr/lib/x86_64-linux-gnu/odbc/libOdbcFb.so\nSetup=/usr/lib/x86_64-linux-gnu/odbc/libOdbcFb.so\nThreading=1\nFileUsage=1\nCPTimeout=\nCPReuse=" >> /etc/odbcinst.ini && \
  echo "/usr/lib/x86_64-linux-gnu/odbc" > /etc/ld.so.conf.d/firebird-odbc.conf && \
  chmod 644 /etc/ld.so.conf.d/firebird-odbc.conf && \
  ldconfig && \
  test -f /usr/lib/x86_64-linux-gnu/odbc/libOdbcFb.so && \
  test -f /usr/lib/x86_64-linux-gnu/libodbcinst.so && \
  ldd /usr/lib/x86_64-linux-gnu/odbc/libOdbcFb.so && \
  file /usr/lib/x86_64-linux-gnu/odbc/libOdbcFb.so && \
  odbcinst -q -d | grep -i firebird && \
  cd /tmp && find . -maxdepth 1 -type f -name '*.zip*' -exec rm {} \; && \
  rm -rf /tmp/Release_x86_64

# Install pdo_firebird
RUN \
  docker-php-ext-install pdo_firebird && \
  docker-php-ext-enable pdo_firebird

# Instalar e configurar o driver ODBC do MySQL
# MYSQL_ODBC_DRIVER pode ser "mariadb" (padrão) ou "mysql"
# Ambos não podem ser instalados simultaneamente pois compartilham os mesmos nomes de drivers
RUN \
  if [ "$MYSQL_ODBC_DRIVER" = "mariadb" ]; then \
    echo "Installing MariaDB ODBC driver (odbc-mariadb package)..." && \
    apt-get update && apt-get install -y odbc-mariadb && \
    rm -rf /var/lib/apt/lists/* && \
    if [ -f /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so ]; then \
      # Criar links simbólicos para garantir compatibilidade com o caminho esperado
      ln -sf /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so /usr/lib/x86_64-linux-gnu/odbc/libmyodbc9a.so || true; \
      ln -sf /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so /usr/lib/x86_64-linux-gnu/odbc/libmyodbc9w.so || true; \
      # Registrar o driver com o nome esperado pelo código (se ainda não estiver registrado)
      if ! grep -q "\[MySQL ODBC 9.1 ANSI Driver\]" /etc/odbcinst.ini 2>/dev/null; then \
        echo "" >> /etc/odbcinst.ini && \
        echo "[MySQL ODBC 9.1 ANSI Driver]" >> /etc/odbcinst.ini && \
        echo "Description=MySQL ODBC 9.1 ANSI Driver (via MariaDB ODBC)" >> /etc/odbcinst.ini && \
        echo "Driver=/usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so" >> /etc/odbcinst.ini && \
        echo "Setup=/usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so" >> /etc/odbcinst.ini && \
        echo "Threading=1" >> /etc/odbcinst.ini && \
        echo "FileUsage=1" >> /etc/odbcinst.ini; \
      fi && \
      if ! grep -q "\[MySQL ODBC 9.1 Unicode Driver\]" /etc/odbcinst.ini 2>/dev/null; then \
        echo "" >> /etc/odbcinst.ini && \
        echo "[MySQL ODBC 9.1 Unicode Driver]" >> /etc/odbcinst.ini && \
        echo "Description=MySQL ODBC 9.1 Unicode Driver (via MariaDB ODBC)" >> /etc/odbcinst.ini && \
        echo "Driver=/usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so" >> /etc/odbcinst.ini && \
        echo "Setup=/usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so" >> /etc/odbcinst.ini && \
        echo "Threading=1" >> /etc/odbcinst.ini && \
        echo "FileUsage=1" >> /etc/odbcinst.ini; \
      fi && \
      # Configurar o caminho da biblioteca no ld.so.conf
      echo "/usr/lib/x86_64-linux-gnu/odbc" > /etc/ld.so.conf.d/mysql-odbc.conf && \
      chmod 644 /etc/ld.so.conf.d/mysql-odbc.conf && \
      ldconfig && \
      # Verificar se o driver foi registrado corretamente
      odbcinst -q -d | grep -i "MySQL ODBC 9.1" && \
      echo "MariaDB ODBC driver configured successfully"; \
    else \
      echo "Error: libmaodbc.so not found at /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so"; \
      echo "Trying to find alternative location..."; \
      find /usr -name "libmaodbc.so" 2>/dev/null || echo "libmaodbc.so not found in system"; \
      exit 1; \
    fi; \
  elif [ "$MYSQL_ODBC_DRIVER" = "mysql" ]; then \
    echo "Installing MySQL Connector/ODBC (official MySQL driver)..." && \
    cd /tmp/ && wget https://dev.mysql.com/get/Downloads/Connector-ODBC/9.1/mysql-connector-odbc-9.1.0-linux-glibc2.28-x86-64bit.tar.gz && \
    tar -C . -xzvf mysql-connector-odbc-9.1.0-linux-glibc2.28-x86-64bit.tar.gz && \
    cp -r /tmp/mysql-connector-odbc-9.1.0-linux-glibc2.28-x86-64bit/bin/* /usr/bin/ && \
    cp -r /tmp/mysql-connector-odbc-9.1.0-linux-glibc2.28-x86-64bit/lib/* /usr/lib/x86_64-linux-gnu/odbc/ && \
    ln -sf /usr/lib/x86_64-linux-gnu/odbc/libmyodbc9a.so /usr/lib/libmyodbc9a.so && \
    ln -sf /usr/lib/x86_64-linux-gnu/odbc/libmyodbc9w.so /usr/lib/libmyodbc9w.so && \
    ln -sf /usr/lib/x86_64-linux-gnu/odbc/libmyodbc9a.so /usr/local/lib/libmyodbc9a.so && \
    ln -sf /usr/lib/x86_64-linux-gnu/odbc/libmyodbc9w.so /usr/local/lib/libmyodbc9w.so && \
    /tmp/mysql-connector-odbc-9.1.0-linux-glibc2.28-x86-64bit/bin/myodbc-installer -a -d -n "MySQL ODBC 9.1 ANSI Driver" -t "Driver=/usr/lib/x86_64-linux-gnu/odbc/libmyodbc9a.so;SETUP=/usr/lib/x86_64-linux-gnu/odbc/libmyodbc9a.so" && \
    /tmp/mysql-connector-odbc-9.1.0-linux-glibc2.28-x86-64bit/bin/myodbc-installer -a -d -n "MySQL ODBC 9.1 Unicode Driver" -t "Driver=/usr/lib/x86_64-linux-gnu/odbc/libmyodbc9w.so;SETUP=/usr/lib/x86_64-linux-gnu/odbc/libmyodbc9w.so" && \
    echo "/usr/lib/x86_64-linux-gnu/odbc" > /etc/ld.so.conf.d/mysql-odbc.conf && \
    chmod 644 /etc/ld.so.conf.d/mysql-odbc.conf && \
    ldconfig && \
    # Verificar se o driver foi registrado corretamente
    odbcinst -q -d | grep -i "MySQL ODBC 9.1" && \
    echo "MySQL ODBC driver configured successfully" && \
    cd /tmp && find . -maxdepth 1 -type f -name '*.tar.gz*' -exec rm {} \; && \
    rm -rf /tmp/mysql-connector-odbc-9.1.0-linux-glibc2.28-x86-64bit; \
  else \
    echo "Error: MYSQL_ODBC_DRIVER must be 'mariadb' or 'mysql', got: ${MYSQL_ODBC_DRIVER}"; \
    exit 1; \
  fi

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
  mkdir -p /usr/local/include /usr/local/lib /usr/local/incl && \
  (test -f /usr/include/sql.h && ln -sf /usr/include/sql.h /usr/local/include/ && ln -sf /usr/include/sql.h /usr/local/incl/ || true) && \
  (test -f /usr/include/sqlext.h && ln -sf /usr/include/sqlext.h /usr/local/include/ && ln -sf /usr/include/sqlext.h /usr/local/incl/ || true) && \
  (test -f /usr/include/sqltypes.h && ln -sf /usr/include/sqltypes.h /usr/local/include/ && ln -sf /usr/include/sqltypes.h /usr/local/incl/ || true) && \
  (test -f /usr/include/sqlucode.h && ln -sf /usr/include/sqlucode.h /usr/local/include/ && ln -sf /usr/include/sqlucode.h /usr/local/incl/ || true) && \
  docker-php-ext-configure odbc --with-unixODBC=/usr && \
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
  apt-get clean && \
  rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Download and Unzip OCI Instant Client
RUN \
  cd /tmp/ && wget https://download.oracle.com/otn_software/linux/instantclient/216000/instantclient-basic-linux.x64-21.6.0.0.0dbru.zip && \
  wget https://download.oracle.com/otn_software/linux/instantclient/216000/instantclient-sdk-linux.x64-21.6.0.0.0dbru.zip && \
  wget https://download.oracle.com/otn_software/linux/instantclient/216000/instantclient-odbc-linux.x64-21.6.0.0.0dbru.zip && \
  cd /tmp && find . -maxdepth 1 -type f -name '*.zip*' -exec unzip {} \; && \
  cd /tmp/instantclient_21_6 && ./odbc_update_ini.sh / /tmp/instantclient_21_6 "Oracle 21 ODBC driver" "OracleODBC-21" /etc/odbc.ini && \
  cd /tmp && find . -maxdepth 1 -type f -name '*.zip*' -exec rm {} \;

# Configure oci8 & pdo_oci
RUN \
  docker-php-ext-configure oci8 --with-oci8=instantclient,/tmp/instantclient_21_6/ && \
  docker-php-ext-configure pdo_oci --with-pdo-oci=instantclient,/tmp/instantclient_21_6/

# Install oci8 & pdo_oci
RUN \
  docker-php-ext-install oci8 pdo_oci && \
  echo /tmp/instantclient_21_6 > /etc/ld.so.conf.d/oracle.conf && \
  ldconfig

# Ensure ODBC library paths are properly configured and permissions are correct
RUN \
  chmod -R 755 /usr/lib/x86_64-linux-gnu/odbc && \
  chmod 644 /etc/ld.so.conf.d/firebird-odbc.conf && \
  (test -f /etc/ld.so.conf.d/mysql-odbc.conf && chmod 644 /etc/ld.so.conf.d/mysql-odbc.conf || true) && \
  ldconfig && \
  # Verificar se o driver MySQL/MariaDB está disponível (pode ser libmaodbc.so ou libmyodbc9a.so)
  (test -f /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so || test -f /usr/lib/x86_64-linux-gnu/odbc/libmyodbc9a.so || echo "Warning: MySQL ODBC driver not found") && \
  (test -f /usr/lib/x86_64-linux-gnu/odbc/libmyodbc9w.so || test -f /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so || echo "Warning: MySQL ODBC Unicode driver not found") && \
  test -f /usr/lib/x86_64-linux-gnu/odbc/libOdbcFb.so && \
  odbcinst -q -d | grep -i -E "(mysql|mariadb|firebird)" && \
  ldconfig -p | grep -i odbc

# Debug Trace for ODBC
RUN \
  echo "\n[ODBC]\nTrace=Yes\nTraceFile=/tmp/odbc.log\n" >> /etc/odbcinst.ini

# Definir diretório de trabalho
WORKDIR \
  /var/www/html

# Definir permissões para o diretório
RUN \
  chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Copy composer.json
COPY \
  ./composer.json /var/www/html/composer.json

# Copy environment variables
COPY \
  ./.env.docker /var/www/html/.env

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

# Configuração de memory_limit do PHP
RUN \
  echo "memory_limit=-1" >>  /usr/local/etc/php/php.ini

# Apache Configurations, Mod Rewrite
RUN \
  ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/rewrite.load && \
  echo "SetEnv LD_LIBRARY_PATH /usr/lib/x86_64-linux-gnu/odbc:/usr/lib/x86_64-linux-gnu:/tmp/instantclient_21_6:\${LD_LIBRARY_PATH}" >> /etc/apache2/conf-enabled/environment.conf && \
  echo "export LD_LIBRARY_PATH=/usr/lib/x86_64-linux-gnu/odbc:/usr/lib/x86_64-linux-gnu:/tmp/instantclient_21_6:\${LD_LIBRARY_PATH}" >> /etc/apache2/envvars

# Write environment variables
RUN \
  echo 'SetEnv MYSQL_HOST ${MYSQL_HOST}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv MYSQL_PORT ${MYSQL_PORT}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv MYSQL_DATABASE ${MYSQL_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv MYSQL_USERNAME ${MYSQL_USERNAME}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv MYSQL_PASSWORD ${MYSQL_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv MYSQL_CHARSET ${MYSQL_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv MYSQL_ODBC_DRIVER ${MYSQL_ODBC_DRIVER}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv PGSQL_HOST ${PGSQL_HOST}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv PGSQL_PORT ${PGSQL_PORT}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv PGSQL_DATABASE ${PGSQL_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv PGSQL_USERNAME ${PGSQL_USERNAME}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv PGSQL_PASSWORD ${PGSQL_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv PGSQL_CHARSET ${PGSQL_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLSRV_HOST ${SQLSRV_HOST}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLSRV_PORT ${SQLSRV_PORT}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLSRV_DATABASE ${SQLSRV_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLSRV_USERNAME ${SQLSRV_USERNAME}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLSRV_PASSWORD ${SQLSRV_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv SQLSRV_CHARSET ${SQLSRV_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv OCI_HOST ${OCI_HOST}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv OCI_PORT ${OCI_PORT}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv OCI_DATABASE ${OCI_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv OCI_USERNAME ${OCI_USERNAME}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv OCI_PASSWORD ${OCI_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv OCI_CHARSET ${OCI_CHARSET}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv FBIRD_HOST ${FBIRD_HOST}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv FBIRD_PORT ${FBIRD_PORT}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv FBIRD_DATABASE ${FBIRD_DATABASE}' >> /etc/apache2/conf-enabled/environment.conf && \
  echo 'SetEnv FBIRD_USERNAME ${FBIRD_USERNAME}' >> /etc/apache2/conf-enabled/environment.conf && \
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
  composer install -n --ignore-platform-reqs --no-dev || composer install -n --ignore-platform-reqs || true