<?php

use Dotenv\Dotenv;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$context = Chainable::nativePgSQL(env: $_ENV, persistent: true, strategy: false)->connect();

$testA = $context->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :id',
    [':id' => 10]
);

var_dump($testA);

var_dump($testA->getAllMetadata());

var_dump($testA->fetchAll(Connection::FETCH_BOTH));

echo '<hr>';

$testB = $context->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :idA AND id <= :idB',
    [':idA' => 5, ':idB' => 10]
);

var_dump($testB);

var_dump($testB->getAllMetadata());

var_dump($testB->fetchAll(Connection::FETCH_BOTH));

echo '<hr>';

$testC = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id = :id', '27');

var_dump($testC);

var_dump($testC->getAllMetadata());

var_dump($testC->fetchAll(Connection::FETCH_BOTH));

echo '<hr>';

$testD = $context->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id IN(:idA, :idB, :idC)',
    '25',
    '26',
    '27'
);

var_dump($testD);

var_dump($testD->getAllMetadata());

var_dump($testD->fetchAll(Connection::FETCH_BOTH));

echo '<hr>';

$testE = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id');

var_dump($testE);

var_dump($testE->getAllMetadata());

var_dump($testE->fetchAll(Connection::FETCH_BOTH));

echo '<hr>';

$testF = $context->query(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id NOT IN(25, 26, 27) ORDER BY id'
);

var_dump($testF);

var_dump($testF->getAllMetadata());

var_dump($testF->fetchAll(Connection::FETCH_BOTH));
