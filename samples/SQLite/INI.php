<?php

use GenericDatabase\Engine\SQLiteEngine;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$sqlite = SQLiteEngine::new(PATH_ROOT . '/resources/dsn/ini/sqlite.ini')->connect();

var_dump($sqlite);
