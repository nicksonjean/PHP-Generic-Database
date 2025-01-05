<?php

use GenericDatabase\Modules\Fluent;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

try {
    $context = Fluent::odbcMySQL(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::odbcPgSQL(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::odbcSQLSrv(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::odbcOCI(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::odbcFirebird(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::odbcAccess(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

    try {
        $context = Fluent::odbcExcel(env: $_ENV, persistent: true, strategy: false)->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

    try {
        $context = Fluent::odbcText(env: $_ENV, persistent: true, strategy: false)->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

}

try {
    $context = Fluent::odbcSQLite(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::odbcMemory(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}
