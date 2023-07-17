<?php

use GenericDatabase\Runner\Fluent;

use Dotenv\Dotenv;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$context = Fluent::nativeMySQLi(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Fluent::nativePgSQL(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Fluent::nativeSQLSrv(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Fluent::nativeOCI(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Fluent::nativeFBird(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Fluent::nativeSQLite(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Fluent::nativeMemory(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Fluent::pdoMySQL(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Fluent::pdoPgSQL(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Fluent::pdoSQLSrv(env: $_ENV, strategy: true)->connect();

var_dump($context);

$context = Fluent::pdoOCI(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Fluent::pdoFirebird(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Fluent::pdoSQLite(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Fluent::pdoMemory(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);
