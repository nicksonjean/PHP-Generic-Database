<?php

use
    GenericDatabase\Connection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$context = Connection::new(PATH_ROOT . '/resources/dsn/json/stg_mysqli.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/json/stg_pgsql.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/json/stg_sqlsrv.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/json/stg_oci.json')->connect();

var_dump($context);

if (extension_loaded('interbase')) {

    $context = Connection::new(PATH_ROOT . '/resources/dsn/json/stg_firebird.json')->connect();

    var_dump($context);
    
}

$context = Connection::new(PATH_ROOT . '/resources/dsn/json/stg_sqlite.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/json/stg_pdo_mysql.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/json/stg_pdo_pgsql.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/json/stg_pdo_sqlsrv.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/json/stg_pdo_oci.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/json/stg_pdo_firebird.json')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/json/stg_pdo_sqlite.json')->connect();

var_dump($context);
