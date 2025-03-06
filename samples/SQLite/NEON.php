<?php

use GenericDatabase\Engine\SQLiteConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$sqlite = SQLiteConnection::new(PATH_ROOT . '/resources/dsn/neon/sqlite.neon')->connect();

var_dump($sqlite);
