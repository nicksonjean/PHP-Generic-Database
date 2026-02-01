<?php

use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

try {
    $context = Chainable::nativeYAML(env: $_ENV, persistent: true, strategy: false)->connect();
    var_dump($context);

    // $a = $context->getDatabase();
    // var_dump($a);

    // $b = $context->getDsn();
    // var_dump($b);

    // $c = $context->getTables();
    // var_dump($c);

    // $d = $context->getSchema();
    // var_dump($d);

    // $e = $context->getSchema()?->getFile();
    // var_dump($e);

    // $f = $context->getSchema()?->getData();
    // var_dump($f);

    // $g = $context->getStructure()?->getSchema()?->getFile();
    // var_dump($g);

    // $h = $context->getStructure()?->getSchema()?->getData();
    // var_dump($h);

    // $i = $context->getStructure();
    // var_dump($i);

} catch (Exception $e) {
    var_dump($e);
}
