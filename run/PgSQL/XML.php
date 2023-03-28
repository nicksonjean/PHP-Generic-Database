<?php

use GenericDatabase\Engine\PgSQLEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$pgsql = PgSQLEngine::new('../../assets/XML/pgsqln.xml')->connect();

var_dump($pgsql);
