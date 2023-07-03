<?php

use
    GenericDatabase\Engine\PgSQLEngine,

    GenericDatabase\Engine\PgSQL\PgSQL;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$pgsql = PgSQLEngine::new($_ENV['PGSQL_HOST'], +$_ENV['PGSQL_PORT'], $_ENV['PGSQL_DATABASE'], $_ENV['PGSQL_USER'], $_ENV['PGSQL_PASSWORD'], 'utf8', [
    PgSQL::ATTR_PERSISTENT => true,
    PgSQL::ATTR_CONNECT_ASYNC => true,
    PgSQL::ATTR_CONNECT_FORCE_NEW => true,
    PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
], true)->connect();

var_dump($pgsql);
