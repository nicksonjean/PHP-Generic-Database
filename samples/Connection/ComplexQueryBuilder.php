<?php

/**
 * Testes complexos com QueryBuilder.
 * Mesmos testes em ComplexQueryRaw.php e ComplexPrepare.php para comparação.
 *
 * Envolve: JOIN (estado x cidade), GROUP BY, HAVING, ORDER BY, LIMIT, DISTINCT,
 * funções agregadoras (COUNT, SUM, AVG).
 * Relacionamento: cidade.estado_id -> estado.id
 */

use Dotenv\Dotenv;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;
use GenericDatabase\Engine\SQLiteQueryBuilder;

define('PATH_ROOT', dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

// $context = Chainable::nativeFirebird(env: $_ENV, persistent: true, strategy: true)->connect(); // Not Installed
// $context = Chainable::nativeSQLite(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::nativeSQLSrv(env: $_ENV, strategy: true)->connect();
// $context = Chainable::nativeMySQLi(env: $_ENV, persistent: true, strategy: true)->connect();
$context = Chainable::nativeOCI(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::nativePgSQL(env: $_ENV, persistent: true, strategy: true)->connect();

// $context = Chainable::pdoFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::pdoSQLite(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::pdoSQLSrv(env: $_ENV, strategy: true)->connect();
// $context = Chainable::pdoMySQL(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::pdoPgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::pdoOCI(env: $_ENV, persistent: true, strategy: true)->connect();

// $context = Chainable::odbcFirebird(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::odbcSQLite(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::odbcSQLSrv(env: $_ENV, strategy: true)->connect();
// $context = Chainable::odbcMySQL(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::odbcPgSQL(env: $_ENV, persistent: true, strategy: true)->connect();
// $context = Chainable::odbcOCI(env: $_ENV, persistent: true, strategy: true)->connect();

$sep = str_repeat('=', 80) . "\n";

// -----------------------------------------------------------------------------
// Teste 1: JOIN + ORDER BY + LIMIT
// -----------------------------------------------------------------------------
echo $sep . "Teste 1: JOIN estado x cidade, ORDER BY cidade.nome, LIMIT 10\n" . $sep;

$qb1 = (new SQLiteQueryBuilder($context))
    ->select('e.id AS estado_id, e.nome AS estado_nome, e.sigla, c.id AS cidade_id, c.nome AS cidade_nome')
    ->from('estado e')
    ->join('cidade c')
    ->on('e.id = c.estado_id')
    ->order('c.nome ASC')
    ->limit('0, 10');

echo "Query (buildRaw): " . $qb1->buildRaw() . "\n\n";
echo "Metadados:\n";
var_dump($qb1->getAllMetadata());
echo "Resultados:\n";
$rows1 = $qb1->fetchAll(Connection::FETCH_ASSOC);
var_dump($rows1);

// -----------------------------------------------------------------------------
// Teste 2: JOIN + GROUP BY + COUNT + HAVING + ORDER BY + LIMIT
// -----------------------------------------------------------------------------
echo "\n" . $sep . "Teste 2: GROUP BY estado, COUNT(cidades), HAVING > 50, ORDER BY total DESC, LIMIT 5\n" . $sep;

$qb2 = (new SQLiteQueryBuilder($context))
    ->select('e.id AS estado_id, e.nome AS estado_nome, e.sigla, COUNT(c.id) AS total_cidades')
    ->from('estado e')
    ->join('cidade c')
    ->on('e.id = c.estado_id')
    ->group('e.id, e.nome, e.sigla')
    ->having('COUNT(c.id) > 50')
    ->order('COUNT(c.id) DESC')
    ->limit('0, 5');

echo "Query (buildRaw): " . $qb2->buildRaw() . "\n\n";
echo "Metadados:\n";
var_dump($qb2->getAllMetadata());
echo "Resultados:\n";
$rows2 = $qb2->fetchAll(Connection::FETCH_ASSOC);
var_dump($rows2);

// -----------------------------------------------------------------------------
// Teste 3: JOIN + GROUP BY + SUM + ORDER BY + LIMIT
// -----------------------------------------------------------------------------
echo "\n" . $sep . "Teste 3: GROUP BY estado, SUM(c.id), ORDER BY soma DESC, LIMIT 5\n" . $sep;

$qb3 = (new SQLiteQueryBuilder($context))
    ->select('e.id AS estado_id, e.nome AS estado_nome, SUM(c.id) AS soma_ids_cidades')
    ->from('estado e')
    ->join('cidade c')
    ->on('e.id = c.estado_id')
    ->group('e.id, e.nome')
    ->order('SUM(c.id) DESC')
    ->limit('0, 5');

echo "Query (buildRaw): " . $qb3->buildRaw() . "\n\n";
echo "Metadados:\n";
var_dump($qb3->getAllMetadata());
echo "Resultados:\n";
$rows3 = $qb3->fetchAll(Connection::FETCH_ASSOC);
var_dump($rows3);

// -----------------------------------------------------------------------------
// Teste 4: JOIN + GROUP BY + AVG + HAVING + ORDER BY + LIMIT
// -----------------------------------------------------------------------------
echo "\n" . $sep . "Teste 4: GROUP BY estado, AVG(c.id), HAVING AVG > 100, ORDER BY media DESC, LIMIT 5\n" . $sep;

$qb4 = (new SQLiteQueryBuilder($context))
    ->select('e.id AS estado_id, e.nome AS estado_nome, AVG(c.id) AS media_ids_cidades')
    ->from('estado e')
    ->join('cidade c')
    ->on('e.id = c.estado_id')
    ->group('e.id, e.nome')
    ->having('AVG(c.id) > 100')
    ->order('AVG(c.id) DESC')
    ->limit('0, 5');

echo "Query (buildRaw): " . $qb4->buildRaw() . "\n\n";
echo "Metadados:\n";
var_dump($qb4->getAllMetadata());
echo "Resultados:\n";
$rows4 = $qb4->fetchAll(Connection::FETCH_ASSOC);
var_dump($rows4);

// -----------------------------------------------------------------------------
// Teste 5: DISTINCT + ORDER BY + LIMIT
// -----------------------------------------------------------------------------
echo "\n" . $sep . "Teste 5: DISTINCT estado_id em cidade, ORDER BY estado_id, LIMIT 10\n" . $sep;

$qb5 = SQLiteQueryBuilder::with($context)::distinct('estado_id')
    ->from('cidade')
    ->order('estado_id ASC')
    ->limit('0, 10');

echo "Query (buildRaw): " . $qb5->buildRaw() . "\n\n";
echo "Metadados:\n";
var_dump($qb5->getAllMetadata());
echo "Resultados:\n";
$rows5 = $qb5->fetchAll(Connection::FETCH_ASSOC);
var_dump($rows5);

// -----------------------------------------------------------------------------
// Teste 6: JOIN + GROUP BY + COUNT + SUM + AVG + HAVING + ORDER BY + LIMIT
// -----------------------------------------------------------------------------
echo "\n" . $sep . "Teste 6: GROUP BY + COUNT + SUM + AVG + HAVING (>20) + ORDER BY + LIMIT 5\n" . $sep;

$qb6 = (new SQLiteQueryBuilder($context))
    ->select('e.id AS estado_id, e.nome AS estado_nome, COUNT(c.id) AS total_cidades, SUM(c.id) AS soma_ids, AVG(c.id) AS media_ids')
    ->from('estado e')
    ->join('cidade c')
    ->on('e.id = c.estado_id')
    ->group('e.id, e.nome')
    ->having('COUNT(c.id) > 20')
    ->order('COUNT(c.id) DESC')
    ->limit('0, 5');

echo "Query (buildRaw): " . $qb6->buildRaw() . "\n\n";
echo "Metadados:\n";
var_dump($qb6->getAllMetadata());
echo "Resultados:\n";
$rows6 = $qb6->fetchAll(Connection::FETCH_ASSOC);
var_dump($rows6);
