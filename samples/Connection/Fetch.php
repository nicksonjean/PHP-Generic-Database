<?php

use Dotenv\Dotenv;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$contextA = Chainable::nativeMySQLi(env: $_ENV, persistent: true, strategy: true)->connect();

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

while ($row = $testA->fetch(Connection::FETCH_BOTH)) {
    var_dump($row);
}

echo '<hr>';

$contextB = Chainable::pdoMySQL(env: $_ENV, persistent: true, strategy: true)->connect();

$testB = $contextB->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE nome LIKE :nome',
    [':nome' => ['%Rio%', '%Mato%', '%Sant%'][array_rand(['%Rio%', '%Mato%', '%Sant%'])]]
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

while ($row = $testB->fetch(Connection::FETCH_BOTH)) {
    var_dump($row);
}

echo '<hr>';

$contextC = Chainable::pdoPgSQL(env: $_ENV, persistent: true, strategy: true)->connect();

$testC = $contextC->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :idA AND id <= :idB',
    [':idA' => 5, ':idB' => 10]
);

var_dump($testC);

var_dump($testC->getAllMetadata());

var_dump([
    $testC->getQueryString(),
    $testC->getQueryParameters(),
    $testC->getQueryRows(),
    $testC->getQueryColumns(),
    $testC->getAffectedRows()
]);

while ($row = $testC->fetch(Connection::FETCH_BOTH)) {
    var_dump($row);
}

echo '<hr>';

$contextD = Chainable::pdoSQLSrv(env: $_ENV, strategy: true)->connect();

$testD = $contextD->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id = :id', '27');

var_dump($testD);

var_dump($testD->getAllMetadata());

var_dump([
    $testD->getQueryString(),
    $testD->getQueryParameters(),
    $testD->getQueryRows(),
    $testD->getQueryColumns(),
    $testD->getAffectedRows()
]);

while ($row = $testD->fetch(Connection::FETCH_BOTH)) {
    var_dump($row);
}

echo '<hr>';

$contextE = Chainable::pdoOCI(env: $_ENV, persistent: true, strategy: true)->connect();

$testE = $contextE->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id IN(:idA, :idB, :idC)',
    '25',
    '26',
    '27'
);

var_dump($testE);

var_dump($testE->getAllMetadata());

var_dump([
    $testE->getQueryString(),
    $testE->getQueryParameters(),
    $testE->getQueryRows(),
    $testE->getQueryColumns(),
    $testE->getAffectedRows()
]);

while ($row = $testE->fetch(Connection::FETCH_BOTH)) {
    var_dump($row);
}

echo '<hr>';

$contextF = Chainable::pdoFirebird(env: $_ENV, persistent: true, strategy: true)->connect();

$testF = $contextF->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id');

var_dump($testF);

var_dump($testF->getAllMetadata());

var_dump([
    $testF->getQueryString(),
    $testF->getQueryParameters(),
    $testF->getQueryRows(),
    $testF->getQueryColumns(),
    $testF->getAffectedRows()
]);

while ($row = $testF->fetch(Connection::FETCH_BOTH)) {
    var_dump($row);
}

echo '<hr>';

$contextG = Chainable::pdoSQLite(env: $_ENV, persistent: true, strategy: true)->connect();

$testG = $contextG->query(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id NOT IN(25, 26, 27) ORDER BY id'
);

var_dump($testG);

var_dump($testG->getAllMetadata());

var_dump([
    $testG->getQueryString(),
    $testG->getQueryParameters(),
    $testG->getQueryRows(),
    $testG->getQueryColumns(),
    $testG->getAffectedRows()
]);

while ($row = $testG->fetch(Connection::FETCH_BOTH)) {
    var_dump($row);
}

echo '<hr>';
