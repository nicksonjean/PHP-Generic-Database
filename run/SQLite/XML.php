<?php

use GenericDatabase\Engine\SQLiteEngine;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$sqlite = SQLiteEngine::new(PATH_ROOT . '/assets/XML/sqlite.xml')->connect();

var_dump($sqlite);
