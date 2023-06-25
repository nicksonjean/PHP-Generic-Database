<?php

use GenericDatabase\Engine\SQLiteEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$sqlite = SQLiteEngine::new('../../assets/XML/sqlite.xml')->connect();

var_dump($sqlite);
