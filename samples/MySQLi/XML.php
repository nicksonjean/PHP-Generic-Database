<?php

use GenericDatabase\Engine\MySQLiEngine;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = MySQLiEngine::new(PATH_ROOT . '/resources/dsn/xml/mysqli.xml')->connect();

var_dump($mysql);
