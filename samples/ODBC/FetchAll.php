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

var_dump($testA->getAllMetadata());

var_dump($testA->fetchAll(Connection::FETCH_BOTH));

echo '<hr>';

$contextB = Chainable::odbcPgSQL(env: $_ENV, persistent: true, strategy: false)->connect();

$testB = $contextB->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :idA AND id <= :idB',
    [':idA' => 5, ':idB' => 10]
);

var_dump($testB);

var_dump($testB->getAllMetadata());

var_dump($testB->fetchAll(Connection::FETCH_BOTH));

echo '<hr>';

$contextC = Chainable::odbcSQLSrv(env: $_ENV, persistent: true, strategy: false)->connect();

$testC = $contextC->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id = :id', '27');

var_dump($testC);

var_dump($testC->getAllMetadata());

var_dump($testC->fetchAll(Connection::FETCH_BOTH));

echo '<hr>';

$contextD = Chainable::odbcOCI(env: $_ENV, persistent: true, strategy: false)->connect();

$testD = $contextD->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id IN(:idA, :idB, :idC)',
    '25',
    '26',
    '27'
);

var_dump($testD);

var_dump($testD->getAllMetadata());

var_dump($testD->fetchAll(Connection::FETCH_BOTH));

echo '<hr>';

$contextE = Chainable::odbcFirebird(env: $_ENV, persistent: true, strategy: false)->connect();

$testE = $contextE->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id');

var_dump($testE);

var_dump($testE->getAllMetadata());

var_dump($testE->fetchAll(Connection::FETCH_BOTH));

echo '<hr>';

$contextF = Chainable::odbcSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

$testF = $contextF->query(
    'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id NOT IN(25, 26, 27) ORDER BY id'
);

var_dump($testF);

var_dump($testF->getAllMetadata());

var_dump($testF->fetchAll(Connection::FETCH_BOTH));

echo '<hr>';

if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

    $contextG = Chainable::odbcAccess(env: $_ENV, persistent: true, strategy: false)->connect();

    $testG = $contextG->query(
        'SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM estado WHERE id NOT IN(25, 26, 27) ORDER BY id'
    );

    var_dump($testG);

    var_dump($testG->getAllMetadata());

    var_dump($testG->fetchAll(Connection::FETCH_BOTH));

    echo '<hr>';

    $contextH = Chainable::odbcExcel(env: $_ENV, persistent: true, strategy: false)->connect();

    $testH = $contextH->query(
        'SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM [estado$] WHERE id NOT IN(25, 26, 27) ORDER BY id'
    );

    var_dump($testH);

    var_dump($testH->getAllMetadata());

    var_dump($testH->fetchAll(Connection::FETCH_BOTH));

    echo '<hr>';

    $contextI = Chainable::odbcText(env: $_ENV, persistent: true, strategy: false)->connect();

    $testI = $contextI->query(
        'SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM [estado.csv] WHERE id NOT IN(25, 26, 27) ORDER BY id'
    );

    var_dump($testI);

    var_dump($testI->getAllMetadata());

    var_dump($testI->fetchAll(Connection::FETCH_BOTH));

}
