<?php

/**
 * Casos de uso: EXISTS, UNION, UNION ALL e Subqueries (Flat File - JSON).
 * Testes manuais para aferir tipagem e diversos modos de fetch/fetchAll.
 *
 * Métodos explorados: query(), prepare(), QueryBuilder (union, unionAll, whereExists, whereNotExists).
 * Fontes: estado, cidade (estado.json, cidade.json)
 *
 * Nota: Flat Files suportam UNION/UNION ALL e EXISTS via QueryBuilder.
 * SQL bruto para UNION pode ter limitações.
 */

use Dotenv\Dotenv;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;
use GenericDatabase\Engine\JSONQueryBuilder;

define('PATH_ROOT', dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$context = Chainable::nativeJSON(env: $_ENV, persistent: true, strategy: false)->connect();

$sep = str_repeat('=', 80) . "\n";

// =============================================================================
// 1. query() - SELECT simples (baseline para fetch modes)
// =============================================================================
echo $sep . "1. query() - SELECT simples de estado\n" . $sep;

$sqlSimple = 'SELECT id, nome, sigla FROM estado LIMIT 5';
$stmtSimple = $context->query($sqlSimple);
echo "fetchAll(FETCH_ASSOC):\n";
var_dump($stmtSimple->fetchAll(Connection::FETCH_ASSOC));

echo "\nfetch(FETCH_NUM) - iterativo:\n";
$stmtSimple2 = $context->query($sqlSimple);
while ($row = $stmtSimple2->fetch(Connection::FETCH_NUM)) {
    var_dump($row);
}

// =============================================================================
// 2. prepare() - SELECT com parâmetro
// =============================================================================
echo "\n" . $sep . "2. prepare() - SELECT com LIMIT parametrizado\n" . $sep;

$sqlPrepare = 'SELECT id, nome FROM cidade LIMIT :lim';
$stmtPrepare = $context->prepare($sqlPrepare, [':lim' => 5]);
echo "fetchAll(FETCH_OBJ):\n";
var_dump($stmtPrepare->fetchAll(Connection::FETCH_OBJ));

// =============================================================================
// 3. QueryBuilder - union()
// =============================================================================
echo "\n" . $sep . "3. QueryBuilder - union()\n" . $sep;

$qbUnion = (new JSONQueryBuilder($context))
    ->select('nome')
    ->from('estado')
    ->union((new JSONQueryBuilder($context))->select('nome')->from('cidade'))
    ->order('nome ASC')
    ->limit('0, 15');

echo "buildRaw(): " . $qbUnion->buildRaw() . "\n\n";
echo "fetchAll(FETCH_ASSOC):\n";
var_dump($qbUnion->fetchAll(Connection::FETCH_ASSOC));

echo "\nfetchAll(FETCH_COLUMN):\n";
$qbUnion2 = (new JSONQueryBuilder($context))
    ->select('nome')
    ->from('estado')
    ->union((new JSONQueryBuilder($context))->select('nome')->from('cidade'))
    ->limit('0, 10');
var_dump($qbUnion2->fetchAll(Connection::FETCH_COLUMN));

// =============================================================================
// 4. QueryBuilder - unionAll()
// =============================================================================
echo "\n" . $sep . "4. QueryBuilder - unionAll()\n" . $sep;

$qbUnionAll = (new JSONQueryBuilder($context))
    ->select('id', 'nome')
    ->from('estado')
    ->unionAll((new JSONQueryBuilder($context))->select('id', 'nome')->from('cidade'))
    ->order('nome ASC')
    ->limit('0, 12');

echo "buildRaw(): " . $qbUnionAll->buildRaw() . "\n\n";
echo "fetchAll(FETCH_BOTH):\n";
var_dump($qbUnionAll->fetchAll(Connection::FETCH_BOTH));

// =============================================================================
// 5. QueryBuilder - whereExists()
// =============================================================================
echo "\n" . $sep . "5. QueryBuilder - whereExists()\n" . $sep;

$qbExists = (new JSONQueryBuilder($context))
    ->select('e.id', 'e.nome', 'e.sigla')
    ->from('estado e')
    ->whereExists(
        (new JSONQueryBuilder($context))
            ->select('1')
            ->from('cidade c')
            ->where('c.estado_id', '=', 'e.id')
    )
    ->limit('0, 5');

echo "buildRaw(): " . $qbExists->buildRaw() . "\n\n";
echo "fetchAll(FETCH_ASSOC):\n";
var_dump($qbExists->fetchAll(Connection::FETCH_ASSOC));

echo "\nfetchAll(FETCH_CLASS, stdClass):\n";
$qbExists2 = (new JSONQueryBuilder($context))
    ->select('e.id', 'e.nome', 'e.sigla')
    ->from('estado e')
    ->whereExists(
        (new JSONQueryBuilder($context))
            ->select('1')
            ->from('cidade c')
            ->where('c.estado_id', '=', 'e.id')
    )
    ->limit('0, 3');
var_dump($qbExists2->fetchAll(Connection::FETCH_CLASS, stdClass::class));

// =============================================================================
// 6. QueryBuilder - whereNotExists()
// =============================================================================
echo "\n" . $sep . "6. QueryBuilder - whereNotExists()\n" . $sep;

$qbNotExists = (new JSONQueryBuilder($context))
    ->select('e.id', 'e.nome', 'e.sigla')
    ->from('estado e')
    ->whereNotExists(
        (new JSONQueryBuilder($context))
            ->select('1')
            ->from('cidade c')
            ->where('c.estado_id', '=', 'e.id')
    )
    ->limit('0, 5');

echo "fetchAll(FETCH_ASSOC):\n";
var_dump($qbNotExists->fetchAll(Connection::FETCH_ASSOC));

echo "\n" . $sep . "Fim dos testes UnionExistsSubquery (JSON)\n" . $sep;
