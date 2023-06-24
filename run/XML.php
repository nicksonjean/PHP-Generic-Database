<?php

use
  GenericDatabase\Connection;

require_once __DIR__ . '/../vendor/autoload.php';

$context = Connection::new('../assets/XML/stg_mysqli.xml')->connect();

var_dump($context);

$context = Connection::new('../assets/XML/stg_pgsql.xml')->connect();

var_dump($context);

$context = Connection::new('../assets/XML/stg_sqlsrv.xml')->connect();

var_dump($context);

$context = Connection::new('../assets/XML/stg_oci.xml')->connect();

var_dump($context);

$context = Connection::new('../assets/XML/stg_fbird.xml')->connect();

var_dump($context);

$context = Connection::new('../assets/XML/stg_sqlite.xml')->connect();

var_dump($context);

$context = Connection::new('../assets/XML/stg_pdo_mysql.xml')->connect();

var_dump($context);

$context = Connection::new('../assets/XML/stg_pdo_pgsql.xml')->connect();

var_dump($context);

$context = Connection::new('../assets/XML/stg_pdo_sqlsrv.xml')->connect();

var_dump($context);

$context = Connection::new('../assets/XML/stg_pdo_oci.xml')->connect();

var_dump($context);

$context = Connection::new('../assets/XML/stg_pdo_firebird.xml')->connect();

var_dump($context);

$context = Connection::new('../assets/XML/stg_pdo_sqlite.xml')->connect();

var_dump($context);
