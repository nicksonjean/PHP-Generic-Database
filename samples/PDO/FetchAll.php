<?php

use Dotenv\Dotenv;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$contextA = Chainable::pdoMySQL(env: $_ENV, persistent: true, strategy: false)->connect();

$testA = $contextA->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :idA AND id <= :idB',
    [':idA' => 1, ':idB' => rand(2, 27)]
);

var_dump($testA);

var_dump($testA->getAllMetadata());

var_dump([
    $testA->getQueryString(),
    $testA->getQueryParameters(),
    $testA->getQueryRows(),
    $testA->getQueryColumns(),
    $testA->getAffectedRows()
]);

var_dump(
    $testA->fetchAll(Connection::FETCH_BOTH)
);

echo '<hr>';

$contextB = Chainable::pdoPgSQL(env: $_ENV, persistent: true, strategy: false)->connect();

$testB = $contextB->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :idA AND id <= :idB',
    [':idA' => 5, ':idB' => 10]
);

var_dump($testB);

var_dump($testB->getAllMetadata());

var_dump([
    $testB->getQueryString(),
    $testB->getQueryParameters(),
    $testB->getQueryRows(),
    $testB->getQueryColumns(),
    $testB->getAffectedRows()
]);

var_dump(
    $testB->fetchAll(Connection::FETCH_BOTH)
);

echo '<hr>';

$contextC = Chainable::pdoSQLSrv(env: $_ENV, strategy: false)->connect();

$testC = $contextC->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id = :id', '27');

var_dump($testC);

var_dump($testC->getAllMetadata());

var_dump([
    $testC->getQueryString(),
    $testC->getQueryParameters(),
    $testC->getQueryRows(),
    $testC->getQueryColumns(),
    $testC->getAffectedRows()
]);

var_dump(
    $testC->fetchAll(Connection::FETCH_BOTH)
);

echo '<hr>';

$contextD = Chainable::pdoOCI(env: $_ENV, persistent: true, strategy: false)->connect();

$testD = $contextD->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id IN(:idA, :idB, :idC)',
    '25',
    '26',
    '27'
);

var_dump($testD);

var_dump($testD->getAllMetadata());

var_dump([
    $testD->getQueryString(),
    $testD->getQueryParameters(),
    $testD->getQueryRows(),
    $testD->getQueryColumns(),
    $testD->getAffectedRows()
]);

var_dump(
    $testD->fetchAll(Connection::FETCH_BOTH)
);

echo '<hr>';

$contextE = Chainable::pdoFirebird(env: $_ENV, persistent: true, strategy: false)->connect();

$testE = $contextE->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id');

var_dump($testE);

var_dump($testE->getAllMetadata());

var_dump([
    $testE->getQueryString(),
    $testE->getQueryParameters(),
    $testE->getQueryRows(),
    $testE->getQueryColumns(),
    $testE->getAffectedRows()
]);

var_dump(
    $testE->fetchAll(Connection::FETCH_BOTH)
);

echo '<hr>';

$contextF = Chainable::pdoSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

$testF = $contextF->query(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id NOT IN(25, 26, 27) ORDER BY id'
);

var_dump($testF);

var_dump($testF->getAllMetadata());

var_dump([
    $testF->getQueryString(),
    $testF->getQueryParameters(),
    $testF->getQueryRows(),
    $testF->getQueryColumns(),
    $testF->getAffectedRows()
]);

var_dump(
    $testF->fetchAll(Connection::FETCH_BOTH)
);

echo '<hr>';
