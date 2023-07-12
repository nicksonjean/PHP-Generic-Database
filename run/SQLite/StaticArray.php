<?php

use
    GenericDatabase\Engine\SQLiteEngine,

    GenericDatabase\Engine\SQLite\SQLite;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$sqlite = SQLiteEngine::new([
    'database' => $_ENV['SQLITE_DATABASE'],
    'charset' => 'utf8',
    'options' => [
        SQLite::ATTR_OPEN_READONLY => false,
        SQLite::ATTR_OPEN_READWRITE => true,
        SQLite::ATTR_OPEN_CREATE => true,
        SQLite::ATTR_CONNECT_TIMEOUT => 28800,
        SQLite::ATTR_PERSISTENT => true,
        SQLite::ATTR_AUTOCOMMIT => true
    ],
    'exception' => true
])->connect();

var_dump($sqlite);
