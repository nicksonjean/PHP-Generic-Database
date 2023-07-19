<?php

use GenericDatabase\Engine\PgSQLEngine;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$pgsql = PgSQLEngine::new(PATH_ROOT . '/assets/INI/pgsql.ini')->connect();

var_dump($pgsql);
