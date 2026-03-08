<?php

/**
 * Casos de uso: EXISTS, UNION, UNION ALL e Subqueries (Flat File - YAML).
 * Testes manuais para aferir tipagem e diversos modos de fetch/fetchAll.
 *
 * Métodos explorados: query(), prepare(), QueryBuilder (union, unionAll, whereExists, whereNotExists).
 * Fontes: estado, cidade (estado.yaml, cidade.yaml)
 */

use Dotenv\Dotenv;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;
use GenericDatabase\Engine\YAMLQueryBuilder;

define('PATH_ROOT', dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$context = Chainable::nativeYAML(env: $_ENV, persistent: true, strategy: false)->connect();

// =============================================================================
// 1. QUERY - Raw SQL: UNION
// =============================================================================
echo "1. query() - Raw UNION";

$sqlUnion = 'SELECT nome FROM estado UNION SELECT nome FROM cidade ORDER BY nome LIMIT 15';
$stmtUnion = $context->query($sqlUnion);

var_dump($stmtUnion->getAllMetadata());

echo "fetchAll(FETCH_ASSOC):";
$rows = $stmtUnion->fetchAll(Connection::FETCH_ASSOC);
var_dump($rows);

echo "fetchAll(FETCH_NUM):";
$stmtUnion2 = $context->query($sqlUnion);
var_dump($stmtUnion2->fetchAll(Connection::FETCH_NUM));

echo "fetchAll(FETCH_OBJ):";
$stmtUnion3 = $context->query($sqlUnion);
var_dump($stmtUnion3->fetchAll(Connection::FETCH_OBJ));

echo "fetchAll(FETCH_COLUMN):";
$stmtUnion4 = $context->query($sqlUnion);
var_dump($stmtUnion4->fetchAll(Connection::FETCH_COLUMN));

echo "fetch(FETCH_ASSOC) - iterativo:";
$stmtUnion5 = $context->query($sqlUnion);
while ($row = $stmtUnion5->fetch(Connection::FETCH_ASSOC)) {
    var_dump($row);
}

// =============================================================================
// 2. QUERY - Raw SQL: UNION ALL
// =============================================================================
echo "2. query() - Raw UNION ALL";

$sqlUnionAll = 'SELECT nome FROM estado UNION ALL SELECT nome FROM cidade ORDER BY nome LIMIT 10';
$stmtUnionAll = $context->query($sqlUnionAll);
var_dump($stmtUnionAll->getAllMetadata());
echo "fetchAll(FETCH_BOTH):";
var_dump($stmtUnionAll->fetchAll(Connection::FETCH_BOTH));

// =============================================================================
// 3. QUERY - Raw SQL: WHERE EXISTS (subquery)
// =============================================================================
echo "3. query() - Raw WHERE EXISTS com SUBQUERY e LIMIT";

$sqlExists = 'SELECT e.id, e.nome, e.sigla FROM estado e WHERE EXISTS (SELECT 1 FROM cidade c WHERE c.estado_id = e.id) ORDER BY e.nome LIMIT 5';
$stmtExists = $context->query($sqlExists);
var_dump($stmtExists->getAllMetadata());
echo "fetchAll(FETCH_ASSOC):";
var_dump($stmtExists->fetchAll(Connection::FETCH_ASSOC));

// =============================================================================
// 4. QUERY - Raw SQL: WHERE NOT EXISTS
// =============================================================================
echo "4. query() - Raw WHERE NOT EXISTS com SUBQUERY e LIMIT";

$sqlNotExists = 'SELECT e.id, e.nome, e.sigla FROM estado e WHERE NOT EXISTS (SELECT 1 FROM cidade c WHERE c.estado_id = e.id) ORDER BY e.nome LIMIT 5';
$stmtNotExists = $context->query($sqlNotExists);
var_dump($stmtNotExists->getAllMetadata());
echo "fetchAll(FETCH_ASSOC):";
var_dump($stmtNotExists->fetchAll(Connection::FETCH_ASSOC));

// =============================================================================
// 5. PREPARE - Prepared: UNION
// =============================================================================
echo "5. prepare() - UNION com LIMIT";

$sqlPrepareUnion = 'SELECT nome FROM estado UNION SELECT nome FROM cidade ORDER BY nome LIMIT :lim';
$stmtPrepare = $context->prepare($sqlPrepareUnion, [':lim' => 8]);
var_dump($stmtPrepare->getAllMetadata());
echo "fetchAll(FETCH_ASSOC):";
var_dump($stmtPrepare->fetchAll(Connection::FETCH_ASSOC));

// =============================================================================
// 6. PREPARE - Prepared: EXISTS
// =============================================================================
echo "6. prepare() - EXISTS com SUBQUERY e LIMIT";

$sqlPrepareExists = 'SELECT e.id, e.nome FROM estado e WHERE EXISTS (SELECT 1 FROM cidade c WHERE c.estado_id = e.id) ORDER BY e.nome LIMIT :lim';
$stmtPrepareExists = $context->prepare($sqlPrepareExists, [':lim' => 3]);
var_dump($stmtPrepareExists->getAllMetadata());
echo "fetchAll(FETCH_OBJ):";
var_dump($stmtPrepareExists->fetchAll(Connection::FETCH_OBJ));

// =============================================================================
// 7. QueryBuilder - union()
// =============================================================================
echo "7. QueryBuilder - union() com subquery";

$qbUnion = (new YAMLQueryBuilder($context))
    ->select('nome')
    ->from('estado')
    ->union(
        (new YAMLQueryBuilder($context))->select('nome')->from('cidade')
    )
    ->order('nome ASC')
    ->limit('0, 10');

var_dump($qbUnion->getAllMetadata());
echo "fetchAll(FETCH_ASSOC):";
var_dump($qbUnion->fetchAll(Connection::FETCH_ASSOC));

var_dump($qbUnion);
var_dump($qbUnion->getAllMetadata());

echo "fetch(FETCH_NUM) - iterativo:";
$qbUnion2 = (new YAMLQueryBuilder($context))
    ->select('nome')
    ->from('estado')
    ->union((new YAMLQueryBuilder($context))->select('nome')->from('cidade'))
    ->order('nome ASC')
    ->limit('0, 5');

var_dump($qbUnion2);
var_dump($qbUnion2->getAllMetadata());

while ($row = $qbUnion2->fetch(Connection::FETCH_NUM)) {
    var_dump($row);
}

// =============================================================================
// 8. QueryBuilder - unionAll()
// =============================================================================
echo "8. QueryBuilder - unionAll()";

$qbUnionAll = (new YAMLQueryBuilder($context))
    ->select('id', 'nome')
    ->from('estado')
    ->unionAll(
        (new YAMLQueryBuilder($context))->select('id', 'nome')->from('cidade')
    )
    ->order('nome ASC')
    ->limit('0, 8');

var_dump($qbUnionAll);
var_dump($qbUnionAll->getAllMetadata());

echo "fetchAll(FETCH_ASSOC):";
var_dump($qbUnionAll->fetchAll(Connection::FETCH_ASSOC));

// =============================================================================
// 9. QueryBuilder - whereExists()
// =============================================================================
echo "9. QueryBuilder - whereExists()";

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

var_dump($qbExists);
var_dump($qbExists->getAllMetadata());

echo "fetchAll(FETCH_ASSOC):";
var_dump($qbExists->fetchAll(Connection::FETCH_ASSOC));

echo "fetchAll(FETCH_CLASS, stdClass):";
$qbExists2 = (new YAMLQueryBuilder($context))
    ->select('e.id', 'e.nome', 'e.sigla')
    ->from('estado e')
    ->whereExists(
        (new YAMLQueryBuilder($context))
            ->select('1')
            ->from('cidade c')
            ->where('c.estado_id', '=', 'e.id')
    )
    ->limit('0, 3');

var_dump($qbExists2);
var_dump($qbExists2->getAllMetadata());
var_dump($qbExists2->fetchAll(Connection::FETCH_CLASS, stdClass::class));

// =============================================================================
// 10. QueryBuilder - whereNotExists()
// =============================================================================
echo "10. QueryBuilder - whereNotExists()";

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

var_dump($qbNotExists->getAllMetadata());
echo "fetchAll(FETCH_ASSOC):";
var_dump($qbNotExists->fetchAll(Connection::FETCH_ASSOC));

// =============================================================================
// 11. Combinação: UNION + whereExists (QueryBuilder)
// =============================================================================
echo "11. QueryBuilder - Combinação whereExists + union";

$qbComplex = (new YAMLQueryBuilder($context))
    ->select('nome', "'Estado' AS origem")
    ->from('estado e')
    ->whereExists(
        (new YAMLQueryBuilder($context))
            ->select('1')
            ->from('cidade c')
            ->where('c.estado_id', '=', 'e.id')
    )
    ->union(
        (new YAMLQueryBuilder($context))
            ->select('nome', "'Cidade' AS origem")
            ->from('cidade')
            ->where('id', '<=', 10)
    )
    ->order('nome ASC')
    ->limit('0, 12');

var_dump($qbComplex->getAllMetadata());
echo "fetchAll(FETCH_ASSOC):";
var_dump($qbComplex->fetchAll(Connection::FETCH_ASSOC));

// =============================================================================
// 12. Raw SQL - UNION ALL com colunas não escapadas
// =============================================================================
echo "12. query() - Raw UNION ALL com colunas não escapadas";

$sqlUnionAllUnquoted = 'SELECT id, nome FROM estado UNION ALL SELECT id, nome FROM cidade ORDER BY 1 ROWS 8';
$stmtUnionAllUnquoted = $context->query($sqlUnionAllUnquoted);
var_dump($stmtUnionAllUnquoted->getAllMetadata());
echo "fetchAll(FETCH_ASSOC):";
var_dump($stmtUnionAllUnquoted->fetchAll(Connection::FETCH_ASSOC));

// =============================================================================
// 13. QueryBuilder - mesma query do teste 12
// =============================================================================
echo "13. QueryBuilder - mesma query do teste 12";

$qbUnionAllTest13 = (new YAMLQueryBuilder($context))
    ->select('id', 'nome')
    ->from('estado')
    ->unionAll(
        (new YAMLQueryBuilder($context))->select('id', 'nome')->from('cidade')
    )
    ->order('nome ASC')
    ->limit('0, 10');

var_dump($qbUnionAllTest13->getAllMetadata());
echo "fetchAll(FETCH_ASSOC):";
var_dump($qbUnionAllTest13->fetchAll(Connection::FETCH_ASSOC));
