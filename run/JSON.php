<?php

use
  GenericDatabase\Connection;

require_once __DIR__ . '/../vendor/autoload.php';

$context = Connection::new('../assets/JSON/stg_mysqli.json')->connect();

var_dump($context);

$context = Connection::new('../assets/JSON/stg_pgsql.json')->connect();

var_dump($context);

$context = Connection::new('../assets/JSON/stg_sqlsrv.json')->connect();

var_dump($context);

$context = Connection::new('../assets/JSON/stg_oci.json')->connect();

var_dump($context);

$context = Connection::new('../assets/JSON/stg_fbird.json')->connect();

var_dump($context);

$context = Connection::new('../assets/JSON/stg_sqlite.json')->connect();

var_dump($context);

$context = Connection::new('../assets/JSON/stg_pdo_mysql.json')->connect();

var_dump($context);

$context = Connection::new('../assets/JSON/stg_pdo_pgsql.json')->connect();

var_dump($context);

$context = Connection::new('../assets/JSON/stg_pdo_sqlsrv.json')->connect();

var_dump($context);

$context = Connection::new('../assets/JSON/stg_pdo_oci.json')->connect();

var_dump($context);

$context = Connection::new('../assets/JSON/stg_pdo_firebird.json')->connect();

var_dump($context);

$context = Connection::new('../assets/JSON/stg_pdo_sqlite.json')->connect();

var_dump($context);
