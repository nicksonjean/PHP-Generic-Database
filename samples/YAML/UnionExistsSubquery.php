<?php

/**
 * Casos de uso: EXISTS, UNION, UNION ALL e Subqueries (Flat File - YAML).
 * Testes manuais para aferir tipagem e diversos modos de fetch/fetchAll.
 *
 * Métodos explorados: query(), prepare(), QueryBuilder (union, unionAll, whereExists, whereNotExists).
 * Fontes: estado.yaml, cidade.yaml
 */

use Dotenv\Dotenv;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;
use GenericDatabase\Engine\YAMLQueryBuilder;

define('PATH_ROOT', dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$context = Chainable::nativeYAML(env: $_ENV, persistent: true, strategy: false)->connect();

$sep = str_repeat('=', 80) . "\n";

// =============================================================================
// 1. query() - SELECT simples
// =============================================================================
echo $sep . "1. query() - SELECT simples de estado\n" . $sep;

$sqlSimple = 'SELECT id, nome, sigla FROM estado LIMIT 5';
$stmtSimple = $context->query($sqlSimple);
echo "fetchAll(FETCH_ASSOC):\n";
var_dump($stmtSimple->fetchAll(Connection::FETCH_ASSOC));

// =============================================================================
// 2. prepare() - SELECT parametrizado
// =============================================================================
echo "\n" . $sep . "2. prepare() - SELECT com parâmetro\n" . $sep;

$sqlPrepare = 'SELECT id, nome FROM cidade LIMIT :lim';
$stmtPrepare = $context->prepare($sqlPrepare, [':lim' => 5]);
echo "fetchAll(FETCH_OBJ):\n";
var_dump($stmtPrepare->fetchAll(Connection::FETCH_OBJ));

// =============================================================================
// 3. QueryBuilder - union()
// =============================================================================
echo "\n" . $sep . "3. QueryBuilder - union()\n" . $sep;

$qbUnion = (new YAMLQueryBuilder($context))
    ->select('nome')
    ->from('estado')
    ->union((new YAMLQueryBuilder($context))->select('nome')->from('cidade'))
    ->order('nome ASC')
    ->limit('0, 15');

echo "buildRaw(): " . $qbUnion->buildRaw() . "\n\n";
echo "fetchAll(FETCH_ASSOC):\n";
var_dump($qbUnion->fetchAll(Connection::FETCH_ASSOC));

// =============================================================================
// 4. QueryBuilder - unionAll()
// =============================================================================
echo "\n" . $sep . "4. QueryBuilder - unionAll()\n" . $sep;

$qbUnionAll = (new YAMLQueryBuilder($context))
    ->select('id', 'nome')
    ->from('estado')
    ->unionAll((new YAMLQueryBuilder($context))->select('id', 'nome')->from('cidade'))
    ->order('nome ASC')
    ->limit('0, 12');

echo "fetchAll(FETCH_NUM):\n";
var_dump($qbUnionAll->fetchAll(Connection::FETCH_NUM));

// =============================================================================
// 5. QueryBuilder - whereExists()
// =============================================================================
echo "\n" . $sep . "5. QueryBuilder - whereExists()\n" . $sep;

$qbExists = (new YAMLQueryBuilder($context))
    ->select('e.id', 'e.nome', 'e.sigla')
    ->from('estado e')
    ->whereExists(
        (new YAMLQueryBuilder($context))
            ->select('1')
            ->from('cidade c')
            ->where('c.estado_id', '=', 'e.id')
    )
    ->limit('0, 5');

echo "fetchAll(FETCH_ASSOC):\n";
var_dump($qbExists->fetchAll(Connection::FETCH_ASSOC));

// =============================================================================
// 6. QueryBuilder - whereNotExists()
// =============================================================================
echo "\n" . $sep . "6. QueryBuilder - whereNotExists()\n" . $sep;

$qbNotExists = (new YAMLQueryBuilder($context))
    ->select('e.id', 'e.nome', 'e.sigla')
    ->from('estado e')
    ->whereNotExists(
        (new YAMLQueryBuilder($context))
            ->select('1')
            ->from('cidade c')
            ->where('c.estado_id', '=', 'e.id')
    )
    ->limit('0, 5');

echo "fetchAll(FETCH_ASSOC):\n";
var_dump($qbNotExists->fetchAll(Connection::FETCH_ASSOC));

echo "\n" . $sep . "Fim dos testes UnionExistsSubquery (YAML)\n" . $sep;
