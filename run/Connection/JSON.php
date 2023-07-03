<?php

use
    GenericDatabase\Connection;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$context = Connection::new(PATH_ROOT . '/assets/JSON/stg_mysqli.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/JSON/stg_pgsql.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/JSON/stg_sqlsrv.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/JSON/stg_oci.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/JSON/stg_fbird.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/JSON/stg_sqlite.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/JSON/stg_pdo_mysql.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/JSON/stg_pdo_pgsql.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/JSON/stg_pdo_sqlsrv.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/JSON/stg_pdo_oci.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/JSON/stg_pdo_firebird.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/JSON/stg_pdo_sqlite.json')->connect();

var_dump($context);
