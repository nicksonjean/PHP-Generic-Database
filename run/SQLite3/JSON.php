<?php

use GenericDatabase\Engine\SQLite3Engine;

require_once __DIR__ . '/../../vendor/autoload.php';

$sqlite = SQLite3Engine::new('../../assets/JSON/sqlite.json')->connect();

var_dump($sqlite);
