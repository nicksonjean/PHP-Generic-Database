<?php

use
    GenericDatabase\Engine\PgSQLEngine,

    GenericDatabase\Engine\PgSQL\PgSQL;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$pgsql = new PgSQLEngine();
$pgsql->setHost($_ENV['PGSQL_HOST'])
    ->setPort(+$_ENV['PGSQL_PORT'])
    ->setDatabase($_ENV['PGSQL_DATABASE'])
    ->setUser($_ENV['PGSQL_USER'])
    ->setPassword($_ENV['PGSQL_PASSWORD'])
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
