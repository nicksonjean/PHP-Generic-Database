<?php

use GenericDatabase\Modules\StaticArray;
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

if (extension_loaded('interbase')) {

    try {
        $context = StaticArray::nativeFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

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

try {
    $context = StaticArray::odbcMySQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::odbcPgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::odbcSQLSrv(env: $_ENV, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::odbcOCI(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::odbcFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::odbcAccess(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

    try {
        $context = StaticArray::odbcExcel(env: $_ENV, persistent: true, strategy: true)->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

    try {
        $context = StaticArray::odbcText(env: $_ENV, persistent: true, strategy: true)->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

}

try {
    $context = StaticArray::odbcSQLite(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = StaticArray::odbcMemory(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}
