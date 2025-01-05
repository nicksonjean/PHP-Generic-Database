<?php

use GenericDatabase\Modules\StaticArgs;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

try {
    $context = StaticArgs::odbcMySQL(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::odbcPgSQL(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::odbcSQLSrv(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::odbcOCI(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::odbcFirebird(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::odbcAccess(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

    try {
        $context = StaticArgs::odbcExcel(env: $_ENV, persistent: true, strategy: false)->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

    try {
        $context = StaticArgs::odbcText(env: $_ENV, persistent: true, strategy: false)->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

}

try {
    $context = StaticArgs::odbcSQLite(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArgs::odbcMemory(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}
