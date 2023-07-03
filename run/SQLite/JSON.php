<?php

use GenericDatabase\Engine\SQLiteEngine;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$sqlite = SQLiteEngine::new(PATH_ROOT . '/assets/JSON/sqlite.json')->connect();

var_dump($sqlite);
