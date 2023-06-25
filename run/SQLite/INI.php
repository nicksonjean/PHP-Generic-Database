<?php

use GenericDatabase\Engine\SQLiteEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$sqlite = SQLiteEngine::new('../../assets/INI/sqlite.ini')->connect();

var_dump($sqlite);
