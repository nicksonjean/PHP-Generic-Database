<?php

use GenericDatabase\Runner\StaticArray;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

try {
    $context = StaticArray::nativeMySQLi(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::nativePgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::nativeSQLSrv(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::nativeOCI(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::nativeFBird(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::nativeSQLite(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::nativeMemory(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::pdoMySQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::pdoPgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::pdoSQLSrv(env: $_ENV, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::pdoOCI(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::pdoFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::pdoSQLite(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::pdoMemory(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}
