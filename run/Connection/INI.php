<?php

use
    GenericDatabase\Connection;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$context = Connection::new(PATH_ROOT . '/assets/INI/stg_mysqli.ini')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/INI/stg_pgsql.ini')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/INI/stg_sqlsrv.ini')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/INI/stg_oci.ini')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/INI/stg_fbird.ini')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/INI/stg_sqlite.ini')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/INI/stg_pdo_mysql.ini')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/INI/stg_pdo_pgsql.ini')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/INI/stg_pdo_sqlsrv.ini')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/INI/stg_pdo_oci.ini')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/INI/stg_pdo_firebird.ini')->connect();

var_dump($context);

$context = Connection::new(PATH_ROOT . '/assets/INI/stg_pdo_sqlite.ini')->connect();

var_dump($context);
