# Declara os argumentos enviados pelo docker-compose.yml
ARG PHP_VERSION
# PHP_BASE_TAG define o sufixo da tag da imagem (ex: "-bookworm" ou vazio)
# FrankenPHP suporta apenas PHP 8.2+, então sempre usa -bookworm
ARG PHP_BASE_TAG=-bookworm

# Usa a imagem oficial do FrankenPHP
# FrankenPHP suporta apenas PHP 8.2, 8.3, 8.4 e 8.5
# Imagem base: dunglas/frankenphp:{version}-php{VERSION}-bookworm (Debian-based, recomendado)
# Usa a versão específica do PHP ao invés de latest para garantir a versão correta
# Formato da tag: 1-php8.2-bookworm, 1-php8.3-bookworm, etc.
FROM dunglas/frankenphp:1-php${PHP_VERSION}-bookworm

# Redeclara os argumentos enviados pelo docker-compose.yml
ARG PHP_VERSION
ARG PHP_PORT

# Define o driver de ODBC do MySQL (com valor padrão)
ARG MYSQL_ODBC_DRIVER=mariadb
ENV MYSQL_ODBC_DRIVER=${MYSQL_ODBC_DRIVER}

# Controla se instala dependências de desenvolvimento (padrão: sim, para dev)
ARG COMPOSER_NO_DEV=false
ENV COMPOSER_NO_DEV=${COMPOSER_NO_DEV}

# Tag do Autor
LABEL author="Nickson Jeanmerson (nickson.jeanmerson@gmail.com)"

# Echo para verificar os valores
RUN \
  echo "PHP_VERSION=${PHP_VERSION}, PHP_PORT=${PHP_PORT}"

# Verificar se a versão PHP é suportada pelo FrankenPHP
RUN \
  if [ "$PHP_VERSION" != "8.2" ] && [ "$PHP_VERSION" != "8.3" ] && [ "$PHP_VERSION" != "8.4" ] && [ "$PHP_VERSION" != "8.5" ]; then \
    echo "ERROR: FrankenPHP suporta apenas PHP 8.2, 8.3, 8.4 e 8.5. Versão fornecida: ${PHP_VERSION}"; \
    exit 1; \
  fi

# Definindo variáveis de ambiente
ENV \
  ACCEPT_EULA='Y' \
  LANGUAGE='en_US:en' \
  LC_CTYPE=C.UTF-8 \
  LC_ALL=C.UTF-8 \
  LANG=C.UTF-8 \
  TZ='America/Sao_Paulo' \
  LD_LIBRARY_PATH=/usr/lib/x86_64-linux-gnu/odbc:/tmp/instantclient_21_6:${LD_LIBRARY_PATH} \
  SERVER_NAME="localhost" \
  FRANKENPHP_CONFIG="worker ./public/index.php"

# Atualizar o sistema e instalar dependências
# Nota: Debian Bookworm (usado pelo FrankenPHP) usa nomes de pacotes específicos
RUN \
  apt-get update && apt-get install -y \
  libpq-dev \
  libsqlite3-dev \
  libsqliteodbc \
  unixodbc-dev \
  libyaml-dev \
  default-libmysqlclient-dev \
  firebird-dev \
  freetds-dev \
  freetds-bin \
  tdsodbc \
  odbc-postgresql \
  odbcinst \
  libodbcinst2 \
  unixodbc-common \
  libodbc2 \
  unixodbc \
  wget \
  unzip \
  libxml2-dev \
  libxslt-dev \
  libaio1 \
  libaio-dev \
  ssl-cert \
  git \
  g++ \
  make \
  autoconf \
  libc-dev \
  pkg-config && \
  apt-get clean -y && \
  rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
  (rm -f /var/log/lastlog /var/log/faillog || true) && \
  # Criar link simbólico para compatibilidade com aplicações que esperam libaio.so.1
  (test -f /usr/lib/x86_64-linux-gnu/libaio.so.1.0.1 && \
   ln -sf /usr/lib/x86_64-linux-gnu/libaio.so.1.0.1 /usr/lib/libaio.so.1 || \
   test -f /usr/lib/x86_64-linux-gnu/libaio.so.1 && \
   echo "libaio.so.1 already exists" || true)

# Install PHP extensions using install-php-extensions (suporta ZTS)
# FrankenPHP requer extensões compiladas com ZTS (Zend Thread Safety)
RUN \
  install-php-extensions \
    simplexml \
    iconv \
    zlib \
    pdo \
    pdo_sqlite \
    sqlite3 \
    pdo_mysql \
    mysqli \
    pdo_pgsql \
    pgsql \
    pdo_dblib \
    pdo_firebird \
    odbc \
    pdo_odbc \
    xdebug \
    yaml \
    pcov

# Install zlib and fix config file (se necessário)
RUN \
  docker-php-ext-install zlib; exit 0 && \
  cp /usr/src/php/ext/zlib/config0.m4 /usr/src/php/ext/zlib/config.m4 && \
  docker-php-ext-install zlib || true

# Install ODBC Firebird
RUN \
  apt-get update && \
  apt-get install -y libfbclient2 firebird3.0-common && \
  cd /tmp/ && wget https://github.com/user-attachments/files/18010867/linux_libs.zip && \
  unzip linux_libs.zip && \
  cp -r /tmp/Release_x86_64/* /usr/lib/x86_64-linux-gnu/odbc/ && \
  chmod 755 /usr/lib/x86_64-linux-gnu/odbc/libOdbcFb.so && \
  mkdir -p /usr/local/lib/x86_64-linux-gnu && \
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

# Instalar e configurar o driver ODBC do MySQL
RUN \
  if [ "$MYSQL_ODBC_DRIVER" = "mariadb" ]; then \
    echo "Installing MariaDB ODBC driver (odbc-mariadb package)..." && \
    apt-get update && apt-get install -y odbc-mariadb && \
    rm -rf /var/lib/apt/lists/* && \
    if [ -f /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so ]; then \
      ln -sf /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so /usr/lib/x86_64-linux-gnu/odbc/libmyodbc9a.so || true; \
      ln -sf /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so /usr/lib/x86_64-linux-gnu/odbc/libmyodbc9w.so || true; \
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
      echo "/usr/lib/x86_64-linux-gnu/odbc" > /etc/ld.so.conf.d/mysql-odbc.conf && \
      chmod 644 /etc/ld.so.conf.d/mysql-odbc.conf && \
      ldconfig && \
      odbcinst -q -d | grep -i "MySQL ODBC 9.1" && \
      echo "MariaDB ODBC driver configured successfully"; \
    else \
      echo "Error: libmaodbc.so not found"; \
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
    odbcinst -q -d | grep -i "MySQL ODBC 9.1" && \
    echo "MySQL ODBC driver configured successfully" && \
    cd /tmp && find . -maxdepth 1 -type f -name '*.tar.gz*' -exec rm {} \; && \
    rm -rf /tmp/mysql-connector-odbc-9.1.0-linux-glibc2.28-x86-64bit; \
  else \
    echo "Error: MYSQL_ODBC_DRIVER must be 'mariadb' or 'mysql', got: ${MYSQL_ODBC_DRIVER}"; \
    exit 1; \
  fi

# Download and Install sqlsrv and pdo_sqlsrv
# Usa install-php-extensions (mlocati) que suporta ZTS necessário para FrankenPHP
RUN \
  if [ "$PHP_VERSION" = "8.2" ] || [ "$PHP_VERSION" = "8.3" ] || [ "$PHP_VERSION" = "8.4" ]; then \
    echo "Installing sqlsrv and pdo_sqlsrv via mlocati for PHP ${PHP_VERSION}..." && \
    curl -sSLf --connect-timeout 30 --max-time 300 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o /tmp/install-php-extensions && \
    chmod +x /tmp/install-php-extensions && \
    /tmp/install-php-extensions sqlsrv pdo_sqlsrv && \
    rm -f /tmp/install-php-extensions && \
    php -r "if (!extension_loaded('sqlsrv')) { echo 'ERROR: sqlsrv extension not loaded'; exit(1); }" && \
    php -r "if (!extension_loaded('pdo_sqlsrv')) { echo 'ERROR: pdo_sqlsrv extension not loaded'; exit(1); }" && \
    echo "sqlsrv and pdo_sqlsrv installed and verified successfully for PHP ${PHP_VERSION}"; \
  elif [ "$PHP_VERSION" = "8.5" ]; then \
    echo "Compiling sqlsrv/pdo_sqlsrv from source for PHP 8.5..." && \
    apt-get update && apt-get install -y git g++ make autoconf libc-dev pkg-config unixodbc-dev && \
    cd /tmp && \
    git clone --depth 1 --branch master https://github.com/microsoft/msphpsql.git msphpsql || \
    git clone --depth 1 https://github.com/microsoft/msphpsql.git msphpsql && \
    cd msphpsql/source && \
    echo "Checking sqlsrv config.m4..." && \
    if [ -f "sqlsrv/config.m4" ]; then \
      echo "Found config.m4, checking for SQLSRV headers check..." && \
      grep -n "SQLSRV headers" sqlsrv/config.m4 || echo "No 'SQLSRV headers' check found"; \
    fi && \
    echo "Compiling sqlsrv..." && \
    cd sqlsrv && \
    if [ -f "../shared/core_sqlsrv.h" ]; then \
      echo "Found shared headers at ../shared/"; \
    else \
      echo "Warning: shared headers not found at ../shared/core_sqlsrv.h"; \
      ls -la ../shared/ 2>/dev/null || echo "Shared directory does not exist"; \
    fi && \
    phpize && \
    if [ -f "configure" ]; then \
      echo "Patching configure script to fix sqlsrv_inc_path when headers are in ../shared/..." && \
      perl -i -0pe 's/(elif\s+test\s+-f\s+\$srcdir\/shared\/core_sqlsrv\.h;\s+then\s+sqlsrv_inc_path=\$srcdir\/shared\/)/$1\n    elif test -f ..\/shared\/core_sqlsrv.h; then\n      sqlsrv_inc_path="..\/shared\/"/g' configure 2>/dev/null || \
      sed -i '/test -f \$srcdir\/shared\/core_sqlsrv\.h/a\    elif test -f ..\/shared\/core_sqlsrv.h; then\n      sqlsrv_inc_path="..\/shared\/"' configure 2>/dev/null || \
      echo "Patch attempt completed"; \
    fi && \
    ./configure && \
    if [ -f "Makefile" ]; then \
      echo "Fixing Makefile paths for shared directory..." && \
      SHARED_DIR=$(cd .. && pwd)/shared && \
      sed -i "s|/tmp/msphpsql/source/sqlsrv/shared|${SHARED_DIR}|g" Makefile && \
      sed -i "s|\$srcdir/shared|${SHARED_DIR}|g" Makefile && \
      sed -i "s|sqlsrv/shared|shared|g" Makefile && \
      echo "Makefile paths fixed to use ${SHARED_DIR}"; \
    fi && \
    make -j$(nproc) && \
    make install && \
    cd .. && \
    echo "Compiling pdo_sqlsrv..." && \
    cd pdo_sqlsrv && \
    if [ -f "../shared/core_sqlsrv.h" ]; then \
      echo "Found shared headers at ../shared/"; \
    else \
      echo "Warning: shared headers not found at ../shared/core_sqlsrv.h"; \
    fi && \
    echo "Patching pdo_sqlsrv source code for PHP 8.5 compatibility..." && \
    if [ -f "php_pdo_sqlsrv_int.h" ]; then \
      sed -i 's/query_stmt_zval/query_stmt_obj/g' php_pdo_sqlsrv_int.h && \
      perl -i -pe 's/^\s*zval_ptr_dtor\( &dbh->query_stmt_obj \);\s*$/\/\* PHP 8.5: query_stmt_obj is zend_object**, GC manages cleanup \*\//g' php_pdo_sqlsrv_int.h && \
      echo "Patched query_stmt_zval -> query_stmt_obj and removed zval_ptr_dtor call in php_pdo_sqlsrv_int.h"; \
    fi && \
    if [ -f "pdo_dbh.cpp" ]; then \
      sed -i 's/prev_err_mode = dbh->error_mode;/prev_err_mode = (pdo_error_mode)dbh->error_mode;/g' pdo_dbh.cpp && \
      echo "Patched error_mode type conversion in pdo_dbh.cpp"; \
    fi && \
    phpize && \
    if [ -f "configure" ]; then \
      echo "Patching configure script to fix pdo_sqlsrv_inc_path when headers are in ../shared/..." && \
      perl -i -0pe 's/(elif\s+test\s+-f\s+\$srcdir\/shared\/core_sqlsrv\.h;\s+then\s+pdo_sqlsrv_inc_path=\$srcdir\/shared\/)/$1\n    elif test -f ..\/shared\/core_sqlsrv.h; then\n      pdo_sqlsrv_inc_path="..\/shared\/"/g' configure 2>/dev/null || \
      sed -i '/test -f \$srcdir\/shared\/core_sqlsrv\.h/a\    elif test -f ..\/shared\/core_sqlsrv.h; then\n      pdo_sqlsrv_inc_path="..\/shared\/"' configure 2>/dev/null || \
      echo "Patch attempt completed"; \
    fi && \
    ./configure && \
    if [ -f "Makefile" ]; then \
      echo "Fixing Makefile paths for shared directory..." && \
      SHARED_DIR=$(cd .. && pwd)/shared && \
      sed -i "s|pdo_sqlsrv/shared|shared|g" Makefile && \
      sed -i "s|\$srcdir/shared|${SHARED_DIR}|g" Makefile 2>/dev/null || \
      sed -i "s|\.\./shared|${SHARED_DIR}|g" Makefile 2>/dev/null || \
      echo "Makefile path fix attempt completed"; \
    fi && \
    make -j$(nproc) && \
    make install && \
    cd ../.. && \
    docker-php-ext-enable sqlsrv pdo_sqlsrv && \
    php -r "if (!extension_loaded('sqlsrv') || !extension_loaded('pdo_sqlsrv')) { echo 'Extensions not loaded'; exit(1); }" && \
    cd / && rm -rf /tmp/msphpsql && \
    apt-get purge -y git g++ make autoconf libc-dev pkg-config && \
    apt-get autoremove -y && apt-get clean && \
    echo "sqlsrv/pdo_sqlsrv compiled and enabled successfully for PHP 8.5"; \
  else \
    echo "Unsupported PHP version: ${PHP_VERSION}"; \
    exit 1; \
  fi

# Download and Install MSSQL Connector for ODBC and MDBTools for Access
# Nota: Microsoft não tem suporte completo para Debian Trixie (13), então usamos bookworm (12) como padrão
RUN \
  apt-get update && \
  apt-get install -y gnupg2 ca-certificates curl lsb-release && \
  mkdir -p /usr/share/keyrings && \
  curl -sSL https://packages.microsoft.com/keys/microsoft.asc > /tmp/microsoft.asc && \
  gpg --batch --no-tty --dearmor < /tmp/microsoft.asc > /usr/share/keyrings/microsoft-prod.gpg 2>/dev/null || \
  (gpg --dearmor < /tmp/microsoft.asc > /usr/share/keyrings/microsoft-prod.gpg 2>/dev/null) && \
  rm -f /tmp/microsoft.asc && \
  echo "deb [arch=amd64,arm64,armhf signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" > /etc/apt/sources.list.d/mssql-release.list && \
  apt-get update && \
  (apt-get -y --no-install-recommends install msodbcsql18 odbc-mdbtools || \
   apt-get -y --no-install-recommends install msodbcsql17 odbc-mdbtools || \
   echo "Warning: msodbcsql may not be available for this Debian version, continuing without it") && \
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

# Install oci8 & pdo_oci
RUN \
  if [ "$PHP_VERSION" = "8.2" ] || [ "$PHP_VERSION" = "8.3" ] || [ "$PHP_VERSION" = "8.4" ]; then \
    echo "Installing oci8 and pdo_oci via mlocati for PHP ${PHP_VERSION}..." && \
    curl -sSLf --connect-timeout 30 --max-time 300 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o /tmp/install-php-extensions && \
    chmod +x /tmp/install-php-extensions && \
    /tmp/install-php-extensions oci8 pdo_oci || \
    (echo "Warning: oci8/pdo_oci may require manual ZTS compilation" && exit 0); \
    rm -f /tmp/install-php-extensions; \
    echo "oci8 and pdo_oci installed successfully for PHP ${PHP_VERSION}"; \
  elif [ "$PHP_VERSION" = "8.5" ]; then \
    echo /tmp/instantclient_21_6 > /etc/ld.so.conf.d/oracle.conf && \
    ldconfig && \
    echo "Installing oci8 via mlocati and compiling pdo_oci from source for PHP 8.5..." && \
    apt-get update && apt-get install -y autoconf g++ make libc-dev pkg-config git && \
    curl -sSLf --connect-timeout 30 --max-time 300 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o /tmp/install-php-extensions && \
    chmod +x /tmp/install-php-extensions && \
    /tmp/install-php-extensions oci8 && \
    rm -f /tmp/install-php-extensions && \
    rm -f /usr/local/etc/php/conf.d/docker-php-ext-pdo_oci.ini /usr/local/lib/php/extensions/*/pdo_oci.so 2>/dev/null || true && \
    cd /tmp && \
    git clone --depth 1 https://github.com/php/pecl-database-pdo_oci.git pdo_oci && \
    cd pdo_oci && \
    phpize && ./configure --with-pdo-oci=instantclient,/tmp/instantclient_21_6 && \
    make -j$(nproc) && make install && \
    EXT_DIR=$(php-config --extension-dir) && \
    OCI8_SO=$(find "$EXT_DIR" -name "*oci8*.so" 2>/dev/null | head -1) && \
    PDO_OCI_SO=$(find "$EXT_DIR" -name "*pdo_oci*.so" 2>/dev/null | head -1) && \
    OCI8_INI_FILE="/usr/local/etc/php/conf.d/docker-php-ext-oci8.ini" && \
    PDO_OCI_INI_FILE="/usr/local/etc/php/conf.d/docker-php-ext-pdo_oci.ini" && \
    if [ -n "$OCI8_SO" ] && [ -f "$OCI8_SO" ]; then \
      echo "extension=$(basename $OCI8_SO)" > "$OCI8_INI_FILE" && \
      echo "Created oci8 ini file: $OCI8_INI_FILE pointing to $OCI8_SO"; \
    fi && \
    if [ -n "$PDO_OCI_SO" ] && [ -f "$PDO_OCI_SO" ]; then \
      echo "extension=$(basename $PDO_OCI_SO)" > "$PDO_OCI_INI_FILE" && \
      echo "Created pdo_oci ini file: $PDO_OCI_INI_FILE pointing to $PDO_OCI_SO"; \
    fi && \
    cd / && rm -rf /tmp/pdo_oci && \
    ldconfig && \
    php -r "if (!extension_loaded('oci8')) { echo 'oci8 not loaded'; exit(1); }" && \
    php -r "if (!extension_loaded('pdo_oci')) { echo 'pdo_oci not loaded'; exit(1); }" && \
    apt-get purge -y autoconf g++ make libc-dev pkg-config git && \
    apt-get autoremove -y && apt-get clean && \
    echo "Both oci8 (via mlocati) and pdo_oci (from source) installed and verified successfully"; \
  fi

# Ensure ODBC library paths are properly configured and permissions are correct
RUN \
  chmod -R 755 /usr/lib/x86_64-linux-gnu/odbc && \
  chmod 644 /etc/ld.so.conf.d/firebird-odbc.conf && \
  (test -f /etc/ld.so.conf.d/mysql-odbc.conf && chmod 644 /etc/ld.so.conf.d/mysql-odbc.conf || true) && \
  ldconfig && \
  (test -f /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so || test -f /usr/lib/x86_64-linux-gnu/odbc/libmyodbc9a.so || echo "Warning: MySQL ODBC driver not found") && \
  (test -f /usr/lib/x86_64-linux-gnu/odbc/libmyodbc9w.so || test -f /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so || echo "Warning: MySQL ODBC Unicode driver not found") && \
  (test -f /usr/lib/x86_64-linux-gnu/odbc/libOdbcFb.so || echo "Warning: Firebird ODBC driver not found") && \
  (odbcinst -q -d | grep -i -E "(mysql|mariadb|firebird)" || echo "Warning: No ODBC drivers found in odbcinst") && \
  (ldconfig -p | grep -i odbc || echo "Warning: No ODBC libraries found in ldconfig")

# Debug Trace for ODBC
RUN \
  echo "\n[ODBC]\nTrace=Yes\nTraceFile=/tmp/odbc.log\n" >> /etc/odbcinst.ini

# Definir diretório de trabalho
WORKDIR /var/www/html

# Definir permissões para o diretório
RUN \
  chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Copy composer.json
COPY ./composer.json /var/www/html/composer.json

# Copy environment variables
COPY ./.env.docker /var/www/html/.env

# Copy Caddyfile configuration
COPY ./docker/stack-frankenphp/php-frankenphp/Caddyfile /etc/caddy/Caddyfile

# Download and Install Composer
RUN \
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

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
  echo "memory_limit=-1" >> /usr/local/etc/php/php.ini

# Instalar dependências do Composer
RUN \
  if [ "$COMPOSER_NO_DEV" = "true" ]; then \
    echo "Installing production dependencies only (--no-dev)..." && \
    composer install -n --ignore-platform-reqs --no-dev || composer install -n --ignore-platform-reqs --no-dev || true; \
  else \
    echo "Installing all dependencies (including dev)..." && \
    composer install -n --ignore-platform-reqs || true; \
  fi

# Expor porta HTTP padrão do Caddy (FrankenPHP)
EXPOSE 80 443

# Verificar que o binário frankenphp está disponível
# O FrankenPHP usa o binário 'frankenphp' que já contém o Caddy integrado
RUN \
  FRANKENPHP_PATH=$(which frankenphp 2>/dev/null || \
                    find /usr -name frankenphp -type f 2>/dev/null | head -1 || \
                    find /usr/local -name frankenphp -type f 2>/dev/null | head -1 || \
                    echo "") && \
  if [ -z "$FRANKENPHP_PATH" ] || [ ! -f "$FRANKENPHP_PATH" ]; then \
    echo "ERROR: frankenphp binary not found!"; \
    echo "Searching in common locations..."; \
    ls -la /usr/bin/frankenphp /usr/local/bin/frankenphp /bin/frankenphp 2>/dev/null || true; \
    exit 1; \
  else \
    echo "FrankenPHP found at: $FRANKENPHP_PATH"; \
    "$FRANKENPHP_PATH" version || true; \
  fi

# Comando padrão do FrankenPHP
# O binário 'frankenphp' já contém o Caddy integrado
# A imagem base do FrankenPHP já tem entrypoint configurado que executa 'frankenphp'
# Se o entrypoint padrão não funcionar, sobrescrevemos aqui
# O Caddyfile em /etc/caddy/Caddyfile será usado automaticamente
ENTRYPOINT ["frankenphp"]
CMD ["run", "--config", "/etc/caddy/Caddyfile"]
