<?php

use
    GenericDatabase\Connection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$context = Connection::new(PATH_ROOT . '/resources/dsn/yaml/stg_mysqli.yaml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/yaml/stg_pgsql.yaml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/yaml/stg_sqlsrv.yaml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/yaml/stg_oci.yaml')->connect();

var_dump($context);

if (extension_loaded('interbase')) {

    $context = Connection::new(PATH_ROOT . '/resources/dsn/yaml/stg_firebird.yaml')->connect();

    var_dump($context);

}

$context = Connection::new(PATH_ROOT . '/resources/dsn/yaml/stg_sqlite.yaml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/yaml/stg_pdo_mysql.yaml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/yaml/stg_pdo_pgsql.yaml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/yaml/stg_pdo_sqlsrv.yaml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/yaml/stg_pdo_oci.yaml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/yaml/stg_pdo_firebird.yaml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/yaml/stg_pdo_sqlite.yaml')->connect();

var_dump($context);
