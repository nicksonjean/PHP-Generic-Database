<?php

use GenericDatabase\Engine\XMLConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$xml = XMLConnection::new(PATH_ROOT . '/resources/dsn/json/xml.json')->connect();

var_dump($xml);
