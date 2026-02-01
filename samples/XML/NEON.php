<?php

use GenericDatabase\Engine\XMLConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$xml = XMLConnection::new(PATH_ROOT . '/resources/dsn/xml/xml.xml')->connect();

var_dump($xml);
