<?php

use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

// try {
//     $context = Chainable::odbcMySQL(env: $_ENV, persistent: true, strategy: false)->connect();
//     var_dump($context);
// } catch (Exception $e) {
//     var_dump($e);
// }

// try {
//     $context = Chainable::odbcPgSQL(env: $_ENV, persistent: true, strategy: false)->connect();
//     var_dump($context);
// } catch (Exception $e) {
//     var_dump($e);
// }

// try {
//     $context = Chainable::odbcSQLSrv(env: $_ENV, persistent: true, strategy: false)->connect();
//     var_dump($context);
// } catch (Exception $e) {
//     var_dump($e);
// }

// try {
//     $context = Chainable::odbcOCI(env: $_ENV, persistent: true, strategy: false)->connect();
//     var_dump($context);
// } catch (Exception $e) {
//     var_dump($e);
// }

// try {
//     $context = Chainable::odbcFirebird(env: $_ENV, persistent: true, strategy: false)->connect();
//     var_dump($context);
// } catch (Exception $e) {
//     var_dump($e);
// }

try {
    $context = Chainable::odbcAccess(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);
} catch (Exception $e) {
    var_dump($e);
}

// try {
//     $context = Chainable::odbcExcel(env: $_ENV, persistent: true, strategy: false)->connect();
//     var_dump($context);
// } catch (Exception $e) {
//     var_dump($e);
// }

// try {
//     $context = Chainable::odbcText(env: $_ENV, persistent: true, strategy: false)->connect();
//     var_dump($context);
// } catch (Exception $e) {
//     var_dump($e);
// }

// try {
//     $context = Chainable::odbcSQLite(env: $_ENV, persistent: true, strategy: false)->connect();
//     var_dump($context);
// } catch (Exception $e) {
//     var_dump($e);
// }

// try {
//     $context = Chainable::odbcMemory(env: $_ENV, persistent: true, strategy: false)->connect();
//     var_dump($context);
// } catch (Exception $e) {
//     var_dump($e);
// }
