<?php

use GenericDatabase\Engine\MySQLiConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = MySQLiConnection::new(PATH_ROOT . '/resources/dsn/yaml/mysqli.yaml')->connect();

var_dump($mysql);
