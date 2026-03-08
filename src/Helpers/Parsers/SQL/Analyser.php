<?php

declare(strict_types=1);

namespace GenericDatabase\Helpers\Parsers\SQL;

use Hoa\Compiler\Llk\TreeNode;

/**
 * SQL Query Analyzer - Complete AST analysis and query reconstruction
 *
 * Extracts and rebuilds all query components: tables, columns, aliases,
 * joins, unions, subqueries, literals, identifiers, parameters, etc.
 */
class Analyser
{
    private TreeNode $ast;

    /** @var array Complete parsed structure */
    private array $structure = [];

    /** @var array All literals found (strings, numbers) */
    private array $literals = [];

    /** @var array All identifiers found (tables, columns) */
    private array $identifiers = [];

    /** @var array All parameters/placeholders found */
    private array $parameters = [];

    /** @var array All escaped identifiers (backticks, brackets, quotes) */
    private array $escapedIdentifiers = [];

    /** @var array All subqueries found */
    private array $subqueries = [];

    /** @var array All EXISTS expressions */
    private array $existsExpressions = [];

    /** @var array All values (literals + parameters) in query order */
    private array $orderedValues = [];

    public function __construct(TreeNode $ast)
    {
        $this->ast = $ast;
        $this->analyze();
    }

    // =========================================================================
    // PUBLIC GETTERS
    // =========================================================================

    /**
     * Get complete analysis result
     */
    public function getResult(): array
    {
        return $this->structure;
    }

    /**
     * Get all SELECT queries (main + unions)
     */
    public function getQueries(): array
    {
        return $this->structure['queries'] ?? [];
    }

    /**
     * Get main query (first SELECT)
     */
    public function getMainQuery(): ?array
    {
        return $this->structure['queries'][0] ?? null;
    }

    /**
     * Get all UNION/INTERSECT/EXCEPT operations
     */
    public function getSetOperations(): array
    {
        return $this->structure['setOperations'] ?? [];
    }

    /**
     * Get all tables from all queries
     */
    public function getAllTables(): array
    {
        $tables = [];
        foreach ($this->structure['queries'] as $query) {
            foreach ($query['tables'] as $table) {
                $tables[] = $table;
            }
            foreach ($query['joins'] as $join) {
                if ($join['table']) {
                    $tables[] = $join['table'];
                }
            }
        }
        return $tables;
    }

    /**
     * Get all columns from all queries
     */
    public function getAllColumns(): array
    {
        $columns = [];
        foreach ($this->structure['queries'] as $query) {
            foreach ($query['columns'] as $col) {
                $columns[] = $col;
            }
        }
        return $columns;
    }

    /**
     * Get all string literals
     */
    public function getLiterals(): array
    {
        return $this->literals;
    }

    /**
     * Get all identifiers (table names, column names)
     */
    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }

    /**
     * Get all parameters/placeholders (?, :name, $1, @var)
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get all values (parameters and optionally literals) in query order.
     *
     * Returns an associative array where:
     * - Named parameters (:name, @name, #name) use the parameter name as key
     * - Numbered parameters ($1, $2) use the number as key
     * - Positional placeholders (?) use sequential numeric index (0, 1, 2...)
     * - Literals (when included) use 'literal_N' format as key
     *
     * @param bool $includeLiterals Whether to include string/number literals
     * @param bool $simplified When true, returns a flat array with type-casted values.
     *        For question_param (?): sequential numeric keys (0, 1, 2...)
     *        For named/at/hash params: ':name' as key
     *        For numbered params ($N): '$N' as key
     *        Literal values are cast by type affinity: int, float, bool, or string
     * @return array Ordered array of values with appropriate keys
     */
    public function getOrderedValues(bool $includeLiterals = false, bool $simplified = false): array
    {
        // Sort by offset to maintain query order
        $sorted = $this->orderedValues;
        usort($sorted, fn($a, $b) => ($a['offset'] ?? 0) <=> ($b['offset'] ?? 0));

        $result = [];
        $questionIndex = 0;
        $literalIndex = 0;

        foreach ($sorted as $item) {
            $category = $item['category'] ?? 'unknown';

            if ($category === 'parameter') {
                $type = $item['type'] ?? '';

                if ($simplified) {
                    if ($type === 'named_param') {
                        $result[':' . ($item['name'] ?? ltrim($item['original'], ':'))] = null;
                    } elseif ($type === 'at_param') {
                        $result['@' . ($item['name'] ?? ltrim($item['original'], '@'))] = null;
                    } elseif ($type === 'hash_param') {
                        $result['#' . ($item['name'] ?? ltrim($item['original'], '#'))] = null;
                    } elseif ($type === 'numbered_param') {
                        $result[$item['original'] ?? ('$' . ($item['position'] ?? 0))] = null;
                    } elseif ($type === 'question_param') {
                        $result[$questionIndex] = null;
                        $questionIndex++;
                    }
                } else {
                    if ($type === 'named_param' || $type === 'at_param' || $type === 'hash_param') {
                        $key = $item['name'] ?? $item['original'];
                        $result[$key] = [
                            'type' => 'parameter',
                            'paramType' => $type,
                            'original' => $item['original'],
                            'value' => null,
                        ];
                    } elseif ($type === 'numbered_param') {
                        $key = $item['position'] ?? 0;
                        $result[$key] = [
                            'type' => 'parameter',
                            'paramType' => $type,
                            'original' => $item['original'],
                            'value' => null,
                        ];
                    } elseif ($type === 'question_param') {
                        $result[$questionIndex] = [
                            'type' => 'parameter',
                            'paramType' => $type,
                            'original' => '?',
                            'value' => null,
                        ];
                        $questionIndex++;
                    }
                }
            } elseif ($category === 'literal' && $includeLiterals) {
                if ($simplified) {
                    $result[] = self::castByAffinity($item['value'], $item['type']);
                } else {
                    $key = 'literal_' . $literalIndex;
                    $result[$key] = [
                        'type' => 'literal',
                        'literalType' => $item['type'],
                        'value' => $item['value'],
                        'raw' => $item['raw'],
                    ];
                    $literalIndex++;
                }
            }
        }

        return $result;
    }

    /**
     * Cast a literal value to its appropriate PHP type by affinity.
     *
     * - Integer strings → int
     * - Decimal/monetary strings → float
     * - 'true'/'false' → bool
     * - Everything else → string
     */
    private static function castByAffinity(string $value, string $literalType): int|float|bool|string|null
    {
        if ($literalType === 'number') {
            if (str_contains($value, '.') || str_contains($value, ',')) {
                return (float) str_replace(',', '.', $value);
            }
            if (ctype_digit(ltrim($value, '-+'))) {
                return (int) $value;
            }
            return (float) $value;
        }

        // String type - check for boolean, numeric, and other affinities
        $lower = strtolower(trim($value));

        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if ($lower === 'null') return null;

        // Pure integer (possibly negative)
        if (preg_match('/^[+-]?\d+$/', $value)) {
            return (int) $value;
        }

        // Decimal / monetary (e.g. "19.99", "1,500.00", "3.14")
        $normalized = str_replace(',', '', $value);
        if (preg_match('/^[+-]?\d+\.\d+$/', $normalized)) {
            return (float) $normalized;
        }

        return $value;
    }

    /**
     * Get all escaped identifiers (with backticks, brackets, or quotes)
     */
    public function getEscapedIdentifiers(): array
    {
        return $this->escapedIdentifiers;
    }

    /**
     * Get all subqueries
     */
    public function getSubqueries(): array
    {
        return $this->subqueries;
    }

    /**
     * Get all EXISTS expressions
     */
    public function getExistsExpressions(): array
    {
        return $this->existsExpressions;
    }

    /**
     * Check if query has UNION
     */
    public function hasUnion(): bool
    {
        return count($this->structure['queries']) > 1;
    }

    /**
     * Check if query has subqueries
     */
    public function hasSubqueries(): bool
    {
        return !empty($this->subqueries);
    }

    /**
     * Check if query has EXISTS
     */
    public function hasExists(): bool
    {
        return !empty($this->existsExpressions);
    }

    /**
     * Check if query has JOINs
     */
    public function hasJoins(): bool
    {
        foreach ($this->structure['queries'] as $query) {
            if (!empty($query['joins'])) {
                return true;
            }
        }
        return false;
    }

    // =========================================================================
    // QUERY RECONSTRUCTION
    // =========================================================================

    /**
     * Rebuild the complete SQL query from the AST
     */
    public function toSql(): string
    {
        return $this->rebuildFromNode($this->ast);
    }

    /**
     * Rebuild query with custom identifier escaping
     * @param string $escapeChar Character to use for escaping (`, ", [)
     */
    public function toSqlWithEscape(string $escapeChar = '`'): string
    {
        $closeChar = $escapeChar === '[' ? ']' : $escapeChar;
        $sql = $this->rebuildFromNode($this->ast);

        // Replace all escaped identifiers with new escape style
        foreach ($this->escapedIdentifiers as $escaped) {
            $original = $escaped['original'];
            $name = $escaped['name'];
            $newEscaped = $escapeChar . $name . $closeChar;
            $sql = str_replace($original, $newEscaped, $sql);
        }

        return $sql;
    }

    /**
     * Rebuild query with parameters replaced by values
     * @param array $values Associative array of parameter => value
     */
    public function toSqlWithValues(array $values): string
    {
        $sql = $this->rebuildFromNode($this->ast);

        foreach ($this->parameters as $param) {
            $key = $param['name'] ?? $param['position'] ?? null;
            if ($key !== null && isset($values[$key])) {
                $value = $values[$key];
                $replacement = is_string($value) ? "'" . addslashes($value) . "'" : $value;
                $sql = str_replace($param['original'], $replacement, $sql);
            }
        }

        return $sql;
    }

    // =========================================================================
    // ANALYSIS
    // =========================================================================

    private function analyze(): void
    {
        $this->structure = [
            'type' => $this->detectQueryType(),
            'queries' => [],
            'setOperations' => [],
            'orderBy' => [],
            'limit' => null,
        ];

        // First scan entire AST for literals and parameters
        $this->scanForLiteralsAndParams($this->ast);

        $this->processSelectQuery($this->ast);
    }

    private function detectQueryType(): string
    {
        $id = $this->ast->getId();
        if (str_contains($id, 'Select')) return 'SELECT';
        if (str_contains($id, 'Insert')) return 'INSERT';
        if (str_contains($id, 'Update')) return 'UPDATE';
        if (str_contains($id, 'Delete')) return 'DELETE';
        return 'UNKNOWN';
    }

    private function processSelectQuery(TreeNode $node): void
    {
        $id = $node->getId();

        if ($id === '#SelectQuery') {
            $this->processSelectQueryNode($node);
        } elseif ($id === '#SimpleSelectQuery') {
            $this->structure['queries'][] = $this->processSimpleSelect($node);
        } else {
            foreach ($node->getChildren() as $child) {
                $this->processSelectQuery($child);
            }
        }
    }

    private function processSelectQueryNode(TreeNode $node): void
    {
        $children = $node->getChildren();
        $queryIndex = 0;

        foreach ($children as $child) {
            $id = $child->getId();

            if ($id === '#SimpleSelectQuery') {
                $this->structure['queries'][] = $this->processSimpleSelect($child);
                $queryIndex++;
            } elseif ($id === '#SetOperator') {
                $this->structure['setOperations'][] = $this->processSetOperator($child, $queryIndex);
            } elseif ($id === '#WithClause') {
                $this->structure['with'] = $this->processWithClause($child);
            } elseif ($id === '#SelectQuery') {
                // Nested select query (with parentheses)
                $this->processSelectQueryNode($child);
            }
        }
    }

    private function processSimpleSelect(TreeNode $node): array
    {
        $query = [
            'columns' => [],
            'tables' => [],
            'joins' => [],
            'where' => null,
            'groupBy' => [],
            'having' => null,
            'orderBy' => [],
            'limit' => null,
            'distinct' => false,
            'all' => false,
        ];

        foreach ($node->getChildren() as $child) {
            $id = $child->getId();

            switch ($id) {
                case '#SelectClause':
                    $this->processSelectClause($child, $query);
                    break;
                case '#FromClause':
                    $this->processFromClause($child, $query);
                    break;
                case '#WhereClause':
                    $query['where'] = $this->processWhereClause($child);
                    break;
                case '#GroupByClause':
                    $query['groupBy'] = $this->processGroupByClause($child);
                    break;
                case '#HavingClause':
                    $query['having'] = $this->processHavingClause($child);
                    break;
                case '#OrderByClause':
                    $orderBy = $this->processOrderByClause($child);
                    $query['orderBy'] = $orderBy;
                    $this->structure['orderBy'] = $orderBy; // Also at top level for UNION
                    break;
                case '#LimitClause':
                    $limit = $this->processLimitClause($child);
                    $query['limit'] = $limit;
                    $this->structure['limit'] = $limit; // Also at top level for UNION
                    break;
            }
        }

        return $query;
    }

    private function processSelectClause(TreeNode $node, array &$query): void
    {
        foreach ($node->getChildren() as $child) {
            if ($child->isToken()) {
                $token = $child->getValue()['token'];
                if ($token === 'distinct') $query['distinct'] = true;
                if ($token === 'all') $query['all'] = true;
            } elseif ($child->getId() === '#SelectExpression') {
                $query['columns'][] = $this->processSelectExpression($child);
            }
        }
    }

    private function processSelectExpression(TreeNode $node): array
    {
        $column = [
            'expression' => '',
            'expressionRaw' => '',
            'alias' => null,
            'aliasRaw' => null,
            'type' => 'expression',
            'isWildcard' => false,
            'table' => null,
        ];

        foreach ($node->getChildren() as $child) {
            $id = $child->getId();

            if ($id === '#ColumnAlias') {
                $aliasInfo = $this->extractAlias($child);
                $column['alias'] = $aliasInfo['name'];
                $column['aliasRaw'] = $aliasInfo['raw'];
            } elseif ($child->isToken() && $child->getValue()['token'] === 'op_mul') {
                $column['expression'] = '*';
                $column['expressionRaw'] = '*';
                $column['type'] = 'wildcard';
                $column['isWildcard'] = true;
            } elseif ($id === '#QualifiedTableName') {
                // table.*
                $tableName = $this->extractQualifiedName($child);
                $column['table'] = $tableName['name'];
                $column['expression'] = $tableName['name'] . '.*';
                $column['expressionRaw'] = $this->rebuildFromNode($child) . '.*';
                $column['type'] = 'wildcard';
                $column['isWildcard'] = true;
            } else {
                $exprInfo = $this->extractExpression($child);
                $column['expression'] = $exprInfo['text'];
                $column['expressionRaw'] = $exprInfo['raw'];
                $column['type'] = $exprInfo['type'];
                if (isset($exprInfo['table'])) {
                    $column['table'] = $exprInfo['table'];
                }
            }
        }

        return $column;
    }

    private function processFromClause(TreeNode $node, array &$query): void
    {
        foreach ($node->getChildren() as $child) {
            $id = $child->getId();

            if ($id === '#TableIdentifier') {
                // TableIdentifier may contain JoinedTable which wraps
                // TablePrimaryIdentifier + QualifiedJoin(s)
                $this->processTableIdentifier($child, $query);
            } elseif ($id === '#QualifiedJoin') {
                $query['joins'][] = $this->processJoin($child);
            } elseif ($id === '#CrossJoin') {
                $query['joins'][] = $this->processJoin($child);
            }
        }
    }

    private function processTableIdentifier(TreeNode $node, array &$query): void
    {
        foreach ($node->getChildren() as $child) {
            $id = $child->getId();

            if ($id === '#JoinedTable') {
                // JoinedTable contains TablePrimaryIdentifier + QualifiedJoin(s)
                foreach ($child->getChildren() as $joinChild) {
                    $joinId = $joinChild->getId();
                    if ($joinId === '#TablePrimaryIdentifier') {
                        $query['tables'][] = $this->extractTableFromPrimary($joinChild);
                    } elseif ($joinId === '#QualifiedJoin' || $joinId === '#CrossJoin') {
                        $query['joins'][] = $this->processJoin($joinChild);
                    }
                }
            } elseif ($id === '#TablePrimaryIdentifier') {
                $query['tables'][] = $this->extractTableFromPrimary($child);
            } elseif ($id === '#QualifiedTableName' || $id === '#Subselect') {
                // Direct table reference without JoinedTable wrapper
                $query['tables'][] = $this->extractTableIdentifier($node);
            }
        }
    }

    private function extractTableFromPrimary(TreeNode $node): array
    {
        $table = [
            'name' => '',
            'nameRaw' => '',
            'alias' => null,
            'aliasRaw' => null,
            'schema' => null,
            'database' => null,
            'isSubquery' => false,
            'subquery' => null,
        ];

        foreach ($node->getChildren() as $child) {
            $id = $child->getId();
            if ($id === '#QualifiedTableName') {
                $parts = $this->extractQualifiedName($child);
                $table = array_merge($table, $parts);
            } elseif ($id === '#TableAlias') {
                $aliasInfo = $this->extractAlias($child);
                $table['alias'] = $aliasInfo['name'];
                $table['aliasRaw'] = $aliasInfo['raw'];
            } elseif ($id === '#Subselect') {
                $table['isSubquery'] = true;
                $table['name'] = '(subquery)';
                $table['subquery'] = $this->rebuildFromNode($child);
                $this->registerSubquery($child);
            }
        }

        return $table;
    }

    private function processJoin(TreeNode $node): array
    {
        $join = [
            'type' => 'INNER',
            'table' => null,
            'condition' => null,
            'conditionRaw' => null,
            'using' => [],
        ];

        foreach ($node->getChildren() as $child) {
            $id = $child->getId();

            if ($child->isToken()) {
                $token = strtoupper($child->getValue()['token']);
                $value = strtoupper($child->getValue()['value']);

                if (in_array($token, ['inner', 'left', 'right', 'full', 'cross', 'natural'])) {
                    $join['type'] = $value;
                } elseif ($token === 'outer') {
                    $join['type'] .= ' OUTER';
                }
            } elseif ($id === '#JoinType' || $id === '#OuterJoinType') {
                $join['type'] = strtoupper(trim($this->rebuildFromNode($child)));
            } elseif ($id === '#TableIdentifier') {
                $join['table'] = $this->extractTableIdentifier($child);
            } elseif ($id === '#TablePrimaryIdentifier') {
                $join['table'] = $this->extractTableFromPrimary($child);
            } elseif ($id === '#JoinSpecification' || $id === '#JoinCondition') {
                $this->processJoinCondition($child, $join);
            }
        }

        return $join;
    }

    private function processJoinCondition(TreeNode $node, array &$join): void
    {
        foreach ($node->getChildren() as $child) {
            $id = $child->getId();

            if ($id === '#ConditionalExpression') {
                $join['condition'] = $this->extractCondition($child);
                $join['conditionRaw'] = $this->rebuildFromNode($child);
            } elseif ($id === '#Identifier') {
                $join['using'][] = $this->extractIdentifierValue($child);
            } elseif ($id === '#JoinCondition') {
                $this->processJoinCondition($child, $join);
            }
        }
    }

    private function processWhereClause(TreeNode $node): array
    {
        $where = [
            'text' => '',
            'raw' => '',
            'conditions' => [],
            'hasExists' => false,
            'hasSubquery' => false,
        ];

        foreach ($node->getChildren() as $child) {
            if ($child->getId() === '#ConditionalExpression') {
                $where['text'] = $this->extractText($child);
                $where['raw'] = $this->rebuildFromNode($child);
                $where['conditions'] = $this->extractConditions($child);
                $where['hasExists'] = $this->nodeContains($child, '#ExistsExpression');
                $where['hasSubquery'] = $this->nodeContains($child, '#Subselect');
            }
        }

        return $where;
    }

    private function processGroupByClause(TreeNode $node): array
    {
        $items = [];

        foreach ($node->getChildren() as $child) {
            if ($child->getId() === '#GroupByItem') {
                $items[] = [
                    'expression' => $this->extractText($child),
                    'raw' => $this->rebuildFromNode($child),
                ];
            }
        }

        return $items;
    }

    private function processHavingClause(TreeNode $node): array
    {
        $having = [
            'text' => '',
            'raw' => '',
            'conditions' => [],
        ];

        foreach ($node->getChildren() as $child) {
            if ($child->getId() === '#ConditionalExpression') {
                $having['text'] = $this->extractText($child);
                $having['raw'] = $this->rebuildFromNode($child);
                $having['conditions'] = $this->extractConditions($child);
            }
        }

        return $having;
    }

    private function processOrderByClause(TreeNode $node): array
    {
        $items = [];

        foreach ($node->getChildren() as $child) {
            if ($child->getId() === '#OrderByItem') {
                $items[] = $this->processOrderByItem($child);
            }
        }

        return $items;
    }

    private function processOrderByItem(TreeNode $node): array
    {
        $item = [
            'expression' => '',
            'raw' => '',
            'direction' => 'ASC',
            'nulls' => null,
        ];

        foreach ($node->getChildren() as $child) {
            if ($child->isToken()) {
                $token = strtolower($child->getValue()['token']);
                if ($token === 'asc') $item['direction'] = 'ASC';
                elseif ($token === 'desc') $item['direction'] = 'DESC';
                elseif ($token === 'first') $item['nulls'] = 'FIRST';
                elseif ($token === 'last') $item['nulls'] = 'LAST';
            } else {
                $id = $child->getId();
                if (in_array($id, ['#SimpleArithmeticExpression', '#ScalarExpression', '#ColumnIdentifier', '#number'])) {
                    $item['expression'] = $this->extractText($child);
                    $item['raw'] = $this->rebuildFromNode($child);
                }
            }
        }

        return $item;
    }

    private function processLimitClause(TreeNode $node): array
    {
        $numbers = [];

        foreach ($node->getChildren() as $child) {
            if ($child->getId() === '#number' || ($child->isToken() && $child->getValue()['token'] === 'num_literal')) {
                $numbers[] = (int) $this->extractText($child);
            }
        }

        if (count($numbers) === 1) {
            return ['count' => $numbers[0], 'offset' => 0];
        } elseif (count($numbers) >= 2) {
            return ['offset' => $numbers[0], 'count' => $numbers[1]];
        }

        return ['count' => 0, 'offset' => 0];
    }

    private function processSetOperator(TreeNode $node, int $beforeQueryIndex): array
    {
        $op = [
            'type' => 'UNION',
            'all' => false,
            'beforeQuery' => $beforeQueryIndex,
            'afterQuery' => $beforeQueryIndex + 1,
        ];

        foreach ($node->getChildren() as $child) {
            if ($child->isToken()) {
                $token = strtolower($child->getValue()['token']);
                $value = strtoupper($child->getValue()['value']);

                if (in_array($token, ['union', 'intersect', 'except', 'minus'])) {
                    $op['type'] = $value;
                } elseif ($token === 'all') {
                    $op['all'] = true;
                }
            }
        }

        return $op;
    }

    private function processWithClause(TreeNode $node): array
    {
        // CTE processing - simplified
        return [
            'raw' => $this->rebuildFromNode($node),
        ];
    }

    // =========================================================================
    // EXTRACTION HELPERS
    // =========================================================================

    private function extractTableIdentifier(TreeNode $node): array
    {
        $table = [
            'name' => '',
            'nameRaw' => '',
            'alias' => null,
            'aliasRaw' => null,
            'schema' => null,
            'database' => null,
            'isSubquery' => false,
            'subquery' => null,
        ];

        foreach ($node->getChildren() as $child) {
            $id = $child->getId();

            if ($id === '#TablePrimaryIdentifier') {
                foreach ($child->getChildren() as $subChild) {
                    if ($subChild->getId() === '#QualifiedTableName') {
                        $parts = $this->extractQualifiedName($subChild);
                        $table = array_merge($table, $parts);
                    } elseif ($subChild->getId() === '#TableAlias') {
                        $aliasInfo = $this->extractAlias($subChild);
                        $table['alias'] = $aliasInfo['name'];
                        $table['aliasRaw'] = $aliasInfo['raw'];
                    } elseif ($subChild->getId() === '#Subselect') {
                        $table['isSubquery'] = true;
                        $table['name'] = '(subquery)';
                        $table['subquery'] = $this->rebuildFromNode($subChild);
                        $this->registerSubquery($subChild);
                    }
                }
            } elseif ($id === '#QualifiedTableName') {
                $parts = $this->extractQualifiedName($child);
                $table = array_merge($table, $parts);
            } elseif ($id === '#TableAlias') {
                $aliasInfo = $this->extractAlias($child);
                $table['alias'] = $aliasInfo['name'];
                $table['aliasRaw'] = $aliasInfo['raw'];
            } elseif ($id === '#Subselect') {
                $table['isSubquery'] = true;
                $table['name'] = '(subquery)';
                $table['subquery'] = $this->rebuildFromNode($child);
                $this->registerSubquery($child);
            }
        }

        return $table;
    }

    private function extractQualifiedName(TreeNode $node): array
    {
        $parts = ['name' => '', 'nameRaw' => '', 'schema' => null, 'database' => null];
        $identifiers = [];

        foreach ($node->getChildren() as $child) {
            $id = $child->getId();
            if (in_array($id, ['#Identifier', '#SchemaIdentifier', '#DatabaseIdentifier'])) {
                $identifiers[] = $this->extractIdentifierInfo($child);
            }
        }

        $count = count($identifiers);
        if ($count >= 1) {
            $parts['name'] = $identifiers[$count - 1]['name'];
            $parts['nameRaw'] = $identifiers[$count - 1]['raw'];
        }
        if ($count >= 2) {
            $parts['schema'] = $identifiers[$count - 2]['name'];
        }
        if ($count >= 3) {
            $parts['database'] = $identifiers[$count - 3]['name'];
        }

        return $parts;
    }

    private function extractIdentifierInfo(TreeNode $node): array
    {
        $info = ['name' => '', 'raw' => '', 'isEscaped' => false, 'escapeType' => null];

        foreach ($node->getChildren() as $child) {
            if ($child->isToken()) {
                $token = $child->getValue()['token'];
                $value = $child->getValue()['value'];

                if ($token === 'identifier') {
                    $info['name'] = $value;
                    $info['raw'] = $value;
                    $this->registerIdentifier($value, 'identifier');
                } elseif ($token === 'btstring') {
                    $info['name'] = $value;
                    $info['raw'] = '`' . $value . '`';
                    $info['isEscaped'] = true;
                    $info['escapeType'] = 'backtick';
                    $this->registerEscapedIdentifier($value, '`' . $value . '`', 'backtick');
                } elseif ($token === 'dstring') {
                    $info['name'] = $value;
                    $info['raw'] = '"' . $value . '"';
                    $info['isEscaped'] = true;
                    $info['escapeType'] = 'double_quote';
                    $this->registerEscapedIdentifier($value, '"' . $value . '"', 'double_quote');
                } elseif ($token === 'brstring') {
                    $info['name'] = $value;
                    $info['raw'] = '[' . $value . ']';
                    $info['isEscaped'] = true;
                    $info['escapeType'] = 'bracket';
                    $this->registerEscapedIdentifier($value, '[' . $value . ']', 'bracket');
                }
            } elseif ($child->getId() === '#Identifier') {
                return $this->extractIdentifierInfo($child);
            }
        }

        return $info;
    }

    private function extractIdentifierValue(TreeNode $node): string
    {
        return $this->extractIdentifierInfo($node)['name'];
    }

    private function extractAlias(TreeNode $node): array
    {
        $alias = ['name' => '', 'raw' => ''];

        foreach ($node->getChildren() as $child) {
            if ($child->getId() === '#Identifier') {
                $info = $this->extractIdentifierInfo($child);
                $alias['name'] = $info['name'];
                $alias['raw'] = $info['raw'];
            }
        }

        return $alias;
    }

    private function extractExpression(TreeNode $node): array
    {
        $expr = [
            'text' => $this->extractText($node),
            'raw' => $this->rebuildFromNode($node),
            'type' => 'expression',
            'table' => null,
        ];

        // Detect expression type
        $first = $node->getChildrenNumber() > 0 ? $node->getChild(0) : null;
        if ($first) {
            $id = $first->getId();
            if ($id === '#ColumnIdentifier') {
                $expr['type'] = 'column';
                $expr['table'] = $this->extractColumnTable($first);
            } elseif (in_array($id, ['#Literal', '#string', '#number'])) {
                $expr['type'] = 'literal';
            } elseif ($id === '#FunctionCall') {
                $expr['type'] = 'function';
            } elseif ($id === '#AggregateExpression') {
                $expr['type'] = 'aggregate';
            } elseif ($id === '#Subselect') {
                $expr['type'] = 'subquery';
                $this->registerSubquery($first);
            }
        }

        return $expr;
    }

    private function extractColumnTable(TreeNode $node): ?string
    {
        // Check if column has table prefix (table.column)
        $identifiers = [];
        $this->collectIdentifiers($node, $identifiers);

        if (count($identifiers) >= 2) {
            return $identifiers[count($identifiers) - 2];
        }

        return null;
    }

    private function collectIdentifiers(TreeNode $node, array &$identifiers): void
    {
        if ($node->isToken()) {
            $token = $node->getValue()['token'];
            if (in_array($token, ['identifier', 'btstring', 'dstring', 'brstring'])) {
                $identifiers[] = $node->getValue()['value'];
            }
        } else {
            foreach ($node->getChildren() as $child) {
                $this->collectIdentifiers($child, $identifiers);
            }
        }
    }

    private function extractCondition(TreeNode $node): array
    {
        return [
            'text' => $this->extractText($node),
            'raw' => $this->rebuildFromNode($node),
            'parts' => $this->extractConditions($node),
        ];
    }

    private function extractConditions(TreeNode $node): array
    {
        $conditions = [];
        $this->collectConditions($node, $conditions);
        return $conditions;
    }

    private function collectConditions(TreeNode $node, array &$conditions): void
    {
        $id = $node->getId();

        if ($id === '#ComparisonExpression') {
            $conditions[] = $this->extractComparison($node);
        } elseif ($id === '#BetweenExpression') {
            $conditions[] = [
                'type' => 'between',
                'text' => $this->extractText($node),
                'raw' => $this->rebuildFromNode($node),
            ];
        } elseif ($id === '#LikeExpression') {
            $conditions[] = [
                'type' => 'like',
                'text' => $this->extractText($node),
                'raw' => $this->rebuildFromNode($node),
            ];
        } elseif ($id === '#InExpression') {
            $conditions[] = [
                'type' => 'in',
                'text' => $this->extractText($node),
                'raw' => $this->rebuildFromNode($node),
            ];
        } elseif ($id === '#ExistsExpression') {
            $cond = [
                'type' => 'exists',
                'text' => $this->extractText($node),
                'raw' => $this->rebuildFromNode($node),
            ];
            $conditions[] = $cond;
            $this->registerExistsExpression($node);
        } elseif ($id === '#NullComparisonExpression') {
            $conditions[] = [
                'type' => 'null_check',
                'text' => $this->extractText($node),
                'raw' => $this->rebuildFromNode($node),
            ];
        } else {
            foreach ($node->getChildren() as $child) {
                if (!$child->isToken()) {
                    $this->collectConditions($child, $conditions);
                }
            }
        }
    }

    private function extractComparison(TreeNode $node): array
    {
        $comparison = [
            'type' => 'comparison',
            'left' => '',
            'leftRaw' => '',
            'operator' => '',
            'right' => '',
            'rightRaw' => '',
        ];

        $exprIndex = 0;
        foreach ($node->getChildren() as $child) {
            $id = $child->getId();

            if ($id === '#ArithmeticExpression') {
                if ($exprIndex === 0) {
                    $comparison['left'] = $this->extractText($child);
                    $comparison['leftRaw'] = $this->rebuildFromNode($child);
                } else {
                    $comparison['right'] = $this->extractText($child);
                    $comparison['rightRaw'] = $this->rebuildFromNode($child);
                }
                $exprIndex++;
            } elseif ($id === '#ComparisonOperator') {
                $comparison['operator'] = $this->extractText($child);
            }
        }

        return $comparison;
    }

    // =========================================================================
    // REGISTRATION HELPERS
    // =========================================================================

    private function registerIdentifier(string $name, string $type): void
    {
        $this->identifiers[] = [
            'name' => $name,
            'type' => $type,
        ];
    }

    private function registerEscapedIdentifier(string $name, string $original, string $escapeType): void
    {
        $this->escapedIdentifiers[] = [
            'name' => $name,
            'original' => $original,
            'escapeType' => $escapeType,
        ];
    }

    private function registerSubquery(TreeNode $node): void
    {
        $this->subqueries[] = [
            'sql' => $this->rebuildFromNode($node),
            'node' => $node,
        ];
    }

    private function registerExistsExpression(TreeNode $node): void
    {
        $subquery = null;
        foreach ($node->getChildren() as $child) {
            if ($child->getId() === '#Subselect') {
                $subquery = $this->rebuildFromNode($child);
                $this->registerSubquery($child);
            }
        }

        $this->existsExpressions[] = [
            'sql' => $this->rebuildFromNode($node),
            'subquery' => $subquery,
        ];
    }

    private function scanForLiteralsAndParams(TreeNode $node): void
    {
        if ($node->isToken()) {
            $tokenData = $node->getValue();
            $token = $tokenData['token'];
            $value = $tokenData['value'];
            $offset = $tokenData['offset'] ?? 0;

            if ($token === 'string') {
                $literal = [
                    'type' => 'string',
                    'value' => $value,
                    'raw' => "'" . $value . "'",
                    'offset' => $offset,
                ];
                $this->literals[] = $literal;
                $this->orderedValues[] = array_merge($literal, ['category' => 'literal']);
            } elseif ($token === 'num_literal') {
                $literal = [
                    'type' => 'number',
                    'value' => $value,
                    'raw' => $value,
                    'offset' => $offset,
                ];
                $this->literals[] = $literal;
                $this->orderedValues[] = array_merge($literal, ['category' => 'literal']);
            } elseif (in_array($token, ['question_param', 'named_param', 'numbered_param', 'at_param', 'hash_param'])) {
                $param = [
                    'type' => $token,
                    'original' => $value,
                    'offset' => $offset,
                ];

                if ($token === 'named_param') {
                    $param['name'] = ltrim($value, ':');
                } elseif ($token === 'numbered_param') {
                    $param['position'] = (int) ltrim($value, '$');
                } elseif ($token === 'question_param') {
                    $param['position'] = count(array_filter($this->parameters, fn($p) => $p['type'] === 'question_param'));
                } elseif ($token === 'at_param') {
                    $param['name'] = ltrim($value, '@');
                } elseif ($token === 'hash_param') {
                    $param['name'] = ltrim($value, '#');
                }

                $this->parameters[] = $param;
                $this->orderedValues[] = array_merge($param, ['category' => 'parameter']);
            }
        } else {
            foreach ($node->getChildren() as $child) {
                $this->scanForLiteralsAndParams($child);
            }
        }
    }

    // =========================================================================
    // UTILITY HELPERS
    // =========================================================================

    private function extractText(TreeNode $node): string
    {
        if ($node->isToken()) {
            return $node->getValue()['value'];
        }

        $parts = [];
        foreach ($node->getChildren() as $child) {
            $text = $this->extractText($child);
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        // Handle qualified identifiers with periods
        $id = $node->getId();
        if (($id === '#ColumnIdentifier' || $id === '#QualifiedTableName') && count($parts) > 1) {
            return implode('.', $parts);
        }

        // Handle function calls with parentheses
        if ($id === '#FunctionCall') {
            $children = $node->getChildren();
            $firstChild = $children[0] ?? null;
            if ($firstChild && !$firstChild->isToken()) {
                $firstId = $firstChild->getId();
                if ($firstId === '#ExtractExpression' || $firstId === '#CastExpression') {
                    return $this->extractText($firstChild);
                }
            }
            $funcName = '';
            $argParts = [];
            foreach ($children as $child) {
                if (!$child->isToken() && $child->getId() === '#FunctionName') {
                    $funcName = $this->extractText($child);
                } elseif (!$child->isToken() && $child->getId() === '#ArgumentsList') {
                    $argParts[] = $this->extractText($child);
                } elseif ($child->isToken()) {
                    $argParts[] = $child->getValue()['value'];
                }
            }
            return $funcName . '(' . implode(' ', $argParts) . ')';
        }

        if ($id === '#ArgumentsList') {
            $items = [];
            foreach ($node->getChildren() as $child) {
                $items[] = $this->extractText($child);
            }
            return implode(', ', $items);
        }

        if ($id === '#CastExpression' || $id === '#ExtractExpression') {
            if (count($parts) > 1) {
                $keyword = array_shift($parts);
                return $keyword . '(' . implode(' ', $parts) . ')';
            }
        }

        return implode(' ', $parts);
    }

    private function rebuildFromNode(TreeNode $node): string
    {
        if ($node->isToken()) {
            $token = $node->getValue()['token'];
            $value = $node->getValue()['value'];

            // Handle special tokens with delimiters
            if ($token === 'string') return "'" . $value . "'";
            if ($token === 'btstring') return '`' . $value . '`';
            if ($token === 'dstring') return '"' . $value . '"';
            if ($token === 'brstring') return '[' . $value . ']';

            return $value;
        }

        $parts = [];
        foreach ($node->getChildren() as $child) {
            $text = $this->rebuildFromNode($child);
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        // Handle specific node types for proper formatting
        $id = $node->getId();

        // Only wrap subqueries in parentheses, not the main SelectQuery
        if ($id === '#Subselect') {
            return '(' . implode(' ', $parts) . ')';
        }

        // Join with periods for qualified names (table.column, schema.table, etc.)
        if ($id === '#ColumnIdentifier' && count($parts) > 1) {
            return implode('.', $parts);
        }
        if ($id === '#QualifiedTableName' && count($parts) > 1) {
            return implode('.', $parts);
        }

        // Handle FunctionCall - FunctionName(args)
        if ($id === '#FunctionCall') {
            // Children: FunctionName, optionally DISTINCT keyword, ArgumentsList or *
            // Or: ExtractExpression or CastExpression (already handles parentheses)
            $children = $node->getChildren();
            $firstChild = $children[0] ?? null;
            if ($firstChild && !$firstChild->isToken()) {
                $firstId = $firstChild->getId();
                if ($firstId === '#ExtractExpression' || $firstId === '#CastExpression') {
                    return $this->rebuildFromNode($firstChild);
                }
            }
            // Normal function: FunctionName( [DISTINCT] ArgumentsList | * )
            $funcName = '';
            $argParts = [];
            foreach ($children as $child) {
                if (!$child->isToken() && $child->getId() === '#FunctionName') {
                    $funcName = $this->rebuildFromNode($child);
                } elseif (!$child->isToken() && $child->getId() === '#ArgumentsList') {
                    $argParts[] = $this->rebuildFromNode($child);
                } elseif ($child->isToken()) {
                    $argParts[] = $child->getValue()['value'];
                }
            }
            return $funcName . '(' . implode(' ', $argParts) . ')';
        }

        // Handle ArgumentsList - comma separated
        if ($id === '#ArgumentsList') {
            $items = [];
            foreach ($node->getChildren() as $child) {
                $items[] = $this->rebuildFromNode($child);
            }
            return implode(', ', $items);
        }

        // Handle CastExpression - CAST(expr AS type) or CONVERT(type, expr)
        if ($id === '#CastExpression') {
            // First part is the keyword (CAST/CONVERT/TRY_CAST), rest goes in parentheses
            if (count($parts) > 1) {
                $keyword = array_shift($parts);
                return $keyword . '(' . implode(' ', $parts) . ')';
            }
            return implode(' ', $parts);
        }

        // Handle ExtractExpression - EXTRACT(field FROM expr)
        if ($id === '#ExtractExpression') {
            if (count($parts) > 1) {
                $keyword = array_shift($parts);
                return $keyword . '(' . implode(' ', $parts) . ')';
            }
            return implode(' ', $parts);
        }

        // Handle SELECT clause - join select expressions with comma
        if ($id === '#SelectClause') {
            // First part is SELECT keyword (and possibly ALL/DISTINCT)
            // Rest are SelectExpression nodes that need commas
            $result = '';
            $inExpressions = false;
            $exprParts = [];
            foreach ($parts as $part) {
                $upperPart = strtoupper($part);
                if (in_array($upperPart, ['SELECT', 'ALL', 'DISTINCT', 'TOP'])) {
                    $result .= ($result ? ' ' : '') . $part;
                } else {
                    $exprParts[] = $part;
                }
            }
            if ($exprParts) {
                $result .= ' ' . implode(', ', $exprParts);
            }
            return $result;
        }

        // Handle LIMIT clause - format as LIMIT offset, count or LIMIT count
        if ($id === '#LimitClause') {
            $numbers = [];
            $hasOffset = false;
            foreach ($parts as $part) {
                $upper = strtoupper($part);
                if ($upper === 'OFFSET') {
                    $hasOffset = true;
                } elseif (is_numeric($part)) {
                    $numbers[] = $part;
                }
            }
            if ($hasOffset && count($numbers) >= 2) {
                return 'LIMIT ' . $numbers[0] . ' OFFSET ' . $numbers[1];
            } elseif (count($numbers) >= 2) {
                return 'LIMIT ' . $numbers[0] . ', ' . $numbers[1];
            } elseif (count($numbers) >= 1) {
                return 'LIMIT ' . $numbers[0];
            }
            return implode(' ', $parts);
        }

        // Handle ORDER BY clause - comma separated order items
        if ($id === '#OrderByClause') {
            $result = 'ORDER BY ';
            $items = [];
            foreach ($node->getChildren() as $child) {
                if (!$child->isToken() && $child->getId() === '#OrderByItem') {
                    $items[] = $this->rebuildFromNode($child);
                }
            }
            return $result . implode(', ', $items);
        }

        // Handle GROUP BY clause - comma separated expressions
        if ($id === '#GroupByClause') {
            $result = 'GROUP BY ';
            $items = [];
            foreach ($node->getChildren() as $child) {
                if (!$child->isToken()) {
                    $items[] = $this->rebuildFromNode($child);
                }
            }
            return $result . implode(', ', $items);
        }

        return implode(' ', $parts);
    }

    private function nodeContains(TreeNode $node, string $searchId): bool
    {
        if ($node->getId() === $searchId) {
            return true;
        }

        foreach ($node->getChildren() as $child) {
            if (!$child->isToken() && $this->nodeContains($child, $searchId)) {
                return true;
            }
        }

        return false;
    }

    // =========================================================================
    // OUTPUT
    // =========================================================================

    /**
     * Convert result to array (complete structure with metadata)
     */
    public function toArray(): array
    {
        $output = $this->structure;

        // Add collected items as metadata
        $output['_meta'] = [
            'literals' => $this->literals,
            'identifiers' => $this->identifiers,
            'escapedIdentifiers' => $this->escapedIdentifiers,
            'parameters' => $this->parameters,
            'subqueries' => array_map(fn($s) => $s['sql'], $this->subqueries),
            'existsExpressions' => $this->existsExpressions,
        ];

        return $output;
    }

    /**
     * Convert result to JSON
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT): string
    {
        $output = $this->structure;

        // Add collected items
        $output['_meta'] = [
            'literals' => $this->literals,
            'identifiers' => $this->identifiers,
            'escapedIdentifiers' => $this->escapedIdentifiers,
            'parameters' => $this->parameters,
            'subqueries' => array_map(fn($s) => $s['sql'], $this->subqueries),
            'existsExpressions' => $this->existsExpressions,
        ];

        return json_encode($output, $flags | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Debug: dump the AST structure
     */
    public function dumpAst(): string
    {
        return $this->dumpNode($this->ast);
    }

    private function dumpNode(TreeNode $node, int $indent = 0): string
    {
        $prefix = str_repeat('  ', $indent);
        $output = '';

        if ($node->isToken()) {
            $value = $node->getValue();
            $output .= $prefix . "TOKEN: {$value['token']} = '{$value['value']}'\n";
        } else {
            $output .= $prefix . $node->getId() . " (" . $node->getChildrenNumber() . " children)\n";
            foreach ($node->getChildren() as $child) {
                $output .= $this->dumpNode($child, $indent + 1);
            }
        }

        return $output;
    }
}
