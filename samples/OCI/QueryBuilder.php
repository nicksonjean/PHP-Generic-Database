<?php

use Dotenv\Dotenv;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;
use GenericDatabase\Engine\OCIQueryBuilder;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$context = Chainable::nativeOCI(env: $_ENV, persistent: true, strategy: false)->connect();

$test0 = (new OCIQueryBuilder($context))->select(['e.id AS Codigo', 'e.nome AS Estado', 'e.sigla AS Sigla'])
    ->from(['estado e'])
    ->where(['e.id >= 25']);

var_dump($test0);
var_dump($test0->build());
var_dump($test0->buildRaw());
var_dump($test0->getAllMetadata());
var_dump($test0->fetchAll(Connection::FETCH_BOTH));
// while ($row = $test0->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }

$testA = OCIQueryBuilder::with($context)::select(['e.id AS Codigo', 'e.nome AS Estado', 'e.sigla AS Sigla'])
    ->from(['estado e'])
    ->where(['e.id <= 25']);

var_dump($testA);
var_dump($testA->build());
var_dump($testA->buildRaw());
var_dump($testA->getAllMetadata());
var_dump($testA->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testA->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }

$testB = (new OCIQueryBuilder($context))->select('e.id AS Codigo, e.nome AS Estado, e.sigla AS Sigla')
    ->from('estado e')
    ->where('e.id >= 1')
    ->andWhere('e.id <= 5');

var_dump($testB);
var_dump($testB->build());
var_dump($testB->buildRaw());
var_dump($testB->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testB->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }
// var_dump($testB->getAllMetadata());

$testC = OCIQueryBuilder::with($context)::select(['e.id AS Codigo', 'e.nome AS Estado', 'e.sigla AS Sigla'])
    ->from('estado e')
    ->where('e.id = 6')
    ->orWhere('e.id = 11');

var_dump($testC);
var_dump($testC->build());
var_dump($testC->buildRaw());
var_dump($testC->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testC->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }
// var_dump($testC->getAllMetadata());

$testD = OCIQueryBuilder::with($context)::select(['e.id AS Codigo', 'e.nome AS Estado', 'e.sigla AS Sigla'])
    ->from('estado e')
    ->where([['e.id >= 12'], ['AND' => 'e.id <= 20'], ['OR' => 'e.id = 25']]);

var_dump($testD);
var_dump($testD->build());
var_dump($testD->buildRaw());
var_dump($testD->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testD->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }
// var_dump($testD->getAllMetadata());

$testE = OCIQueryBuilder::with($context)::select(['e.id AS Codigo', 'e.nome AS Estado', 'e.sigla AS Sigla'])
    ->from('estado e')
    ->where('e.nome LIKE %Rio Grande%');

var_dump($testE);
var_dump($testE->build());
var_dump($testE->buildRaw());
var_dump($testE->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testE->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }
// var_dump($testE->getAllMetadata());

$testF = OCIQueryBuilder::with($context)::select(['e.id AS Codigo', 'e.nome AS Estado', 'e.sigla AS Sigla'])
    ->from('estado e')
    ->where('e.id BETWEEN 1, 20');

var_dump($testF);
var_dump($testF->build());
var_dump($testF->buildRaw());
var_dump($testF->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testF->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }
// var_dump($testF->getAllMetadata());

$testF1 = OCIQueryBuilder::with($context)::select(['e.id AS Codigo', 'e.nome AS Estado', 'e.sigla AS Sigla'])
    ->from('estado e')
    ->where('e.id BETWEEN 21 AND 22');

var_dump($testF1);
var_dump($testF1->build());
var_dump($testF1->buildRaw());
var_dump($testF1->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testF->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }
// var_dump($testF1->getAllMetadata());

$testG = OCIQueryBuilder::with($context)::select(['e.id AS Codigo', 'e.nome AS Estado', 'e.sigla AS Sigla'])
    ->from('estado e')
    ->where('e.id IN (20, 21, 22)');

var_dump($testG);
var_dump($testG->build());
var_dump($testG->buildRaw());
var_dump($testG->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testG->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }
// var_dump($testG->getAllMetadata());

$testH1 = OCIQueryBuilder::with($context)::select(['e.id AS Codigo', 'e.nome AS Estado', 'COUNT(c.id) as num_cidades'])
    ->from(['estado e'])
    ->join(['cidade c'])
    ->on(['e.id = c.estado_id'])
    ->group(['e.id, e.nome'])
    ->having(['COUNT(c.id) > 3'])
    ->order(['COUNT(c.id) DESC'])
    ->limit([0, 20]);

var_dump($testH1);
var_dump($testH1->build());
var_dump($testH1->buildRaw());
var_dump($testH1->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testH->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }
// var_dump($testH1->getAllMetadata());

$testH2 = OCIQueryBuilder::with($context)::select('e.id AS Codigo, e.nome AS Estado, COUNT(c.id) as num_cidades')
    ->from('estado e')
    ->join('cidade c')
    ->on('e.id = c.estado_id')
    ->group('e.id, e.nome')
    ->having('COUNT(c.id) > 5')
    ->order('COUNT(c.id) DESC')
    ->limit('0, 20');

var_dump($testH2);
var_dump($testH2->build());
var_dump($testH2->buildRaw());
var_dump($testH2->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testH->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }
// var_dump($testH2->getAllMetadata());

$testI = OCIQueryBuilder::with($context)::select(['e.*', 'c.*'])
    ->from(['estado e'], ['cidade c'])
    ->where(['e.id = 10'])
    ->andWhere(['c.id = 10']);

var_dump($testI);
var_dump($testI->build());
var_dump($testI->buildRaw());
var_dump($testI->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testI->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }
// var_dump($testI->getAllMetadata());

$testI1 = OCIQueryBuilder::with($context)::select('e.*, c.*')
    ->from('estado e, cidade c')
    ->where('e.id = 5')
    ->andWhere('c.id = 5');

var_dump($testI1);
var_dump($testI1->build());
var_dump($testI1->buildRaw());
var_dump($testI1->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testI->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }
// var_dump($testI1->getAllMetadata());

$testJ = OCIQueryBuilder::with($context)::select(['e.*', 'c.*'])
    ->from(['estado e', 'cidade c'])
    ->where([['e.id = 1'], ['AND' => 'e.id = 2'], ['AND' => 'e.id = 3']])
    ->andWhere(['e.id = 4'])
    ->orWhere(['c.id = 5'])
    ->order(['c.id DESC'])
    ->limit([0, 20]);

var_dump($testJ);
var_dump($testJ->build());
var_dump($testJ->buildRaw());
var_dump($testJ->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testJ->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }
// var_dump($testJ->getAllMetadata());

$testJ1 = OCIQueryBuilder::with($context)::select(['e.*', 'c.*'])
    ->from(['estado e', 'cidade c'])
    ->where([['e.id = 10'], ['AND' => 'e.id = 11'], ['AND' => 'e.id = 12']])
    ->andWhere([['e.id = 13'], ['OR' => 'c.id = 14']])
    ->order(['c.id DESC'])
    ->limit([0, 20]);

var_dump($testJ1);
var_dump($testJ1->build());
var_dump($testJ1->buildRaw());
var_dump($testJ1->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testJ->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }
// var_dump($testJ1->getAllMetadata());

$testJ2 = OCIQueryBuilder::with($context)::select('e.*, c.*')
    ->from('estado e, cidade c')
    ->where([['e.id = 10'], ['AND' => 'e.id = 11'], ['AND' => 'e.id = 12']])
    ->andWhere([['e.id = 13'], ['OR' => 'c.id = 14']])
    ->order(['c.id DESC'])
    ->limit('0, 20');

var_dump($testJ2);
var_dump($testJ2->build());
var_dump($testJ2->buildRaw());
var_dump($testJ2->fetchAll(Connection::FETCH_BOTH));
// while ($row = $testJ2->fetch(Connection::FETCH_ASSOC)) {
//     var_dump($row);
// }
// var_dump($testJ2->getAllMetadata());

// $combinedQuery = OCIQueryBuilder::with($context)::select(['maconha.id', 'maconha.nome'])
//     ->from('estado AS maconha')
//     ->where('maconha.id', '=', 'teste')
//     ->union(
//         OCIQueryBuilder::with($context)::select(['c.id', 'c.nome'])
//             ->from('estado AS c')
//             ->where('c.id', '=', 'maconha.id')
//     )
//     ->union(
//         OCIQueryBuilder::with($context)::select(['d.id', 'd.nome'])
//             ->from('estado AS d')
//             ->where('d.id', '=', 'maconha.id')
//     );

// var_dump($combinedQuery);
// var_dump($combinedQuery->build());
// var_dump($combinedQuery->buildRaw());

// $query = OCIQueryBuilder::with($context)::select(['*'])
//     ->from('orders AS o')
//     ->where('o.status', '=', 'completed')
//     ->where('o.total_amount', '>', 1000)
//     ->andWhere(
//         OCIQueryBuilder::with($context)::exists(
//             OCIQueryBuilder::with($context)::select(['1'])
//                 ->from('customers c')
//                 ->where('c.id', '=', 'o.customer_id')
//         )
//     )
//     ->orWhere(
//         OCIQueryBuilder::with($context)::notExists(
//             OCIQueryBuilder::with($context)::select(['1'])
//                 ->from('returns r')
//                 ->where('r.order_id', '=', 'o.id')
//         )
//     )
//     ->buildRaw();

// var_dump($query);


// var_dump(OCIQueryBuilder::with($context)::getRegexSelect());
// var_dump(OCIQueryBuilder::with($context)::getRegexFrom());
// var_dump(OCIQueryBuilder::with($context)::getRegexOn());
// var_dump(OCIQueryBuilder::with($context)::getRegexGroupOrder());
// var_dump(OCIQueryBuilder::with($context)::getRegexWhereHaving());
