<?php

use
    GenericDatabase\Engine\SQLiteEngine,

    GenericDatabase\Engine\SQLite\SQLite;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$sqlite = new SQLiteEngine();
$sqlite->setDatabase($_ENV['SQLITE_DATABASE'])
    ->setCharset('utf8')
    ->setOptions([
        SQLite::ATTR_OPEN_READONLY => false,
        SQLite::ATTR_OPEN_READWRITE => true,
        SQLite::ATTR_OPEN_CREATE => true,
        SQLite::ATTR_CONNECT_TIMEOUT => 28800,
        SQLite::ATTR_PERSISTENT => true,
        SQLite::ATTR_AUTOCOMMIT => true
    ])
    ->setException(true)
    ->connect();

var_dump($sqlite);
