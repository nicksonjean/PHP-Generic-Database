<?php

use GenericDatabase\Engine\SQLiteEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$sqlite = SQLiteEngine::new('../../assets/YAML/sqlite.yaml')->connect();

var_dump($sqlite);
