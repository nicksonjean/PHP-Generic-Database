<?php

use GenericDatabase\Runner\Chainable;

use Dotenv\Dotenv;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$status = 'Connection Status: %s';

$withStrategy = Chainable::nativeMySQLi(env: $_ENV, strategy: true, persistent: true);

// Connectar

$withStrategy->connect();

var_dump($withStrategy);

var_dump(sprintf($status, $withStrategy->isConnected() ? 'true' : 'false'));

// Desconnectar

$withStrategy->disconnect();

var_dump($withStrategy);

var_dump(sprintf($status, $withStrategy->isConnected() ? 'true' : 'false'));

// Reconnectar

$withStrategy->connect();

var_dump($withStrategy);

var_dump(sprintf($status, $withStrategy->isConnected() ? 'true' : 'false'));



$withoutStrategy = Chainable::nativeMySQLi(env: $_ENV, strategy: false, persistent: true);

// Connectar

$withoutStrategy->connect();

var_dump($withoutStrategy);

var_dump(sprintf($status, $withoutStrategy->isConnected() ? 'true' : 'false'));

// Desconnectar

$withoutStrategy->disconnect();

var_dump($withoutStrategy);

var_dump(sprintf($status, $withoutStrategy->isConnected() ? 'true' : 'false'));

// Reconnectar

$withoutStrategy->connect();

var_dump($withoutStrategy);

var_dump(sprintf($status, $withoutStrategy->isConnected() ? 'true' : 'false'));
