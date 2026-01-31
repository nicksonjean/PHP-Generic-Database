<?php

use GenericDatabase\Engine\CSVConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$csv = CSVConnection::new(PATH_ROOT . '/resources/dsn/ini/csv.ini')->connect();

var_dump($csv);
