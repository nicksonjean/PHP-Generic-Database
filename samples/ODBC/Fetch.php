<?php

use Dotenv\Dotenv;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$contextA = Chainable::odbcMySQL(env: $_ENV, persistent: true, strategy: false)->connect();

$testA = $contextA->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM estado WHERE id >= :idA AND id <= :idB',
    [':idA' => 1, ':idB' => rand(2, 27)]
);

var_dump($testA);

var_dump($testA->getAllMetadata());

while ($row = $testA->fetch(Connection::FETCH_BOTH)) {
    var_dump($row);
}

echo '<hr>';

$contextB = Chainable::odbcPgSQL(env: $_ENV, persistent: true, strategy: false)->connect();

$testB = $contextB->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM estado WHERE id >= :idA AND id <= :idB',
    [':idA' => 5, ':idB' => 10]
);

var_dump($testB);

var_dump($testB->getAllMetadata());

while ($row = $testB->fetch(Connection::FETCH_BOTH)) {
    var_dump($row);
}

echo '<hr>';

$contextC = Chainable::odbcSQLSrv(env: $_ENV, persistent: true, strategy: false)->connect();

$testC = $contextC->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM estado WHERE id = :id', '27');

var_dump($testC);

var_dump($testC->getAllMetadata());

while ($row = $testC->fetch(Connection::FETCH_BOTH)) {
    var_dump($row);
}

echo '<hr>';

$contextD = Chainable::odbcOCI(env: $_ENV, persistent: true, strategy: false)->connect();

$testD = $contextD->prepare(
    'SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM estado WHERE id IN(:idA, :idB, :idC)',
    '25',
    '26',
    '27'
);

var_dump($testD);

var_dump($testD->getAllMetadata());

while ($row = $testD->fetch(Connection::FETCH_BOTH)) {
    var_dump($row);
}

echo '<hr>';

$contextE = Chainable::odbcFirebird(env: $_ENV, persistent: true, strategy: false)->connect();

$testE = $contextE->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM estado ORDER BY id');

var_dump($testE);

var_dump($testE->getAllMetadata());

while ($row = $testE->fetch(Connection::FETCH_BOTH)) {
    var_dump($row);
}

echo '<hr>';

$contextF = Chainable::odbcSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

$testF = $contextF->query(
    'SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM estado WHERE id NOT IN(25, 26, 27) ORDER BY id'
);

var_dump($testF);

var_dump($testF->getAllMetadata());

while ($row = $testF->fetch(Connection::FETCH_BOTH)) {
    var_dump($row);
}

echo '<hr>';

if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

    $contextG = Chainable::odbcAccess(env: $_ENV, persistent: true, strategy: false)->connect();

    $testG = $contextG->query(
        'SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM estado WHERE id NOT IN(25, 26, 27) ORDER BY id'
    );

    var_dump($testG);

    var_dump($testG->getAllMetadata());

    while ($row = $testG->fetch(Connection::FETCH_BOTH)) {
        var_dump($row);
    }

    echo '<hr>';

    $contextH = Chainable::odbcExcel(env: $_ENV, persistent: true, strategy: false)->connect();

    $testH = $contextH->query(
        'SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM [estado$] WHERE id NOT IN(25, 26, 27) ORDER BY id'
    );

    var_dump($testH);

    var_dump($testH->getAllMetadata());

    while ($row = $testH->fetch(Connection::FETCH_BOTH)) {
        var_dump($row);
    }

    echo '<hr>';

    $contextI = Chainable::odbcText(env: $_ENV, persistent: true, strategy: false)->connect();

    $testI = $contextI->query(
        'SELECT id AS Codigo, nome AS Estado, sigla AS UF FROM [estado.csv] WHERE id NOT IN(25, 26, 27) ORDER BY id'
    );

    var_dump($testI);

    var_dump($testI->getAllMetadata());

    while ($row = $testI->fetch(Connection::FETCH_BOTH)) {
        var_dump($row);
    }

}
