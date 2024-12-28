<?php

use GenericDatabase\Engine\PgSQLConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$pgsql = PgSQLConnection::new(PATH_ROOT . '/resources/dsn/xml/pgsql.xml')->connect();

var_dump($pgsql);
