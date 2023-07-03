<?php

use GenericDatabase\Engine\MySQLiEngine;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = MySQLiEngine::new(PATH_ROOT . '/assets/JSON/mysqli.json')->connect();

var_dump($mysql);
