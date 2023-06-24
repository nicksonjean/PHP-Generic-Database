<?php

use
  GenericDatabase\Connection;

require_once __DIR__ . '/../vendor/autoload.php';

$context = Connection::new('../assets/YAML/stg_mysqli.yaml')->connect();

var_dump($context);

$context = Connection::new('../assets/YAML/stg_pgsql.yaml')->connect();

var_dump($context);

$context = Connection::new('../assets/YAML/stg_sqlsrv.yaml')->connect();

var_dump($context);

$context = Connection::new('../assets/YAML/stg_oci.yaml')->connect();

var_dump($context);

$context = Connection::new('../assets/YAML/stg_fbird.yaml')->connect();

var_dump($context);

$context = Connection::new('../assets/YAML/stg_sqlite.yaml')->connect();

var_dump($context);

$context = Connection::new('../assets/YAML/stg_pdo_mysql.yaml')->connect();

var_dump($context);

$context = Connection::new('../assets/YAML/stg_pdo_pgsql.yaml')->connect();

var_dump($context);

$context = Connection::new('../assets/YAML/stg_pdo_sqlsrv.yaml')->connect();

var_dump($context);

$context = Connection::new('../assets/YAML/stg_pdo_oci.yaml')->connect();

var_dump($context);

$context = Connection::new('../assets/YAML/stg_pdo_firebird.yaml')->connect();

var_dump($context);

$context = Connection::new('../assets/YAML/stg_pdo_sqlite.yaml')->connect();

var_dump($context);
