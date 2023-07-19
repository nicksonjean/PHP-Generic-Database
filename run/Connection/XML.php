<?php

use
    GenericDatabase\Connection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$context = Connection::new(PATH_ROOT . '/assets/XML/stg_mysqli.xml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/XML/stg_pgsql.xml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/XML/stg_sqlsrv.xml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/XML/stg_oci.xml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/XML/stg_fbird.xml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/XML/stg_sqlite.xml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/XML/stg_pdo_mysql.xml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/XML/stg_pdo_pgsql.xml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/XML/stg_pdo_sqlsrv.xml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/XML/stg_pdo_oci.xml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/XML/stg_pdo_firebird.xml')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/XML/stg_pdo_sqlite.xml')->connect();

var_dump($context);
