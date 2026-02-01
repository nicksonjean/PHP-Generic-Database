<?php

use GenericDatabase\Engine\SQLiteConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$sqlite = SQLiteConnection::new(PATH_ROOT . '/resources/dsn/csv/sqlite.csv')->connect();

var_dump($sqlite);
