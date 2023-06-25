<?php

use
  GenericDatabase\Engine\SQLiteEngine,
  GenericDatabase\Engine\SQLite\SQLite;

require_once __DIR__ . '/../../vendor/autoload.php';

$sqlite = SQLiteEngine
  ::setDatabase('../../assets/DB.SQLITE')
  ::setCharset('utf8')
  ::setOptions([
    SQLite::ATTR_OPEN_READONLY => false,
    SQLite::ATTR_OPEN_READWRITE => true,
    SQLite::ATTR_OPEN_CREATE => true,
    SQLite::ATTR_CONNECT_TIMEOUT => 28800,
    SQLite::ATTR_PERSISTENT => true,
    SQLite::ATTR_AUTOCOMMIT => true
  ])
  ::setException(true)
  ->connect();

var_dump($sqlite);
