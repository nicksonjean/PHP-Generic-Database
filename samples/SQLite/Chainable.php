<?php

use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

try {
    $context = Chainable::nativeSQLite(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

try {
    $context = Chainable::nativeMemory(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}
