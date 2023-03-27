<?php

use
  GenericDatabase\Engine\PgSQLEngine,
  GenericDatabase\Engine\PgSQL\PgSQL;

require_once __DIR__ . '/../../vendor/autoload.php';

$pgsql = new PgSQLEngine();
$pgsql->setHost('localhost')
  ->setPort(5432)
  ->setDatabase('postgres')
  ->setUser('postgres')
  ->setPassword('masterkey')
  ->setCharset('utf8')
  ->setOptions([
    PgSQL::ATTR_PERSISTENT => true,
    PgSQL::ATTR_CONNECT_ASYNC => true,
    PgSQL::ATTR_CONNECT_FORCE_NEW => true,
    PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
  ])
  ->setException(true)
  ->connect();

var_dump($pgsql);
