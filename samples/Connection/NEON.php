<?php

use
    GenericDatabase\Connection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$context = Connection::new(PATH_ROOT . '/resources/dsn/neon/stg_mysqli.neon')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/neon/stg_pgsql.neon')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/neon/stg_sqlsrv.neon')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/neon/stg_oci.neon')->connect();

var_dump($context);

if (extension_loaded('interbase')) {

    $context = Connection::new(PATH_ROOT . '/resources/dsn/neon/stg_firebird.neon')->connect();

    var_dump($context);

}

$context = Connection::new(PATH_ROOT . '/resources/dsn/neon/stg_sqlite.neon')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/neon/stg_pdo_mysql.neon')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/neon/stg_pdo_pgsql.neon')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/neon/stg_pdo_sqlsrv.neon')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/neon/stg_pdo_oci.neon')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/neon/stg_pdo_firebird.neon')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/resources/dsn/neon/stg_pdo_sqlite.neon')->connect();

var_dump($context);
