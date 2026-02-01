<?php

use
    GenericDatabase\Connection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$context = Connection::new(PATH_ROOT . '/resources/dsn/csv/stg_mysqli.csv')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/csv/stg_pgsql.csv')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/csv/stg_sqlsrv.csv')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/csv/stg_oci.csv')->connect();

var_dump($context);

if (extension_loaded('interbase')) {

    $context = Connection::new(PATH_ROOT . '/resources/dsn/csv/stg_firebird.csv')->connect();

    var_dump($context);

}

$context = Connection::new(PATH_ROOT . '/resources/dsn/csv/stg_sqlite.csv')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/csv/stg_pdo_mysql.csv')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/csv/stg_pdo_pgsql.csv')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/csv/stg_pdo_sqlsrv.csv')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/csv/stg_pdo_oci.csv')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/csv/stg_pdo_firebird.csv')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/csv/stg_pdo_sqlite.csv')->connect();

var_dump($context);
