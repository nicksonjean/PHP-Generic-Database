<?php

use GenericDatabase\Engine\INIConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$csv = INIConnection::new(PATH_ROOT . '/resources/dsn/ini/ini.ini')->connect();

var_dump($csv);
