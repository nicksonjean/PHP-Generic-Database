<?php

use GenericDatabase\Engine\FirebirdConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$firebird = FirebirdConnection::new(PATH_ROOT . '/resources/dsn/csv/firebird.csv')->connect();

var_dump($firebird);
