<?php

use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$context = Chainable::nativeMySQLi(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativePgSQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativeSQLSrv(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativeOCI(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativeFBird(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativeSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

// $context = Chainable::pdoMySQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::pdoPgSQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::pdoSQLSrv(env: $_ENV, strategy: false)->connect();
// $context = Chainable::pdoOCI(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::pdoFirebird(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::pdoSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

// var_dump($context);

// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :id', [':id' => 10]);
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :idA AND id <= :idB', [':idA' => 5, ':idB' => 10]);
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id = :id', '27');
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id IN(:idA, :idB, :idC)', '25', '26', '27');
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id');
$a = $context->query('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id NOT IN(25, 26, 27) ORDER BY id');

var_dump($a);

var_dump($a->queryMetadata());

var_dump([
    $a->queryString(),
    $a->queryParameters(),
    $a->queryRows(),
    $a->queryColumns(),
    $a->affectedRows()
]);

// var_dump($a->fetchAll(FETCH_OBJ));

while ($row = $a->fetch(FETCH_OBJ)) {
    echo vsprintf("<pre>%s, %s/%s</pre>", [$row->Codigo, $row->Estado, $row->Sigla]);
}

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
FBird:  'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
PgSQL:  'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
SQLSrv: 'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
Oracle: 'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM HR."estado" ORDER BY "id"'
*/
