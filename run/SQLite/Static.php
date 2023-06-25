<?php

use
  GenericDatabase\Engine\SQLiteEngine,
  GenericDatabase\Engine\SQLite\SQLite;

require_once __DIR__ . '/../../vendor/autoload.php';

$sqlite = SQLiteEngine::new('../../assets/DB.SQLITE', 'utf8', [
  SQLite::ATTR_OPEN_READONLY => false,
  SQLite::ATTR_OPEN_READWRITE => true,
  SQLite::ATTR_OPEN_CREATE => true,
  SQLite::ATTR_CONNECT_TIMEOUT => 28800,
  SQLite::ATTR_PERSISTENT => true,
  SQLite::ATTR_AUTOCOMMIT => true
], true)->connect();

// $sqlite->loadFromFile('../../tests/test.sql');

var_dump($sqlite);
