<?php

/**
 * Testes complexos com método prepare() - prepared statements.
 * Mesmos testes em ComplexQueryRaw.php e ComplexQueryBuilder.php para comparação.
 *
 * Envolve: JOIN (estado x cidade), GROUP BY, HAVING, ORDER BY, LIMIT, DISTINCT,
 * funções agregadoras (COUNT, SUM, AVG).
 * Relacionamento: cidade.estado_id -> estado.id
 */

use Dotenv\Dotenv;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;

define('PATH_ROOT', dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$context = Chainable::nativeSQLSrv(env: $_ENV, persistent: true, strategy: false)->connect();

$sep = str_repeat('=', 80) . "\n";

// -----------------------------------------------------------------------------
// Teste 1: JOIN + ORDER BY + LIMIT
// -----------------------------------------------------------------------------
echo $sep . "Teste 1: JOIN estado x cidade, ORDER BY cidade.nome, LIMIT 10\n" . $sep;

$sql1 = 'SELECT e.id AS estado_id, e.nome AS estado_nome, e.sigla, c.id AS cidade_id, c.nome AS cidade_nome '
    . 'FROM estado e INNER JOIN cidade c ON c.estado_id = e.id '
    . 'ORDER BY c.nome ASC OFFSET 0 ROWS FETCH NEXT :limit1 ROWS ONLY';

$stmt1 = $context->prepare($sql1, [':limit1' => 10]);
echo "Query: " . $sql1 . " [ :limit1 => 10 ]\n\n";
echo "Metadados:\n";
var_dump($stmt1->getAllMetadata());
echo "Resultados:\n";
$rows1 = $stmt1->fetchAll(Connection::FETCH_ASSOC);
var_dump($rows1);

// -----------------------------------------------------------------------------
// Teste 2: JOIN + GROUP BY + COUNT + HAVING + ORDER BY + LIMIT
// -----------------------------------------------------------------------------
echo "\n" . $sep . "Teste 2: GROUP BY estado, COUNT(cidades), HAVING > 50, ORDER BY total DESC, LIMIT 5\n" . $sep;

$sql2 = 'SELECT e.id AS estado_id, e.nome AS estado_nome, e.sigla, COUNT(c.id) AS total_cidades '
    . 'FROM estado e INNER JOIN cidade c ON c.estado_id = e.id '
    . 'GROUP BY e.id, e.nome, e.sigla '
    . 'HAVING COUNT(c.id) > :min_cidades '
    . 'ORDER BY total_cidades DESC OFFSET 0 ROWS FETCH NEXT :limit2 ROWS ONLY';

$stmt2 = $context->prepare($sql2, [':min_cidades' => 50, ':limit2' => 5]);
echo "Query: " . $sql2 . " [ :min_cidades => 50, :limit2 => 5 ]\n\n";
echo "Metadados:\n";
var_dump($stmt2->getAllMetadata());
echo "Resultados:\n";
$rows2 = $stmt2->fetchAll(Connection::FETCH_ASSOC);
var_dump($rows2);

// -----------------------------------------------------------------------------
// Teste 3: JOIN + GROUP BY + SUM + ORDER BY + LIMIT
// -----------------------------------------------------------------------------
echo "\n" . $sep . "Teste 3: GROUP BY estado, SUM(c.id), ORDER BY soma DESC, LIMIT 5\n" . $sep;

$sql3 = 'SELECT e.id AS estado_id, e.nome AS estado_nome, SUM(c.id) AS soma_ids_cidades '
    . 'FROM estado e INNER JOIN cidade c ON c.estado_id = e.id '
    . 'GROUP BY e.id, e.nome '
    . 'ORDER BY soma_ids_cidades DESC OFFSET 0 ROWS FETCH NEXT :limit3 ROWS ONLY';

$stmt3 = $context->prepare($sql3, [':limit3' => 5]);
echo "Query: " . $sql3 . " [ :limit3 => 5 ]\n\n";
echo "Metadados:\n";
var_dump($stmt3->getAllMetadata());
echo "Resultados:\n";
$rows3 = $stmt3->fetchAll(Connection::FETCH_ASSOC);
var_dump($rows3);

// -----------------------------------------------------------------------------
// Teste 4: JOIN + GROUP BY + AVG + HAVING + ORDER BY + LIMIT
// -----------------------------------------------------------------------------
echo "\n" . $sep . "Teste 4: GROUP BY estado, AVG(c.id), HAVING AVG > 100, ORDER BY media DESC, LIMIT 5\n" . $sep;

$sql4 = 'SELECT e.id AS estado_id, e.nome AS estado_nome, AVG(c.id) AS media_ids_cidades '
    . 'FROM estado e INNER JOIN cidade c ON c.estado_id = e.id '
    . 'GROUP BY e.id, e.nome '
    . 'HAVING AVG(c.id) > :min_avg '
    . 'ORDER BY media_ids_cidades DESC OFFSET 0 ROWS FETCH NEXT :limit4 ROWS ONLY';

$stmt4 = $context->prepare($sql4, [':min_avg' => 100, ':limit4' => 5]);
echo "Query: " . $sql4 . " [ :min_avg => 100, :limit4 => 5 ]\n\n";
echo "Metadados:\n";
var_dump($stmt4->getAllMetadata());
echo "Resultados:\n";
$rows4 = $stmt4->fetchAll(Connection::FETCH_ASSOC);
var_dump($rows4);

// -----------------------------------------------------------------------------
// Teste 5: DISTINCT + ORDER BY + LIMIT
// -----------------------------------------------------------------------------
echo "\n" . $sep . "Teste 5: DISTINCT estado_id em cidade, ORDER BY estado_id, LIMIT 10\n" . $sep;

$sql5 = 'SELECT DISTINCT estado_id FROM cidade ORDER BY estado_id ASC OFFSET 0 ROWS FETCH NEXT :limit5 ROWS ONLY';

$stmt5 = $context->prepare($sql5, [':limit5' => 10]);
echo "Query: " . $sql5 . " [ :limit5 => 10 ]\n\n";
echo "Metadados:\n";
var_dump($stmt5->getAllMetadata());
echo "Resultados:\n";
$rows5 = $stmt5->fetchAll(Connection::FETCH_ASSOC);
var_dump($rows5);

// -----------------------------------------------------------------------------
// Teste 6: JOIN + GROUP BY + COUNT + SUM + AVG + HAVING + ORDER BY + LIMIT
// -----------------------------------------------------------------------------
echo "\n" . $sep . "Teste 6: GROUP BY + COUNT + SUM + AVG + HAVING (>20) + ORDER BY + LIMIT 5\n" . $sep;

$sql6 = 'SELECT e.id AS estado_id, e.nome AS estado_nome, '
    . 'COUNT(c.id) AS total_cidades, SUM(c.id) AS soma_ids, AVG(c.id) AS media_ids '
    . 'FROM estado e INNER JOIN cidade c ON c.estado_id = e.id '
    . 'GROUP BY e.id, e.nome '
    . 'HAVING COUNT(c.id) > :min_count '
    . 'ORDER BY total_cidades DESC OFFSET 0 ROWS FETCH NEXT :limit6 ROWS ONLY';

$stmt6 = $context->prepare($sql6, [':min_count' => 20, ':limit6' => 5]);
echo "Query: " . $sql6 . " [ :min_count => 20, :limit6 => 5 ]\n\n";
echo "Metadados:\n";
var_dump($stmt6->getAllMetadata());
echo "Resultados:\n";
$rows6 = $stmt6->fetchAll(Connection::FETCH_ASSOC);
var_dump($rows6);
