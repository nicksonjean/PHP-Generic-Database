<?php

use GenericDatabase\Engine\SQLite3Engine;

require_once __DIR__ . '/../../vendor/autoload.php';

$sqlite3 = SQLite3Engine::new('../../assets/XML/sqlite3.xml')->connect();

var_dump($sqlite3);