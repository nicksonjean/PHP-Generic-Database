<?php

use Dotenv\Dotenv;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$contextA = Chainable::pdoMySQL(env: $_ENV, persistent: true, strategy: false)->connect();

$testA = $contextA->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :id',
    [':id' => 10]
);

var_dump($testA);

var_dump($testA->getAllMetadata());

var_dump([
    $testA->queryString(),
    $testA->queryParameters(),
    $testA->queryRows(),
    $testA->queryColumns(),
    $testA->affectedRows()
]);

var_dump($testA->fetchAll(Connection::FETCH_BOTH));

$contextB = Chainable::pdoPgSQL(env: $_ENV, persistent: true, strategy: false)->connect();

$testB = $contextB->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :idA AND id <= :idB',
    [':idA' => 5, ':idB' => 10]
);

var_dump($testB);

var_dump($testB->getAllMetadata());

var_dump([
    $testB->queryString(),
    $testB->queryParameters(),
    $testB->queryRows(),
    $testB->queryColumns(),
    $testB->affectedRows()
]);

var_dump($testB->fetchAll(Connection::FETCH_BOTH));

$contextC = Chainable::pdoSQLSrv(env: $_ENV, strategy: false)->connect();

$testC = $contextC->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id = :id', '27');

var_dump($testC);

var_dump($testC->getAllMetadata());

var_dump([
    $testC->queryString(),
    $testC->queryParameters(),
    $testC->queryRows(),
    $testC->queryColumns(),
    $testC->affectedRows()
]);

var_dump($testC->fetchAll(Connection::FETCH_BOTH));

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
    $testD->queryString(),
    $testD->queryParameters(),
    $testD->queryRows(),
    $testD->queryColumns(),
    $testD->affectedRows()
]);

var_dump($testD->fetchAll(Connection::FETCH_BOTH));

$contextE = Chainable::pdoFirebird(env: $_ENV, persistent: true, strategy: false)->connect();

$testE = $contextE->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id');

var_dump($testE);

var_dump($testE->getAllMetadata());

var_dump([
    $testE->queryString(),
    $testE->queryParameters(),
    $testE->queryRows(),
    $testE->queryColumns(),
    $testE->affectedRows()
]);

var_dump($testE->fetchAll(Connection::FETCH_BOTH));

$contextF = Chainable::pdoSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

$testF = $contextF->query(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id NOT IN(25, 26, 27) ORDER BY id'
);

var_dump($testF);

var_dump($testF->getAllMetadata());

var_dump([
    $testF->queryString(),
    $testF->queryParameters(),
    $testF->queryRows(),
    $testF->queryColumns(),
    $testF->affectedRows()
]);

var_dump($testF->fetchAll(Connection::FETCH_BOTH));
