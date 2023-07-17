<?php

use GenericDatabase\Runner\Chainable;

use Dotenv\Dotenv;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$context = Chainable::nativeMySQLi(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Chainable::nativePgSQL(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Chainable::nativeSQLSrv(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Chainable::nativeOCI(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Chainable::nativeFBird(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Chainable::nativeSQLite(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Chainable::nativeMemory(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Chainable::pdoMySQL(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Chainable::pdoPgSQL(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Chainable::pdoSQLSrv(env: $_ENV, strategy: true)->connect();

var_dump($context);

$context = Chainable::pdoOCI(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Chainable::pdoFirebird(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Chainable::pdoSQLite(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);

$context = Chainable::pdoMemory(env: $_ENV, strategy: true, persistent: true)->connect();

var_dump($context);
