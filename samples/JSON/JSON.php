<?php

use GenericDatabase\Engine\JSONConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$json = JSONConnection::new(PATH_ROOT . '/resources/dsn/json/json.json')->connect();

var_dump($json);
