<?php

use GenericDatabase\Connection;
use GenericDatabase\Engine\MySQLi\MySQL;
use GenericDatabase\Engine\MySQLiConnection;
use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

// $context = Chainable::nativeMySQLi(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativePgSQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativeSQLSrv(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativeOCI(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativeFirebird(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativeSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

// $context = Chainable::pdoMySQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::pdoPgSQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::pdoSQLSrv(env: $_ENV, strategy: false)->connect();
// $context = Chainable::pdoOCI(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::pdoFirebird(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::pdoSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

$context = Chainable::nativeJSON(env: $_ENV, persistent: true, strategy: false)->connect();

// var_dump($context);

// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :id', [':id' => 10]);
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id = :id', '27');
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id');
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :idA AND id <= :idB', [':idA' => 5, ':idB' => 10]);
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id BETWEEN :idA AND :idB', [':idA' => 15, ':idB' => 20]);
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id NOT BETWEEN :idA AND :idB', [':idA' => 15, ':idB' => 20]);
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id IN(:idA, :idB, :idC)', '25', '26', '27');
// $a = $context->query('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id NOT IN(25, 26, 27) ORDER BY id');
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE nome LIKE :idA', [':idA' => '%Rio%']);
$a = $context->query('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE nome NOT LIKE "%Rio%"');

// var_dump($a);

var_dump($a->getAllMetadata());

// var_dump([
//     $a->getQueryString(),
//     $a->getQueryParameters(),
//     $a->getQueryRows(),
//     $a->getQueryColumns(),
//     $a->getAffectedRows()
// ]);

var_dump($a->fetchAll(Connection::FETCH_OBJ));
// var_dump($a->fetchAll(Connection::FETCH_ASSOC));
// var_dump($a->fetchAll(Connection::FETCH_BOTH));
// var_dump($a->fetchAll(Connection::FETCH_NUM));
// var_dump($a->fetchAll(Connection::FETCH_COLUMN));
// var_dump($a->fetchAll(Connection::FETCH_CLASS));
// var_dump($a->fetchAll(Connection::FETCH_INTO));

// while ($row = $a->fetch(Connection::FETCH_OBJ)) {
//     echo vsprintf("<pre>%s, %s/%s</pre>", [$row->Codigo, $row->Estado, $row->Sigla]);
// }

// $b = $context->prepare('INSERT INTO estado (nome, sigla) VALUES (:nome, :sigla)', [[':nome' => 'TESTE1', ':sigla' => 'T1'], [':nome' => 'TESTE2', ':sigla' => 'T2']]);
// $b = $context->prepare('INSERT INTO estado (nome, sigla) VALUES (:nome, :sigla)', [':nome' => 'TESTE', ':sigla' => 'TE']);
// $b = $context->prepare('UPDATE estado SET nome = :nome WHERE id = :id', [':nome' => 'TE', ':id' => '210']);
// $b = $context->prepare('UPDATE estado SET nome = :nome, sigla = :sigla WHERE id = :id', 'PDC', 'TI', 210);
// $b = $context->prepare('DELETE FROM estado WHERE id IN (:id)', [[':id' => '271'], [':id' => '272'], [':id' => '273']]);
// $b = $context->query("INSERT INTO estado (nome, sigla) VALUES ('TESTE', 'TE')");
// $b = $context->query('DELETE FROM estado WHERE id IN (285, 286)');

// var_dump($b);

// var_dump($b->queryMetadata());

// var_dump([
//     $b->queryString(),
//     $b->queryParameters(),
//     $b->queryRows(),
//     $b->queryColumns(),
//     $b->affectedRows()
// ]);

/*
SQLite: 'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id'
MySQL:  'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id'
Firebird:  'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
PgSQL:  'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
SQLSrv: 'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
OCI: 'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM HR."estado" ORDER BY "id"'
*/
