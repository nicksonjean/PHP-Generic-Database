<?php

use GenericDatabase\Connection;
use GenericDatabase\Engine\MySQLi\MySQL;
use GenericDatabase\Engine\MySQLiConnection;
use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$connection = new Connection();
$connection
    ->setEngine('mysqli')
    ->setHost($_ENV['MYSQL_HOST'])
    ->setPort((int) $_ENV['MYSQL_PORT'])
    ->setDatabase($_ENV['MYSQL_DATABASE'])
    ->setUser($_ENV['MYSQL_USERNAME'])
    ->setPassword($_ENV['MYSQL_PASSWORD'])
    ->setCharset($_ENV['MYSQL_CHARSET'])
    ->setOptions([
        MySQL::ATTR_PERSISTENT => false,
        MySQL::ATTR_AUTOCOMMIT => true,
        MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
        MySQL::ATTR_SET_CHARSET_NAME => "utf8",
        MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
        MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
        MySQL::ATTR_OPT_READ_TIMEOUT => 30,
        MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
    ])
    ->setException(true);

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

$serialized = serialize($connection);
$context = unserialize($serialized);

var_dump($serialized);

$context = Chainable::nativeMySQLi(env: $_ENV, persistent: true, strategy: false)->connect();
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

// var_dump($context);

// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :id', [':id' => 10]);
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= :idA AND id <= :idB', [':idA' => 5, ':idB' => 10]);
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id = :id', '27');
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id IN(:idA, :idB, :idC)', '25', '26', '27');
// $a = $context->prepare('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado ORDER BY id');
$a = $context->query('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id NOT IN(25, 26, 27) ORDER BY id');

var_dump($a);

var_dump($a->getAllMetadata());

var_dump([
    $a->queryString(),
    $a->queryParameters(),
    $a->queryRows(),
    $a->queryColumns(),
    $a->affectedRows()
]);

// var_dump($a->fetchAll(FETCH_OBJ));

while ($row = $a->fetch(Connection::FETCH_OBJ)) {
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

// var_dump($b->getAllMetadata());

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
