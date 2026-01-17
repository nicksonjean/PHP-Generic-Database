<?php

use GenericDatabase\Engine\JSONConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$json = JSONConnection::new(PATH_ROOT . '/resources/dsn/yaml/json.yaml')->connect();

var_dump($json);
