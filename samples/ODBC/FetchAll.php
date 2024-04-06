<?php

use Dotenv\Dotenv;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$contextA = Chainable::odbcMySQL(env: $_ENV, persistent: true, strategy: false)->connect();

$testA = $contextA->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :id',
    [':id' => 10]
);

var_dump($testA);

var_dump($testA->queryMetadata());

var_dump([
    $testA->queryString(),
    $testA->queryParameters(),
    $testA->queryRows(),
    $testA->queryColumns(),
    $testA->affectedRows()
]);

var_dump($testA->fetchAll(Connection::FETCH_BOTH));

$contextB = Chainable::odbcPgSQL(env: $_ENV, persistent: true, strategy: false)->connect();

$testB = $contextB->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :idA AND id <= :idB',
    [':idA' => 5, ':idB' => 10]
);

var_dump($testB);

var_dump($testB->queryMetadata());

var_dump([
    $testB->queryString(),
    $testB->queryParameters(),
    $testB->queryRows(),
    $testB->queryColumns(),
    $testB->affectedRows()
]);

var_dump($testB->fetchAll(Connection::FETCH_BOTH));

$contextC = Chainable::odbcSQLSrv(env: $_ENV, persistent: true, strategy: false)->connect();

$testC = $contextC->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id = :id', '27');

var_dump($testC);

var_dump($testC->queryMetadata());

var_dump([
    $testC->queryString(),
    $testC->queryParameters(),
    $testC->queryRows(),
    $testC->queryColumns(),
    $testC->affectedRows()
]);

var_dump($testC->fetchAll(Connection::FETCH_BOTH));

$contextD = Chainable::odbcOCI(env: $_ENV, persistent: true, strategy: false)->connect();

$testD = $contextD->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id IN(:idA, :idB, :idC)',
    '25',
    '26',
    '27'
);

var_dump($testD);

var_dump($testD->queryMetadata());

var_dump([
    $testD->queryString(),
    $testD->queryParameters(),
    $testD->queryRows(),
    $testD->queryColumns(),
    $testD->affectedRows()
]);

var_dump($testD->fetchAll(Connection::FETCH_BOTH));

$contextE = Chainable::odbcFirebird(env: $_ENV, persistent: true, strategy: false)->connect();

$testE = $contextE->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id');

var_dump($testE);

var_dump($testE->queryMetadata());

var_dump([
    $testE->queryString(),
    $testE->queryParameters(),
    $testE->queryRows(),
    $testE->queryColumns(),
    $testE->affectedRows()
]);

var_dump($testE->fetchAll(Connection::FETCH_BOTH));

$contextF = Chainable::odbcSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

$testF = $contextF->query(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id NOT IN(25, 26, 27) ORDER BY id'
);

var_dump($testF);

var_dump($testF->queryMetadata());

var_dump([
    $testF->queryString(),
    $testF->queryParameters(),
    $testF->queryRows(),
    $testF->queryColumns(),
    $testF->affectedRows()
]);

var_dump($testF->fetchAll(Connection::FETCH_BOTH));

$contextG = Chainable::odbcAccess(env: $_ENV, persistent: true, strategy: false)->connect();

$testG = $contextG->query(
    'SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM estado WHERE id NOT IN(25, 26, 27) ORDER BY id'
);

var_dump($testG);

var_dump($testG->queryMetadata());

var_dump([
    $testG->queryString(),
    $testG->queryParameters(),
    $testG->queryRows(),
    $testG->queryColumns(),
    $testG->affectedRows()
]);

var_dump($testG->fetchAll(Connection::FETCH_BOTH));

$contextH = Chainable::odbcExcel(env: $_ENV, persistent: true, strategy: false)->connect();

$testH = $contextH->query(
    'SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM [estado$] WHERE id NOT IN(25, 26, 27) ORDER BY id'
);

var_dump($testH);

var_dump($testH->queryMetadata());

var_dump([
    $testH->queryString(),
    $testH->queryParameters(),
    $testH->queryRows(),
    $testH->queryColumns(),
    $testH->affectedRows()
]);

var_dump($testH->fetchAll(Connection::FETCH_BOTH));

$contextI = Chainable::odbcText(env: $_ENV, persistent: true, strategy: false)->connect();

$testI = $contextI->query(
    'SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM [estado.csv] WHERE id NOT IN(25, 26, 27) ORDER BY id'
);

var_dump($testI);

var_dump($testI->queryMetadata());

var_dump([
    $testI->queryString(),
    $testI->queryParameters(),
    $testI->queryRows(),
    $testI->queryColumns(),
    $testI->affectedRows()
]);

var_dump($testI->fetchAll(Connection::FETCH_BOTH));
