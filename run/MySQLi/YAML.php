<?php

use GenericDatabase\Engine\MySQLiEngine;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = MySQLiEngine::new(PATH_ROOT . '/assets/YAML/mysqli.yaml')->connect();

var_dump($mysql);
