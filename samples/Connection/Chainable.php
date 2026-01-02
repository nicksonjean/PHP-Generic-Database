<?php

use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

try {
    $context = Chainable::nativeMySQLi(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::nativePgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::nativeSQLSrv(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::nativeOCI(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

if (extension_loaded('interbase')) {

    try {
        $context = Chainable::nativeFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

}

try {
    $context = Chainable::nativeSQLite(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::nativeMemory(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::pdoMySQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::pdoPgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::pdoSQLSrv(env: $_ENV, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::pdoOCI(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::pdoFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::pdoSQLite(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::pdoMemory(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::odbcMySQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::odbcPgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::odbcSQLSrv(env: $_ENV, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::odbcOCI(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::odbcFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::odbcAccess(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

    try {
        $context = Chainable::odbcExcel(env: $_ENV, persistent: true, strategy: true)->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

    try {
        $context = Chainable::odbcText(env: $_ENV, persistent: true, strategy: true)->connect();
        var_dump($context);
    } catch (Exception $e) {
        var_dump($e);
    }

}

try {
    $context = Chainable::odbcSQLite(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::odbcMemory(env: $_ENV, persistent: true, strategy: true)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}
