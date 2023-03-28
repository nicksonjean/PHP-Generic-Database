<?php

use GenericDatabase\Engine\PgSQLEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$pgsql = PgSQLEngine::new('../../assets/INI/pgsqln.ini')->connect();

var_dump($pgsql);
