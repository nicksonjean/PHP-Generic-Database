<?php

use
  GenericDatabase\Engine\PgSQLEngine,
  GenericDatabase\Engine\PgSQL\PgSQL;

require_once __DIR__ . '/../../vendor/autoload.php';

$pgsql = PgSQLEngine::new('localhost', 5432, 'postgres', 'postgres', 'masterkey', 'utf8', [
  PgSQL::ATTR_PERSISTENT => true,
  PgSQL::ATTR_CONNECT_ASYNC => true,
  PgSQL::ATTR_CONNECT_FORCE_NEW => true,
  PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
], true)->connect();

// $pgsql->loadFromFile('../../tests/test.sql');

var_dump($pgsql);
