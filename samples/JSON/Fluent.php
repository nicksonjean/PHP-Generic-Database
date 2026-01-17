<?php

use GenericDatabase\Modules\Fluent;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

try {
    $context = Fluent::nativeJSON(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

// try {
//     $context = Fluent::nativeMemory(env: $_ENV, persistent: true, strategy: false)->connect();
//     var_dump($context);
// } catch (Exception $e) {
//     var_dump($e);
// }
