<?php

use GenericDatabase\Runner\Chainable;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

// $context = Chainable::nativeOCI(env: $_ENV, persistent: true, strategy: false)->connect();
$context = Chainable::nativeFBird(env: $_ENV, persistent: true, strategy: false)->connect();

// $a = $context->prepare('SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" WHERE "id" >= :id', ['id' => 10]);
$a = $context->prepare('SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" WHERE "id" = :id', 'id', '27');
// $a = $context->prepare('SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"');
// $a = $context->query('SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"');

// var_dump($context);

var_dump($a);

while (($row = $a->fetch()) != false) {
    var_dump($row);
}

// var_dump($a->fetchAll());

/*
SQLite: 'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id'
MySQL:  'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id'
FBird:  'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
PgSQL:  'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
SQLSrv: 'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM "estado" ORDER BY "id"'
Oracle: 'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "Sigla" FROM HR."estado" ORDER BY "id"'
*/