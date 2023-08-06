<?php

use GenericDatabase\Runner\Chainable;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

// $context = Chainable::nativeFBird(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativeOCI(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativePgSQL(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativeSQLSrv(env: $_ENV, persistent: true, strategy: false)->connect();
$context = Chainable::nativeMySQLi(env: $_ENV, persistent: true, strategy: false)->connect();
// $context = Chainable::nativeSQLite(env: $_ENV, persistent: true, strategy: false)->connect();

var_dump($context);

// $a = $context->prepare('SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" WHERE "id" >= :id', [':id' => 10]);
// $a = $context->prepare('SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" WHERE "id" >= :idA AND "id" <= :idB', [':idA' => 5, ':idB' => 10]);
// $a = $context->prepare('SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" WHERE "id" = :id', '27');
// $a = $context->prepare('SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" WHERE "id" IN(:idA, :idB, :idC)', '25', '26', '27');
// $a = $context->prepare('SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"');
// $a = $context->query('SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" WHERE "id" NOT IN(25, 26, 27) ORDER BY "id"');

// var_dump($a);
// while ($row = $a->fetch(FETCH_BOTH)) {
//     var_dump($row);
// }
// var_dump($a->fetchAll(FETCH_BOTH));

// $b = $context->prepare('INSERT INTO "estado" ("id", "nome", "sigla") VALUES (?, ?, ?)', [['id' => 28, 'nome' => 'TESTE', 'sigla' => 'TE'], ['id' => 29, 'nome' => 'TESTE', 'sigla' => 'TE']]);
// $b = $context->prepare('INSERT INTO "estado" ("id", "nome", "sigla") VALUES (?, ?, ?)', ['id' => 28, 'nome' => 'TESTE', 'sigla' => 'TE']);
// $b = $context->prepare('UPDATE "estado" SET "nome" = ? WHERE "id" = ?', ['nome' => 'TE', 'id' => '28']);
// $b = $context->prepare('DELETE FROM "estado" WHERE "id" = ?', ['id' => '28']);

// $b = $context->prepare('INSERT INTO "estado" ("id", "nome", "sigla") VALUES (:id, :nome, :sigla)', [[':id' => 28, ':nome' => 'TESTE', ':sigla' => 'TE'], [':id' => 29, ':nome' => 'TESTE', ':sigla' => 'TE']]);
// $b = $context->prepare('INSERT INTO "estado" ("id", "nome", "sigla") VALUES (:id, :nome, :sigla)', [':id' => 28, ':nome' => 'TESTE', ':sigla' => 'TE']);
// $b = $context->prepare('UPDATE "estado" SET "nome" = :nome WHERE "id" = :id', [':nome' => 'TE', ':id' => '28']);
// $b = $context->prepare('DELETE FROM "estado" WHERE "id" = :id', [':id' => '28']);
// $b = $context->prepare('DELETE FROM "estado" WHERE "id" = 28');

// $b = $context->prepare('INSERT INTO "estado" ("nome", "sigla") VALUES (:nome, :sigla)', [[':nome' => 'TESTE1', ':sigla' => 'T1'], [':nome' => 'TESTE2', ':sigla' => 'T2']]);
// $b = $context->prepare('INSERT INTO "estado" ("nome", "sigla") VALUES (:nome, :sigla)', [':nome' => 'TESTE', ':sigla' => 'TE']);
// $b = $context->prepare('UPDATE "estado" SET "nome" = :nome WHERE "id" = :id', [':nome' => 'TE', ':id' => '134']);
// $b = $context->prepare('UPDATE "estado" SET "nome" = :nome, "sigla" = :sigla WHERE "id" = :id', 'PDC', 'TI', 134);
// $b = $context->prepare('DELETE FROM "estado" WHERE "id" IN (:id)', [[':id' => '132'], [':id' => '133'], [':id' => '134']]);

// var_dump($b);

/*
SQLite: 'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id'
MySQL:  'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id'
FBird:  'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
PgSQL:  'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
SQLSrv: 'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
Oracle: 'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM HR."estado" ORDER BY "id"'
*/