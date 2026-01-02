<?php

use GenericDatabase\Modules\Fluent;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

try {
    $context = Fluent::nativeMySQLi(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::nativePgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::nativeSQLSrv(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::nativeOCI(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

if (extension_loaded('interbase')) {

    try {
        $context = Fluent::nativeFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

}

try {
    $context = Fluent::nativeSQLite(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::nativeMemory(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::pdoMySQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::pdoPgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::pdoSQLSrv(env: $_ENV, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::pdoOCI(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::pdoFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::pdoSQLite(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::pdoMemory(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::odbcMySQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::odbcPgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::odbcSQLSrv(env: $_ENV, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::odbcOCI(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::odbcFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::odbcAccess(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

    try {
        $context = Fluent::odbcExcel(env: $_ENV, persistent: true, strategy: true)->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

    try {
        $context = Fluent::odbcText(env: $_ENV, persistent: true, strategy: true)->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

}

try {
    $context = Fluent::odbcSQLite(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Fluent::odbcMemory(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}
