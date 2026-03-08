<?php

// Suppress deprecation warnings from vendor libraries (temporário até aplicar patches)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

const EOL = '<br />' . PHP_EOL;

use GenericDatabase\Helpers\Parsers\SQL\Analyser;

// Re-enable warnings for user code
error_reporting(E_ALL);

try {
    $query = 'SELECT "nome", \'Estado\' AS "origem" FROM "estado" "e" WHERE EXISTS (SELECT 1 FROM "cidade" "c" WHERE "c"."estado_id" = "e"."id") UNION SELECT "nome", \'Cidade\' AS "origem" FROM "cidade" WHERE "id" <= 10 ORDER BY "nome" ASC LIMIT 12 OFFSET 0';
    var_dump($query);

    // First instantiation: grammar is loaded from disk and kept in the static cache.
    // All subsequent Analyser calls reuse the compiled grammar without re-reading the file.
    $start = microtime(true);
    $analyzer = new Analyser($query);
    echo sprintf('First parse (grammar load + parse): %.6f seconds' . EOL . EOL, microtime(true) - $start);

    // echo EOL . "=== AST as Object Output ===" . EOL;
    // var_dump($analyzer->dumpAst());

    $result = $analyzer->getResult();

    echo "=== Result Structure ===" . EOL;
    echo "Type: {$result['type']}" . EOL;
    echo "Number of queries: " . count($result['queries']) . EOL;
    echo "Set operations: " . count($result['setOperations']) . EOL;

    echo EOL . "=== Tables ===" . EOL;
    foreach ($analyzer->getAllTables() as $table) {
        $alias = $table['alias'] ? " AS {$table['alias']}" : '';
        echo "  - {$table['name']}{$alias}" . EOL;
    }

    echo EOL . "=== Columns ===" . EOL;
    foreach ($analyzer->getAllColumns() as $col) {
        $alias = $col['alias'] ? " AS {$col['alias']}" : '';
        echo "  - {$col['expression']}{$alias}" . EOL;
    }

    echo EOL . "=== Literals ===" . EOL;
    foreach ($analyzer->getLiterals() as $lit) {
        echo "  - {$lit['type']}: {$lit['value']}" . EOL;
    }

    echo EOL . "=== EXISTS Expressions ===" . EOL;
    foreach ($analyzer->getExistsExpressions() as $exists) {
        echo "  - Subquery: {$exists['subquery']}" . EOL;
    }

    echo EOL . "=== Array Output (toArray) ===" . EOL;
    var_dump($analyzer->toArray());

    echo EOL . "=== JSON Output ===" . EOL;
    var_dump(PHP_EOL . $analyzer->toJson() . PHP_EOL);

    echo EOL . "=== Original Query ===" . EOL;
    echo $query . EOL;

    echo EOL . "=== Reconstructed Query ===" . EOL;
    echo $analyzer->toSql() . EOL;

    echo EOL . "=== Query Flags ===" . EOL;
    echo "Has UNION: " . ($analyzer->hasUnion() ? 'Yes' : 'No') . EOL;
    echo "Has Subqueries: " . ($analyzer->hasSubqueries() ? 'Yes' : 'No') . EOL;
    echo "Has EXISTS: " . ($analyzer->hasExists() ? 'Yes' : 'No') . EOL;
    echo "Has JOINs: " . ($analyzer->hasJoins() ? 'Yes' : 'No') . EOL;

    echo EOL . "=== Ordered Values (literals only) ===" . EOL;
    var_dump($analyzer->getOrderedValues(true));

    echo EOL . "=== Ordered Values (parameters only) ===" . EOL;
    var_dump($analyzer->getOrderedValues(false));

    echo EOL . "=== Ordered Values (simplified literals) ===" . EOL;
    var_dump($analyzer->getOrderedValues(true, true));

    // The grammar is already cached — subsequent parses only pay the LL(k) cost.
    echo EOL . str_repeat("=", 80) . EOL;
    echo "=== Testing Prepared Statement (MySQL ? placeholders) ===" . EOL;
    $query2 = "SELECT name FROM accounts WHERE id = ? AND status = ? AND name LIKE ?";
    $start2 = microtime(true);
    $analyzer2 = new Analyser($query2);
    echo sprintf('Subsequent parse (grammar cached): %.6f seconds' . EOL, microtime(true) - $start2);
    echo "Query: " . $query2 . EOL;
    echo "Ordered Values:" . EOL;
    var_dump($analyzer2->getOrderedValues());

    echo EOL . "=== Testing Prepared Statement (PostgreSQL \$N placeholders) ===" . EOL;
    $query3 = "SELECT id, name FROM accounts WHERE id = \$1 AND status = \$2 AND name LIKE \$3";
    $analyzer3 = new Analyser($query3);
    echo "Query: " . $query3 . EOL;
    echo "Ordered Values:" . EOL;
    var_dump($analyzer3->getOrderedValues());
    echo "Ordered Values (simplified):" . EOL;
    var_dump($analyzer3->getOrderedValues(false, true));

    echo EOL . "=== Testing Prepared Statement (Named :param placeholders) ===" . EOL;
    $query4 = "SELECT id, name FROM accounts WHERE id = :userId AND status = :status AND name LIKE :pattern";
    $analyzer4 = new Analyser($query4);
    echo "Query: " . $query4 . EOL;
    echo "Ordered Values:" . EOL;
    var_dump($analyzer4->getOrderedValues());
    echo "Ordered Values (simplified):" . EOL;
    var_dump($analyzer4->getOrderedValues(false, true));

    echo EOL . "=== Testing Mixed (literals + parameters) ===" . EOL;
    $query5 = "SELECT id FROM accounts WHERE active = 1 AND id = ? AND name = 'John' AND status = ?";
    $analyzer5 = new Analyser($query5);
    echo "Query: " . $query5 . EOL;
    echo "Ordered Values (with literals):" . EOL;
    var_dump($analyzer5->getOrderedValues(true));
    echo "Ordered Values (simplified with literals):" . EOL;
    var_dump($analyzer5->getOrderedValues(true, true));
} catch (\Exception $e) {
    echo "Error during parsing:" . EOL;
    echo $e->getMessage() . EOL;
    exit(1);
}
