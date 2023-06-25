<?php

use GenericDatabase\Engine\SQLiteEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$sqlite = SQLiteEngine::new('../../assets/JSON/sqlite.json')->connect();

var_dump($sqlite);
