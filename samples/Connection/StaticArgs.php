<?php

use GenericDatabase\Modules\StaticArgs;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

try {
    $context = StaticArgs::nativeMySQLi(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::nativePgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::nativeSQLSrv(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::nativeOCI(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::nativeFBird(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::nativeSQLite(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::nativeMemory(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::pdoMySQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::pdoPgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::pdoSQLSrv(env: $_ENV, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::pdoOCI(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::pdoFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::pdoSQLite(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::pdoMemory(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}
