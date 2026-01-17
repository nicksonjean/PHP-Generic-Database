<?php

use Dotenv\Dotenv;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;
use GenericDatabase\QueryBuilder;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$context = Chainable::nativeMySQLi(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::nativePgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::nativeSQLSrv(env: $_ENV, strategy: true)->connect();
// $context = Chainable::nativeOCI(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::nativeFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::nativeSQLite(env: $_ENV, persistent: true, strategy: true)->connect();

// $context = Chainable::pdoMySQL(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::pdoSQLSrv(env: $_ENV, strategy: true)->connect();
// $context = Chainable::pdoPgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::pdoOCI(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::pdoFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::pdoSQLite(env: $_ENV, persistent: true, strategy: true)->connect();

// $context = Chainable::odbcMySQL(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::odbcPgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::odbcSQLSrv(env: $_ENV, strategy: true)->connect();
// $context = Chainable::odbcOCI(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::odbcFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::odbcSQLite(env: $_ENV, persistent: true, strategy: true)->connect();

$test0 = (new QueryBuilder($context))->select(['e.id AS Codigo', 'e.nome AS Estado', 'e.sigla AS Sigla'])
    ->from(['estado e'])
    ->where(['e.id >= 25']);

var_dump($test0);
var_dump($test0->build());
var_dump($test0->buildRaw());
var_dump($test0->getAllMetadata());

echo 'INI';
while ($row = $test0->fetch(Connection::FETCH_OBJ)) {
    var_dump($row);
}

echo '<hr />';

while ($row = $test0->fetch(Connection::FETCH_INTO)) {
    var_dump($row);
}

echo '<hr />';

while ($row = $test0->fetch(Connection::FETCH_CLASS)) {
    var_dump($row);
}

echo '<hr />';

while ($row = $test0->fetch(Connection::FETCH_COLUMN)) {
    var_dump($row);
}

echo '<hr />';

while ($row = $test0->fetch(Connection::FETCH_ASSOC)) {
    var_dump($row);
}

echo '<hr />';

while ($row = $test0->fetch(Connection::FETCH_NUM)) {
    var_dump($row);
}

echo '<hr />';

while ($row = $test0->fetch(Connection::FETCH_BOTH)) {
    var_dump($row);
}

echo 'FIM';
echo '<hr />';
echo 'INI';

var_dump($test0->fetchAll(Connection::FETCH_OBJ));
echo '<hr />';

var_dump($test0->fetchAll(Connection::FETCH_CLASS));
echo '<hr />';

var_dump($test0->fetchAll(Connection::FETCH_COLUMN));
echo '<hr />';

var_dump($test0->fetchAll(Connection::FETCH_ASSOC));
echo '<hr />';

var_dump($test0->fetchAll(Connection::FETCH_NUM));
echo '<hr />';

var_dump($test0->fetchAll(Connection::FETCH_BOTH));
echo '<hr />';

echo 'FIM';
