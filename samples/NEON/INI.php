<?php

use GenericDatabase\Engine\NEONConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$neon = NEONConnection::new(PATH_ROOT . '/resources/dsn/ini/neon.ini')->connect();

var_dump($neon);
