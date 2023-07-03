<?php

use GenericDatabase\Engine\PgSQLEngine;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$pgsql = PgSQLEngine::new(PATH_ROOT . '/assets/YAML/pgsql.yaml')->connect();

var_dump($pgsql);
