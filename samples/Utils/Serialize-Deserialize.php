<?php

use GenericDatabase\Connection;
use GenericDatabase\Engine\MySQLi\Connection\MySQL;
use GenericDatabase\Modules\Chainable;
use GenericDatabase\Modules\Fluent;
use GenericDatabase\Modules\StaticArgs;
use GenericDatabase\Modules\StaticArray;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

// $connection = new Connection();
// $connection
//     ->setEngine('mysqli')
//     ->setHost($_ENV['MYSQL_HOST'])
//     ->setPort((int) $_ENV['MYSQL_PORT'])
//     ->setDatabase($_ENV['MYSQL_DATABASE'])
//     ->setUser($_ENV['MYSQL_USERNAME'])
//     ->setPassword($_ENV['MYSQL_PASSWORD'])
//     ->setCharset($_ENV['MYSQL_CHARSET'])
//     ->setOptions([
//         MySQL::ATTR_PERSISTENT => false,
//         MySQL::ATTR_AUTOCOMMIT => true,
//         MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
//         MySQL::ATTR_SET_CHARSET_NAME => "utf8",
//         MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
//         MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
//         MySQL::ATTR_OPT_READ_TIMEOUT => 30,
//         MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
//     ])
//     ->setException(true);

// $connection = new Connection();
// $connection
//     ->setHost($_ENV['MYSQL_HOST'])
//     ->setPort((int)$_ENV['MYSQL_PORT'])
//     ->setDatabase($_ENV['MYSQL_DATABASE'])
//     ->setUser($_ENV['MYSQL_USERNAME'])
//     ->setPassword($_ENV['MYSQL_PASSWORD'])
//     ->setCharset($_ENV['MYSQL_CHARSET'])
//     ->setOptions([
//         MySQL::ATTR_PERSISTENT => false,
//         MySQL::ATTR_AUTOCOMMIT => true,
//         MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
//         MySQL::ATTR_SET_CHARSET_NAME => "utf8",
//         MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
//         MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
//         MySQL::ATTR_OPT_READ_TIMEOUT => 30,
//         MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
//     ])
//     ->setException(true);

// $serialized = serialize($connection);
// $context = unserialize($serialized);

// var_dump($serialized);

// $context = Chainable::nativeMySQLi(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativePgSQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativeSQLSrv(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativeOCI(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativeFirebird(env: $_ENV, persistent: true, strategy: false)->connect();
$context = Chainable::nativeSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

// $context = Fluent::nativeMySQLi(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Fluent::nativePgSQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Fluent::nativeSQLSrv(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Fluent::nativeOCI(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Fluent::nativeFirebird(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Fluent::nativeSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

// $context = StaticArgs::nativeMySQLi(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = StaticArgs::nativePgSQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = StaticArgs::nativeSQLSrv(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = StaticArgs::nativeOCI(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = StaticArgs::nativeFirebird(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = StaticArgs::nativeSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

// $context = StaticArray::nativeMySQLi(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = StaticArray::nativePgSQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = StaticArray::nativeSQLSrv(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = StaticArray::nativeOCI(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = StaticArray::nativeFirebird(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = StaticArray::nativeSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

// $context = Chainable::pdoMySQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::pdoPgSQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::pdoSQLSrv(env: $_ENV, strategy: false)->connect();
// $context = Chainable::pdoOCI(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::pdoFirebird(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::pdoSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

// $context = Chainable::odbcMySQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::odbcPgSQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::odbcSQLSrv(env: $_ENV, strategy: false)->connect();
// $context = Chainable::odbcOCI(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::odbcFirebird(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::odbcSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

var_dump($context);

// OBJ, INTO, CLASS, COLUMN, ASSOC, NUM, BOTH

function getRandomWord(): string
{
    $words = ["Rio%", "Par%", "%Sant%"];
    return $words[array_rand($words)];
}

$o = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE nome LIKE :nome', [':nome' => getRandomWord()]);

var_dump($o->getAllMetadata());

var_dump($o->fetchAll(Connection::FETCH_COLUMN));

while ($row = $o->fetch(Connection::FETCH_COLUMN)) {
    var_dump($row);
}

$rand = mt_rand(2, 4);
$range = implode(', ', range(1, $rand));

$q = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :idA AND id <= :idB', [':idA' => 1, ':idB' => $rand]);

var_dump($q->getAllMetadata());

var_dump($q->fetchAll(Connection::FETCH_OBJ));

while ($row = $q->fetch(Connection::FETCH_OBJ)) {
    var_dump($row);
}

$r = $context->query('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id IN(' . $range . ') ORDER BY id');

var_dump($r->getAllMetadata());

while ($row = $r->fetch(Connection::FETCH_OBJ)) {
    var_dump($row);
}

var_dump($r->fetchAll(Connection::FETCH_OBJ));

try {

    $context->beginTransaction();

    $b = $context->prepare('INSERT INTO estado (nome, sigla) VALUES (:nome, :sigla)', [[':nome' => 'TESTE', ':sigla' => 'T1'], [':nome' => 'TESTE', ':sigla' => 'T2'], [':nome' => 'TESTE', ':sigla' => 'T5']]);
    var_dump($b->getAllMetadata());

    var_dump($b->lastInsertId('estado'));

    $c = $context->prepare('UPDATE estado SET sigla = :sigla WHERE nome = :nome', [':sigla' => 'T3', ':nome' => 'TESTE']);
    var_dump($c->getAllMetadata());

    $d = $context->query("UPDATE estado SET sigla = 'T4' WHERE nome = 'TESTE'");
    var_dump($d->getAllMetadata());

    $f = $context->query("DELETE FROM estado WHERE nome IN ('TESTE')");
    var_dump($f->getAllMetadata());

    $context->commit();

    var_dump("Transação completada com sucesso!");
} catch (Exception $e) {

    $context->rollback();
    var_dump("Erro na transação: " . $e->getMessage());
}

// $d = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :id', [':id' => 10]);
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id = :id', '27');
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id IN(:idA, :idB, :idC)', '25', '26', '27');
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id');

// var_dump([
//     'queryString' => $a->getQueryString(),
//     'queryParameters' => $a->getQueryParameters(),
//     'queryRows' => $a->getQueryRows(),
//     'queryColumns' => $a->getQueryColumns(),
//     'affectedRows' => $a->getAffectedRows()
// ]);

// var_dump($a->fetchAll(Connection::FETCH_OBJ));

// while ($row = $a->fetch(Connection::FETCH_OBJ)) {
//     echo vsprintf("<pre>%s, %s/%s</pre>", [$row->Codigo, $row->Estado, $row->Sigla]);
// }

// $b = $context->prepare('INSERT INTO estado (nome, sigla) VALUES (:nome, :sigla)', [[':nome' => 'TESTE1', ':sigla' => 'T1'], [':nome' => 'TESTE2', ':sigla' => 'T2']]);
// $b = $context->prepare('INSERT INTO estado (nome, sigla) VALUES (:nome, :sigla)', [':nome' => 'TESTE', ':sigla' => 'TE']);
// $b = $context->prepare('UPDATE estado SET nome = :nome WHERE id = :id', [':nome' => 'TE', ':id' => '210']);
// $b = $context->prepare('UPDATE estado SET nome = :nome, sigla = :sigla WHERE id = :id', 'PDC', 'TI', 210);
// $b = $context->prepare('DELETE FROM estado WHERE id IN (:id)', [[':id' => '271'], [':id' => '272'], [':id' => '273']]);

// var_dump([
//     'queryString' => $b->getQueryString(),
//     'queryParameters' => $b->getQueryParameters(),
//     'queryRows' => $b->getQueryRows(),
//     'queryColumns' => $b->getQueryColumns(),
//     'affectedRows' => $b->getAffectedRows()
// ]);

/*
SQLite: 'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id'
MySQL:  'SELECT `id` AS `Codigo`, `nome` AS `Estado`, `sigla` AS `Sigla` FROM `estado` ORDER BY `id`'
Firebird:  'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
PgSQL:  'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
SQLSrv: 'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
OCI: 'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM HR."estado" ORDER BY "id"'
*/

/*
    0   MYSQLI_REPORT_OFF	    Turns reporting off
    1   MYSQLI_REPORT_ERROR	    Report errors from mysqli function calls
    2   MYSQLI_REPORT_STRICT	Throw mysqli_sql_exception for errors instead of warnings
    4   MYSQLI_REPORT_INDEX	    Report if no index or bad index was used in a query
    255 MYSQLI_REPORT_ALL       Set all options (report all)
 */