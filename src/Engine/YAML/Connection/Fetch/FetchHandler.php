<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\YAML\Connection\Fetch;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IFetchStrategy;
use GenericDatabase\Interfaces\Connection\IFlatFileFetch;
use GenericDatabase\Interfaces\Connection\IStructure;
use GenericDatabase\Abstract\AbstractFlatFileFetch;
use GenericDatabase\Engine\YAML\Connection\YAML;
use GenericDatabase\Engine\YAML\QueryBuilder\Regex;
use GenericDatabase\Generic\FlatFiles\DataProcessor;
use GenericDatabase\Helpers\Parsers\Schema;
use GenericDatabase\Engine\YAML\Connection\Structure\StructureHandler;

/**
 * Handles fetch operations for YAML connections.
 * Extends AbstractFlatFileFetch to leverage common flat-file fetch functionality.
 *
 * @package GenericDatabase\Engine\YAML\Connection\Fetch
 */
class FetchHandler extends AbstractFlatFileFetch implements IFlatFileFetch
{
    /**
     * Constructor.
     *
     * @param IConnection $instance The connection instance.
     * @param IFetchStrategy|null $strategy The fetch strategy (optional).
     * @param IStructure|null $structureHandler Structure handler.
     */
    public function __construct(
        IConnection $instance,
        ?IFetchStrategy $strategy = null,
        ?IStructure $structureHandler = null
    ) {
        // Create a default strategy if none provided
        $strategy = $strategy ?? new Strategy\FetchStrategy();
        parent::__construct($instance, $strategy, $structureHandler);
    }

    /**
     * Execute a stored query and return the result set.
     * Implementation of abstract method from AbstractFlatFileFetch.
     *
     * @return array The result set.
     */
    protected function executeStoredQuery(): array
    {
        // Get the stored query string and parameters
        $queryString = $this->getInstance()->getQueryString();
        $queryParameters = $this->getInstance()->getQueryParameters();

        // If no query is stored, return raw data from structure handler
        if (empty($queryString)) {
            $structureHandler = $this->getStructureHandler();
            if ($structureHandler !== null) {
                return $structureHandler->getData();
            }
            return [];
        }

        try {
            // Replace parameters in the query string
            $processedQuery = $this->replaceQueryParameters($queryString, $queryParameters);

            // Parse and execute the query
            $result = $this->parseAndExecuteQuery($processedQuery);

            // Update metadata with actual counts
            $rowCount = count($result);
            $columnCount = !empty($result) ? count((array) reset($result)) : 0;

            $this->getInstance()->setQueryRows($rowCount);
            $this->getInstance()->setQueryColumns($columnCount);
            $this->getInstance()->setAffectedRows(0);

            return $result;
        } catch (\Exception $e) {
            // Re-throw exception to help debugging - don't silently fail
            throw $e;
        }
    }

    /**
     * Replace query parameters with their values.
     *
     * @param string $query The query string.
     * @param array|null $parameters The parameters.
     * @return string The processed query.
     */
    private function replaceQueryParameters(string $query, ?array $parameters): string
    {
        if (empty($parameters)) {
            return $query;
        }

        // Check if parameters is a multidimensional array (batch operation)
        // In this case, we can't do simple replacement - return query as-is
        $firstValue = reset($parameters);
        if (is_array($firstValue) && !empty($firstValue)) {
            return $query;
        }

        foreach ($parameters as $key => $value) {
            $placeholder = is_int($key) ? '?' : $key;

            // Skip array values (shouldn't happen for SELECT, but safety check)
            if (is_array($value)) {
                continue;
            }

            // Format value based on type
            if (is_null($value)) {
                $formattedValue = 'NULL';
            } elseif (is_bool($value)) {
                $formattedValue = $value ? '1' : '0';
            } elseif (is_numeric($value)) {
                $formattedValue = (string) $value;
            } else {
                $formattedValue = "'" . addslashes((string) $value) . "'";
            }

            // Replace placeholder
            if (is_int($key)) {
                // Replace first occurrence of ? with the value
                $pos = strpos($query, '?');
                if ($pos !== false) {
                    $query = substr_replace($query, $formattedValue, $pos, 1);
                }
            } else {
                // Replace named parameter
                $query = str_replace($placeholder, $formattedValue, $query);
            }
        }

        return $query;
    }

    /**
     * Remove double quotes from identifiers in SQL query for parsing.
     * This allows regex patterns to work with queries that have quoted identifiers.
     * Only removes quotes from identifiers (table names, column names, aliases),
     * not from string literals.
     *
     * @param string $query The SQL query with quoted identifiers.
     * @return string The query with quotes removed from identifiers only.
     */
    private function unquoteIdentifiers(string $query): string
    {
        // Remove double quotes from identifiers (table names, column names, aliases)
        // Pattern matches: "identifier" where identifier is a valid SQL identifier
        // This will match: "table", "column", "alias", "table"."column", etc.
        // But won't match string literals in WHERE clauses (those are handled separately)

        // Replace quoted identifiers - matches word characters between double quotes
        // that are not inside single-quoted strings
        $result = '';
        $len = strlen($query);
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $i = 0;

        while ($i < $len) {
            $char = $query[$i];

            // Track single quotes (string literals) - don't process inside them
            if ($char === "'" && ($i === 0 || $query[$i - 1] !== '\\')) {
                $inSingleQuote = !$inSingleQuote;
                $result .= $char;
                $i++;
                continue;
            }

            // Process double quotes (identifiers); support SQL escaped quotes "" = one "
            if ($char === '"' && !$inSingleQuote) {
                $start = $i;
                $i++;
                $identifier = '';

                while ($i < $len) {
                    if ($i + 1 < $len && $query[$i] === '"' && $query[$i + 1] === '"') {
                        $identifier .= '"';
                        $i += 2;
                    } elseif ($query[$i] === '"') {
                        $i++;
                        break;
                    } else {
                        $identifier .= $query[$i];
                        $i++;
                    }
                }

                // Normalize identifier: strip surrounding quotes when escaped (""e"" -> e)
                while (strlen($identifier) >= 2 && $identifier[0] === '"' && $identifier[-1] === '"') {
                    $identifier = substr($identifier, 1, -1);
                    $identifier = str_replace('""', '"', $identifier);
                }

                if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
                    $result .= $identifier;
                } else {
                    $result .= substr($query, $start, $i - $start);
                }
            } else {
                $result .= $char;
                $i++;
            }
        }

        return $result;
    }

    /**
     * Parse and execute a SQL query using the DataProcessor directly.
     * Supports JOIN, GROUP BY, HAVING, aggregate functions (COUNT, SUM, AVG), DISTINCT,
     * ORDER BY, LIMIT, and result type casting to match SQLite (int/float).
     *
     * @param string $query The SQL query.
     * @return array The result set.
     */
    private function parseAndExecuteQuery(string $query): array
    {
        $query = trim($query);
        $structureHandler = $this->getStructureHandler();
        $queryForParsing = $this->unquoteIdentifiers($query);

        $database = $this->getDatabasePath();
        $parsed = $this->parseQueryComponents($queryForParsing);
        $selectSpecs = $parsed['select'];
        $isDistinct = $parsed['distinct'];
        $fromTable = $parsed['from'];
        $fromAlias = $parsed['from_alias'];
        $fromTables = $parsed['from_tables'] ?? [];
        $joinTable = $parsed['join_table'];
        $joinAlias = $parsed['join_alias'];
        $onCondition = $parsed['on_condition'];
        $whereClause = $parsed['where'];
        $groupByCols = $parsed['group_by'];
        $havingClause = $parsed['having'];
        $orderByCol = $parsed['order_by_col'];
        $orderDir = $parsed['order_by_dir'];
        $limit = $parsed['limit'];
        $offset = $parsed['offset'];

        // Multiple tables in FROM (comma-separated) – load all and build Cartesian product
        if (count($fromTables) >= 2) {
            $data = $this->loadTableData($structureHandler, $fromTables[0]['table'], $fromTables[0]['alias']);
            for ($i = 1; $i < count($fromTables); $i++) {
                $rightData = $this->loadTableData($structureHandler, $fromTables[$i]['table'], $fromTables[$i]['alias']);
                $data = $this->cartesianProduct($data, $rightData);
            }
            $joinTable = $fromTables[1]['table'] ?? null;
            $joinAlias = $fromTables[1]['alias'] ?? null;
        } else {
            $data = $this->loadTableData($structureHandler, $fromTable, $fromAlias);
            if ($joinTable !== null) {
                $rightData = $this->loadTableData($structureHandler, $joinTable, $joinAlias);
                $data = $this->executeJoin($data, $rightData, $fromAlias, $joinAlias, $onCondition);
            }
        }

        $processor = new DataProcessor($data);

        if ($whereClause !== null && $whereClause !== '') {
            $whereParsed = $this->parseWhereClauseWithLogic($whereClause);
            if (isset($whereParsed['groups'])) {
                // Mixed AND/OR: row matches if it matches any group (each group is AND of conditions)
                $data = array_values(array_filter($data, function ($row) use ($whereParsed) {
                    foreach ($whereParsed['groups'] as $groupConditions) {
                        $proc = new DataProcessor([(array) $row]);
                        $proc->where($groupConditions, 'AND');
                        if (!empty($proc->getData())) {
                            return true;
                        }
                    }
                    return false;
                }));
                $processor->setData($data);
            } else {
                $conditions = $whereParsed['conditions'];
                $whereLogic = $whereParsed['logic'];
                if (!empty($conditions)) {
                    $processor->where($conditions, $whereLogic);
                }
            }
        }

        $data = $processor->getData();

        if (!empty($groupByCols)) {
            $result = $this->executeGroupByAggregate(
                $data,
                $groupByCols,
                $selectSpecs,
                $havingClause,
                $fromAlias,
                $joinAlias
            );
        } else {
            $processor->setData($data);
            $skipSelect = count($selectSpecs) === 1 && ($selectSpecs[0]['expr'] ?? '') === '*';

            if ($isDistinct) {
                // DISTINCT: project first, then distinct, then order, then limit (so LIMIT applies to distinct rows)
                if (!$skipSelect) {
                    $result = $this->applySelectColumns($processor->getData(), $selectSpecs, $fromAlias, $joinAlias);
                } else {
                    $result = $processor->getData();
                }
                $result = $this->applyDistinct($result);
                if ($orderByCol !== null) {
                    $result = $this->applyOrderBy($result, $orderByCol, $orderDir === 'DESC');
                }
                if ($limit !== null) {
                    $result = array_slice($result, (int) ($offset ?? 0), (int) $limit);
                }
            } else {
                if ($orderByCol !== null) {
                    $processor->orderBy($orderByCol, $orderDir === 'DESC' ? DataProcessor::DESC : DataProcessor::ASC);
                }
                if ($limit !== null) {
                    $processor->limit((int) $limit, (int) ($offset ?? 0));
                }
                $result = $processor->getData();
                if (!$skipSelect) {
                    $result = $this->applySelectColumns($result, $selectSpecs, $fromAlias, $joinAlias);
                }
            }
        }

        if (!empty($groupByCols)) {
            if ($orderByCol !== null) {
                $sortCol = $this->resolveOrderByColumn($orderByCol, $selectSpecs);
                $result = $this->applyOrderBy($result, $sortCol, $orderDir === 'DESC');
            }
            if ($limit !== null) {
                $result = array_slice($result, (int) ($offset ?? 0), (int) $limit);
            }
        }

        return $this->applyResultTypes($result, $selectSpecs, $fromTable, $joinTable, $fromAlias, $joinAlias, $database);
    }

    private function getDatabasePath(): string
    {
        $h = $this->getStructureHandler();
        if ($h instanceof StructureHandler) {
            $v = $h->get('database');
            return $v !== null ? (string) $v : '';
        }
        return '';
    }

    /**
     * Output key for a select spec: alias if present, otherwise unqualified column (no table prefix).
     * Generic examples: "t.col" with no alias -> "col"; "a.id AS ref_id" -> "ref_id".
     */
    private function outputKeyForSelectSpec(array $spec): string
    {
        $alias = $spec['alias'] ?? null;
        if ($alias !== null && $alias !== '') {
            return $alias;
        }
        $expr = $spec['expr'] ?? '';
        if (preg_match('/^\w+\.(\w+)$/', $expr, $m)) {
            return $m[1];
        }
        return $expr;
    }

    /**
     * @return array{select: list<array{expr: string, alias: string|null, aggregate: string|null}>,
     *     distinct: bool, from: string, from_alias: string|null, join_table: string|null,
     *     join_alias: string|null, on_condition: array{left: string, right: string}|null,
     *     where: string|null, group_by: list<string>, having: array{agg: string, op: string, val: mixed}|null,
     *     order_by_col: string|null, order_by_dir: string, limit: int|null, offset: int|null}
     */
    private function parseQueryComponents(string $q): array
    {
        $out = [
            'select' => [],
            'distinct' => false,
            'from' => '',
            'from_alias' => null,
            'from_tables' => [],  // multiple tables from "t1 a, t2 b" (Cartesian product)
            'join_table' => null,
            'join_alias' => null,
            'on_condition' => null,
            'where' => null,
            'group_by' => [],
            'having' => null,
            'order_by_col' => null,
            'order_by_dir' => 'ASC',
            'limit' => null,
            'offset' => null,
        ];

        if (!preg_match('/^\s*SELECT\s+/i', $q)) {
            return $out;
        }

        $rest = preg_replace('/^\s*SELECT\s+/i', '', $q, 1);
        if (preg_match('/^\s*DISTINCT\s+/i', $rest)) {
            $out['distinct'] = true;
            $rest = preg_replace('/^\s*DISTINCT\s+/i', '', $rest, 1);
        }
        if (!preg_match('/\s+FROM\s+/i', $rest)) {
            return $out;
        }
        $parts = preg_split('/\s+FROM\s+/i', $rest, 2);
        $selectStr = trim($parts[0]);
        $fromStr = trim($parts[1]);

        $columnParts = $this->splitSelectColumns($selectStr);
        foreach ($columnParts as $col) {
            $col = trim($col);
            if ($col === '' || $col === '*') {
                $out['select'][] = ['expr' => '*', 'alias' => null, 'aggregate' => null];
                continue;
            }
            $alias = null;
            if (preg_match('/^(.+?)\s+AS\s+(\w+)\s*$/i', $col, $m)) {
                $expr = trim($m[1]);
                $alias = trim($m[2], '"\'`');
            } else {
                $expr = $col;
            }
            $agg = null;
            if (preg_match('/^(COUNT|SUM|AVG|MIN|MAX)\s*\(\s*(?:(\w+)\.)?(\w+|\*)\s*\)$/i', $expr, $m)) {
                $agg = strtoupper($m[1]);
            }
            $out['select'][] = ['expr' => $expr, 'alias' => $alias, 'aggregate' => $agg];
        }

        $fromStr = $this->extractFromJoin($fromStr, $out);
        $this->extractWhereGroupHavingOrderLimit($fromStr, $out);

        return $out;
    }

    private function splitSelectColumns(string $s): array
    {
        $out = [];
        $len = strlen($s);
        $cur = '';
        $depth = 0;
        for ($i = 0; $i < $len; $i++) {
            $c = $s[$i];
            if ($c === '(') {
                $depth++;
                $cur .= $c;
            } elseif ($c === ')') {
                $depth--;
                $cur .= $c;
            } elseif (($c === ',') && $depth === 0) {
                $out[] = trim($cur);
                $cur = '';
            } else {
                $cur .= $c;
            }
        }
        if ($cur !== '') {
            $out[] = trim($cur);
        }
        return $out;
    }

    private const RESERVED_WORDS = ['order', 'group', 'where', 'having', 'limit', 'by', 'on', 'inner', 'join', 'left', 'right', 'outer', 'and', 'or', 'asc', 'desc'];

    private function extractFromJoin(string $fromStr, array &$out): string
    {
        // Extract FROM clause content (until WHERE, GROUP BY, ORDER BY, LIMIT)
        if (preg_match('/^(.+?)(?=\s+WHERE\s+|\s+GROUP\s+BY\s+|\s+ORDER\s+BY\s+|\s+LIMIT\s+|$)/is', $fromStr, $fromMatch)) {
            $fromClause = trim($fromMatch[1]);
        } else {
            $fromClause = $fromStr;
        }
        $afterFrom = trim(preg_replace('/^.+?(?=\s+WHERE\s+|\s+GROUP\s+BY\s+|\s+ORDER\s+BY\s+|\s+LIMIT\s+|$)/is', '', $fromStr, 1));

        // Check for explicit JOIN ... ON (single pattern: table alias JOIN table alias ON cond)
        $joinPattern = '/^(\w+)(?:\s+(\w+))?(?:\s+(?:INNER\s+)?JOIN\s+(\w+)(?:\s+(\w+))?\s+ON\s+(.+?))$/is';
        if (preg_match($joinPattern, $fromClause, $m)) {
            $out['from'] = $m[1];
            $second = isset($m[2]) && $m[2] !== '' ? $m[2] : null;
            if ($second !== null && in_array(strtolower($second), self::RESERVED_WORDS, true)) {
                $second = null;
            }
            $out['from_alias'] = $second;
            $out['join_table'] = $m[3];
            $out['join_alias'] = isset($m[4]) && $m[4] !== '' ? $m[4] : null;
            $on = trim($m[5]);
            if (preg_match('/^(\w+\.\w+)\s*=\s*(\w+\.\w+)\s*$/i', $on, $onM)) {
                $out['on_condition'] = ['left' => trim($onM[1]), 'right' => trim($onM[2])];
            }
            return $afterFrom;
        }

        if (str_contains($fromClause, ',')) {
            $out['from_tables'] = [];
            foreach (array_map('trim', explode(',', $fromClause)) as $part) {
                if ($part === '') {
                    continue;
                }
                if (preg_match('/^(\w+)\s+(\w+)\s*$/', $part, $tm)) {
                    $alias = in_array(strtolower($tm[2]), self::RESERVED_WORDS, true) ? null : $tm[2];
                    $out['from_tables'][] = ['table' => $tm[1], 'alias' => $alias];
                } else {
                    $out['from_tables'][] = ['table' => $part, 'alias' => null];
                }
            }
            if (!empty($out['from_tables'])) {
                $first = $out['from_tables'][0];
                $out['from'] = $first['table'];
                $out['from_alias'] = $first['alias'];
            }
            return $afterFrom;
        }

        // Single table [alias]
        $singlePattern = '/^(\w+)(?:\s+(\w+))?$/';
        if (preg_match($singlePattern, $fromClause, $m)) {
            $out['from'] = $m[1];
            $second = isset($m[2]) && $m[2] !== '' ? $m[2] : null;
            if ($second !== null && in_array(strtolower($second), self::RESERVED_WORDS, true)) {
                $second = null;
            }
            $out['from_alias'] = $second;
            return $afterFrom;
        }

        return $afterFrom;
    }

    private function extractWhereGroupHavingOrderLimit(string $s, array &$out): void
    {
        $where = null;
        $groupBy = [];
        $having = null;
        $orderCol = null;
        $orderDir = 'ASC';
        $limit = null;
        $offset = null;

        if (preg_match('/\bWHERE\s+(.+?)(?=\s+GROUP\s+BY|\s+ORDER\s+BY|\s+LIMIT|$)/is', $s, $m)) {
            $where = trim($m[1]);
        }
        if (preg_match('/\bGROUP\s+BY\s+(.+?)(?=\s+HAVING|\s+ORDER\s+BY|\s+LIMIT|$)/is', $s, $m)) {
            $groupBy = array_map('trim', explode(',', trim($m[1])));
        }
        if (preg_match('/\bHAVING\s+(.+?)(?=\s+ORDER\s+BY|\s+LIMIT|$)/is', $s, $m)) {
            $hav = trim($m[1]);
            if (preg_match('/^(COUNT|SUM|AVG|MIN|MAX)\s*\(\s*(?:\w+\.)?(\w+|\*)\s*\)\s*(>=|<=|!=|<>|>|<|=)\s*(\d+\.?\d*)\s*$/i', $hav, $hm)) {
                $having = ['agg' => strtoupper($hm[1]), 'op' => $hm[3], 'val' => strpos((string) $hm[4], '.') !== false ? (float) $hm[4] : (int) $hm[4]];
            }
        }
        if (preg_match('/\bORDER\s+BY\s+(.+?)(?=\s+LIMIT|$)/is', $s, $m)) {
            $ob = trim($m[1]);
            if (preg_match('/^(.+?)\s+(ASC|DESC)\s*$/i', $ob, $om)) {
                $orderCol = trim($om[1]);
                $orderDir = strtoupper($om[2]);
            } else {
                $orderCol = $ob;
            }
        }
        if (preg_match('/\bLIMIT\s+(\d+)\s+OFFSET\s+(\d+)\s*$/i', $s, $m)) {
            $limit = (int) $m[1];
            $offset = (int) $m[2];
        } elseif (preg_match('/\bLIMIT\s+(\d+)(?:\s*,\s*(\d+))?\s*$/i', $s, $m)) {
            if (isset($m[2]) && $m[2] !== '') {
                $offset = (int) $m[1];
                $limit = (int) $m[2];
            } else {
                $limit = (int) $m[1];
            }
        }

        $out['where'] = $where;
        $out['group_by'] = $groupBy;
        $out['having'] = $having;
        $out['order_by_col'] = $orderCol;
        $out['order_by_dir'] = $orderDir;
        $out['limit'] = $limit;
        $out['offset'] = $offset;
    }

    /**
     * Build Cartesian product of two result sets (each row of left × each row of right, merged).
     *
     * @param array $left Rows with prefixed keys (e.g. e.id, e.nome).
     * @param array $right Rows with prefixed keys (e.g. c.id, c.nome).
     * @return array Cartesian product rows (merged associative arrays).
     */
    private function cartesianProduct(array $left, array $right): array
    {
        if (empty($left) || empty($right)) {
            return [];
        }
        $result = [];
        foreach ($left as $lRow) {
            $lRow = (array) $lRow;
            foreach ($right as $rRow) {
                $rRow = (array) $rRow;
                $result[] = $lRow + $rRow;
            }
        }
        return $result;
    }

    private function loadTableData(?IStructure $handler, string $table, ?string $alias): array
    {
        if ($handler === null) {
            return [];
        }
        $handler->load($table);
        $rows = $handler->getData();
        if ($alias === null || $alias === '') {
            return $rows;
        }
        $prefixed = [];
        foreach ($rows as $row) {
            $r = [];
            foreach ((array) $row as $k => $v) {
                $r[$alias . '.' . $k] = $v;
            }
            $prefixed[] = $r;
        }
        return $prefixed;
    }

    private function executeJoin(array $left, array $right, ?string $leftAlias, ?string $rightAlias, ?array $on): array
    {
        if ($on === null || $leftAlias === null || $rightAlias === null) {
            return $left;
        }
        $lCol = $on['left'];
        $rCol = $on['right'];
        if (str_starts_with($rCol, $rightAlias . '.')) {
            $rightKey = $rCol;
            $leftKey = $lCol;
        } else {
            $rightKey = $lCol;
            $leftKey = $rCol;
        }
        $joined = [];
        foreach ($left as $lRow) {
            $lVal = $lRow[$leftKey] ?? null;
            foreach ($right as $rRow) {
                if (($rRow[$rightKey] ?? null) == $lVal) {
                    $joined[] = $lRow + $rRow;
                }
            }
        }
        return $joined;
    }

    private function executeGroupByAggregate(
        array $data,
        array $groupByCols,
        array $selectSpecs,
        ?array $having,
        ?string $fromAlias,
        ?string $joinAlias
    ): array {
        $groups = [];
        foreach ($data as $row) {
            $row = (array) $row;
            $keyParts = [];
            foreach ($groupByCols as $c) {
                $keyParts[] = $row[$c] ?? null;
            }
            $key = serialize($keyParts);
            if (!isset($groups[$key])) {
                $groups[$key] = ['__key__' => $keyParts, '__rows__' => []];
            }
            $groups[$key]['__rows__'][] = $row;
        }

        $result = [];
        foreach ($groups as $g) {
            $rows = $g['__rows__'];
            $first = $rows[0];
            $outRow = [];

            foreach ($selectSpecs as $spec) {
                $expr = $spec['expr'];
                $alias = $spec['alias'];
                $agg = $spec['aggregate'] ?? null;

                if ($agg !== null) {
                    $col = null;
                    if (preg_match('/^(?:COUNT|SUM|AVG|MIN|MAX)\s*\(\s*(?:(\w+)\.)?(\w+|\*)\s*\)$/i', $expr, $m)) {
                        $col = (array_key_exists(1, $m) && $m[1] !== '') ? $m[1] . '.' . $m[2] : $m[2];
                    }
                    if ($col === '*') {
                        $firstRow = reset($rows);
                        $rowKeys = array_keys($firstRow instanceof \stdClass ? (array) $firstRow : (array) $firstRow);
                        $pk = Schema::getPrimaryKeyFromRowKeys($rowKeys);
                        $col = $joinAlias ? $joinAlias . '.' . $pk : $pk;
                    }
                    $values = array_column($rows, $col);
                    $values = array_filter($values, fn($v) => $v !== null);
                    $val = match (strtoupper($agg)) {
                        'COUNT' => count($values),
                        'SUM' => array_sum($values),
                        'AVG' => count($values) > 0 ? array_sum($values) / count($values) : 0,
                        'MIN' => count($values) > 0 ? min($values) : null,
                        'MAX' => count($values) > 0 ? max($values) : null,
                        default => null,
                    };
                    $k = $this->outputKeyForSelectSpec($spec);
                    $outRow[$k] = $val;
                } else {
                    $k = $this->outputKeyForSelectSpec($spec);
                    $lookup = $expr;
                    if (!isset($first[$expr]) && preg_match('/^(\w+)\.(\w+)$/', $expr, $mx)) {
                        $lookup = $expr;
                    }
                    $outRow[$k] = $first[$lookup] ?? $first[$expr] ?? null;
                }
            }

            if ($having !== null) {
                $aggCol = $having['agg'];
                $op = $having['op'];
                $havVal = $having['val'];
                $candidate = null;
                foreach ($selectSpecs as $s) {
                    if (($s['aggregate'] ?? null) === $aggCol) {
                        $candidate = $outRow[$this->outputKeyForSelectSpec($s)] ?? null;
                        break;
                    }
                }
                $ok = match ($op) {
                    '>' => $candidate > $havVal,
                    '>=' => $candidate >= $havVal,
                    '<' => $candidate < $havVal,
                    '<=' => $candidate <= $havVal,
                    '=', '==' => $candidate == $havVal,
                    '!=', '<>' => $candidate != $havVal,
                    default => true,
                };
                if (!$ok) {
                    continue;
                }
            }

            $result[] = $outRow;
        }

        return $result;
    }

    private function applySelectColumns(array $data, array $selectSpecs, ?string $fromAlias, ?string $joinAlias): array
    {
        return array_map(function ($row) use ($selectSpecs) {
            $row = (array) $row;
            $result = [];
            foreach ($selectSpecs as $spec) {
                $expr = $spec['expr'];
                $alias = $spec['alias'];
                if ($expr === '*') {
                    foreach ($row as $k => $v) {
                        $result[$k] = $v;
                    }
                    continue;
                }
                // table.* wildcard: copy columns using unprefixed names (SQLite-style). Same column name = last wins.
                if (preg_match('/^(\w+)\.\*$/', $expr, $starM)) {
                    $prefix = $starM[1] . '.';
                    foreach ($row as $k => $v) {
                        if (str_starts_with((string) $k, $prefix)) {
                            $colName = substr((string) $k, strlen($prefix));
                            $result[$colName] = $v;
                        }
                    }
                    continue;
                }
                $key = $this->outputKeyForSelectSpec($spec);
                if (isset($row[$expr])) {
                    $result[$key] = $row[$expr];
                } elseif (preg_match('/^(\w+)\.(\w+)$/', $expr, $m)) {
                    $result[$key] = $row[$expr] ?? $row[$m[2]] ?? null;
                } else {
                    $result[$key] = $row[$expr] ?? null;
                }
            }
            return $result;
        }, $data);
    }

    private function applyDistinct(array $data): array
    {
        $seen = [];
        $out = [];
        foreach ($data as $row) {
            $k = serialize($row);
            if (!isset($seen[$k])) {
                $seen[$k] = true;
                $out[] = $row;
            }
        }
        return $out;
    }

    /**
     * Resolve ORDER BY expression to the actual key used in result rows.
     * For GROUP BY, rows use alias (e.g. agg_alias) not aggregate expr (e.g. COUNT(t.id)).
     */
    private function resolveOrderByColumn(string $orderByCol, array $selectSpecs): string
    {
        $norm = fn(string $s) => strtolower(preg_replace('/\s+/', '', trim($s)));
        $needle = $norm($orderByCol);
        foreach ($selectSpecs as $spec) {
            $outKey = $this->outputKeyForSelectSpec($spec);
            if ($needle === $norm($outKey)) {
                return $outKey;
            }
            $expr = $spec['expr'] ?? '';
            if ($expr !== '' && $needle === $norm($expr)) {
                return $outKey;
            }
        }
        return $orderByCol;
    }

    private function applyOrderBy(array $data, string $column, bool $desc): array
    {
        usort($data, function ($a, $b) use ($column, $desc) {
            $a = (array) $a;
            $b = (array) $b;
            $av = $a[$column] ?? null;
            $bv = $b[$column] ?? null;
            $c = $av <=> $bv;
            return $desc ? -$c : $c;
        });
        return $data;
    }

    private function applyResultTypes(
        array $rows,
        array $selectSpecs,
        string $fromTable,
        ?string $joinTable,
        ?string $fromAlias,
        ?string $joinAlias,
        string $database
    ): array {
        $schemaFrom = $database !== '' && Schema::exists($database) ? Schema::getSchemaForFile($database, $fromTable) : null;
        $schemaJoin = $joinTable !== null && $database !== '' && Schema::exists($database)
            ? Schema::getSchemaForFile($database, $joinTable) : null;

        $typeMap = [];
        foreach ($selectSpecs as $s) {
            $outKey = $this->outputKeyForSelectSpec($s);
            $agg = $s['aggregate'] ?? null;
            if ($agg !== null) {
                $typeMap[$outKey] = match (strtoupper($agg)) {
                    'AVG' => 'float',
                    'COUNT', 'SUM' => 'int',
                    default => 'number',
                };
                continue;
            }
            $expr = $s['expr'];
            if (preg_match('/^(\w+)\.(\w+)$/', $expr, $m)) {
                $tbl = $m[1];
                $col = $m[2];
                $schema = ($fromAlias !== null && $tbl === $fromAlias) ? $schemaFrom : $schemaJoin;
                if ($schema !== null && !empty($schema['columns'])) {
                    foreach ($schema['columns'] as $def) {
                        if (isset($def['name']) && strcasecmp($def['name'], $col) === 0) {
                            $typeMap[$outKey] = strtolower($def['type'] ?? 'text');
                            break;
                        }
                    }
                }
            } else {
                $schema = $schemaFrom ?? $schemaJoin;
                if ($schema !== null && !empty($schema['columns'])) {
                    foreach ($schema['columns'] as $def) {
                        if (isset($def['name']) && strcasecmp($def['name'], $expr) === 0) {
                            $typeMap[$outKey] = strtolower($def['type'] ?? 'text');
                            break;
                        }
                    }
                }
            }
            $typeMap[$outKey] = $typeMap[$outKey] ?? 'text';
        }

        $out = [];
        foreach ($rows as $row) {
            $r = [];
            foreach ((array) $row as $k => $v) {
                $t = $typeMap[$k] ?? null;
                if ($v !== null && $v !== '' && is_numeric($v) && ($t === null || $t === 'text')) {
                    $t = strpos((string) $v, '.') !== false ? 'float' : 'int';
                }
                if ($t === 'integer' || $t === 'int') {
                    $r[$k] = $v === null ? null : (int) $v;
                } elseif ($t === 'float' || $t === 'double' || $t === 'number') {
                    $r[$k] = $v === null ? null : (float) $v;
                } elseif ($v !== null && $v !== '' && is_string($v) && is_numeric($v)) {
                    $r[$k] = strpos($v, '.') !== false ? (float) $v : (int) $v;
                } else {
                    $r[$k] = $v;
                }
            }
            $out[] = $r;
        }
        return $out;
    }

    /**
     * Split WHERE clause by OR operator (word boundary), for use when clause uses OR logic.
     *
     * @param string $clause The clause to split.
     * @return array The split conditions (each trimmed).
     */
    private function splitByTopLevelOrOperator(string $clause): array
    {
        $parts = [];
        $current = '';
        $index = 0;
        $len = strlen($clause);

        while ($index < $len) {
            if (strtoupper(substr($clause, $index, 2)) === 'OR') {
                $beforeOk = ($index === 0) || !ctype_alnum($clause[$index - 1]);
                $afterOk = ($index + 2 >= $len) || !ctype_alnum($clause[$index + 2]);

                if ($beforeOk && $afterOk) {
                    if (!empty(trim($current))) {
                        $parts[] = trim($current);
                    }
                    $current = '';
                    $index += 2;
                    while ($index < $len && ctype_space($clause[$index])) {
                        $index++;
                    }
                    continue;
                }
            }

            $current .= $clause[$index];
            $index++;
        }

        if (!empty(trim($current))) {
            $parts[] = trim($current);
        }

        return !empty($parts) ? $parts : [$clause];
    }

    /**
     * Parse WHERE clause. Supports mixed AND/OR: split by OR first, then each part by AND.
     * Returns groups: (group1_cond1 AND group1_cond2 AND ...) OR (group2_cond1 AND ...).
     *
     * @param string $whereClause The WHERE clause string.
     * @return array{conditions: array, logic: string, groups?: array} For single logic: conditions+logic. For mixed: groups (array of condition arrays).
     */
    private function parseWhereClauseWithLogic(string $whereClause): array
    {
        $whereClause = $this->unquoteIdentifiers($whereClause);

        $hasOr = (bool) preg_match('/\bOR\b/i', $whereClause);
        $hasAnd = (bool) preg_match('/\bAND\b/i', $whereClause);

        // Mixed AND/OR: split by OR, then each part by AND (each OR-segment is AND of conditions)
        if ($hasOr && $hasAnd) {
            $orParts = $this->splitByTopLevelOrOperator($whereClause);
            $groups = [];
            foreach ($orParts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                $andParts = $this->splitByTopLevelAndOperator($part);
                $groupConditions = [];
                foreach ($andParts as $sub) {
                    $sub = trim($sub);
                    if ($sub === '') {
                        continue;
                    }
                    $cond = $this->parseOneWhereCondition($sub);
                    if ($cond !== null) {
                        $groupConditions[] = $cond;
                    }
                }
                if (!empty($groupConditions)) {
                    $groups[] = $groupConditions;
                }
            }
            return ['groups' => $groups, 'group_logic' => 'OR'];
        }

        // Single logic: all OR or all AND
        $parts = $hasOr
            ? $this->splitByTopLevelOrOperator($whereClause)
            : $this->splitByTopLevelAndOperator($whereClause);
        $logic = $hasOr ? 'OR' : 'AND';

        $conditions = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }
            $cond = $this->parseOneWhereCondition($part);
            if ($cond !== null) {
                $conditions[] = $cond;
            }
        }

        return ['conditions' => $conditions, 'logic' => $logic];
    }

    /**
     * Parse a single condition string (e.g. "e.id = 10") into a condition array for DataProcessor.
     */
    private function parseOneWhereCondition(string $part): ?array
    {
        $parsed = Regex::parseWhereCondition($part);
        if ($parsed === null) {
            return null;
        }
        $column = $parsed['column'];
        if (isset($parsed['prefix']) && $parsed['prefix'] !== null && $parsed['prefix'] !== '') {
            $column = $parsed['prefix'] . '.' . $column;
        }
        $condition = [
            'column' => $column,
            'operator' => $parsed['operator'],
        ];
        switch (strtoupper($parsed['operator'])) {
            case 'BETWEEN':
            case 'NOT BETWEEN':
                $condition['value'] = [
                    'min' => $parsed['value'],
                    'max' => $parsed['value2'] ?? $parsed['value']
                ];
                break;
            case 'IN':
            case 'NOT IN':
                $values = array_map(function ($val) {
                    return trim(trim($val), "'\"");
                }, explode(',', $parsed['value']));
                $condition['value'] = $values;
                break;
            case 'LIKE':
            case 'NOT LIKE':
                $condition['value'] = $parsed['value'];
                break;
            default:
                $condition['value'] = $parsed['value'];
                break;
        }
        return $condition;
    }

    /**
     * Parse WHERE clause and return conditions array for DataProcessor (backward compat).
     *
     * @param string $whereClause The WHERE clause string.
     * @return array The conditions array.
     */
    private function parseWhereClause(string $whereClause): array
    {
        $result = $this->parseWhereClauseWithLogic($whereClause);
        return $result['conditions'] ?? [];
    }

    /**
     * Internal fetch for numeric array - preserve native types (int/float) from result set.
     *
     * @return array|false|null
     */
    public function internalFetchNum(): array|false|null
    {
        $results = $this->getResultSet();

        if ($this->cursor >= count($results)) {
            return false;
        }

        $row = $results[$this->cursor++];
        return array_values((array) $row);
    }

    /**
     * Internal fetch for both mode - preserve native types (int/float) from result set.
     *
     * @return bool|array
     */
    public function internalFetchBoth(): bool|array
    {
        $results = $this->getResultSet();

        if ($this->cursor >= count($results)) {
            return false;
        }

        $row = $results[$this->cursor++];
        return $this->formatBothMode((array) $row);
    }

    /**
     * Internal fetch for column - preserve native types (int/float) from result set.
     *
     * @param int $columnIndex
     * @return mixed
     */
    public function internalFetchColumn(int $columnIndex = 0): mixed
    {
        $results = $this->getResultSet();

        if ($this->cursor >= count($results)) {
            return false;
        }

        $row = $results[$this->cursor++];
        $values = array_values((array) $row);
        return isset($values[$columnIndex]) ? $values[$columnIndex] : false;
    }

    /**
     * Internal fetch all for numeric array - preserve native types (int/float) from result set.
     *
     * @return array
     */
    public function internalFetchAllNum(): array
    {
        $results = $this->getResultSet();
        $result = [];
        foreach ($results as $row) {
            $result[] = array_values((array) $row);
        }
        return $result;
    }

    /**
     * Internal fetch all for both mode - preserve native types (int/float) from result set.
     *
     * @return array
     */
    public function internalFetchAllBoth(): array
    {
        $results = $this->getResultSet();
        $result = [];
        foreach ($results as $row) {
            $result[] = $this->formatBothMode((array) $row);
        }
        return $result;
    }

    /**
     * Internal fetch all for column - preserve native types (int/float) from result set.
     *
     * @param int $columnIndex
     * @return array
     */
    public function internalFetchAllColumn(int $columnIndex = 0): array
    {
        $results = $this->getResultSet();
        $result = [];
        foreach ($results as $row) {
            $values = array_values((array) $row);
            if (isset($values[$columnIndex])) {
                $result[] = $values[$columnIndex];
            }
        }
        return $result;
    }

    /**
     * Fetch the next row from the result set.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @param mixed|null $optArgs Additional options.
     * @return mixed The fetched row or false if no more rows.
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $fetch = $fetchStyle ?? YAML::FETCH_ASSOC;

        return match ($fetch) {
            YAML::FETCH_OBJ => $this->internalFetchAssoc() !== false ? (object) $this->getResultSet()[$this->cursor - 1] : false,
            YAML::FETCH_INTO => $this->fetchIntoObject($fetchArgument),
            YAML::FETCH_CLASS => $this->internalFetchClass($optArgs, $fetchArgument ?? '\stdClass'),
            YAML::FETCH_COLUMN => $this->internalFetchColumn($fetchArgument ?? 0),
            YAML::FETCH_ASSOC => $this->internalFetchAssoc(),
            YAML::FETCH_NUM => $this->internalFetchNum(),
            default => $this->internalFetchBoth(),
        };
    }

    /**
     * Fetch all rows from the result set.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @param mixed|null $optArgs Additional options.
     * @return array|bool The fetched rows or false on failure.
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        $fetch = $fetchStyle ?? YAML::FETCH_ASSOC;

        return match ($fetch) {
            YAML::FETCH_OBJ => array_map(fn($row) => (object) $row, $this->internalFetchAllAssoc()),
            YAML::FETCH_INTO => $this->fetchAllIntoObjects($fetchArgument),
            YAML::FETCH_CLASS => $this->internalFetchAllClass($optArgs, $fetchArgument ?? '\stdClass'),
            YAML::FETCH_COLUMN => $this->internalFetchAllColumn($fetchArgument ?? 0),
            YAML::FETCH_ASSOC => $this->internalFetchAllAssoc(),
            YAML::FETCH_NUM => $this->internalFetchAllNum(),
            default => $this->internalFetchAllBoth(),
        };
    }

    /**
     * Fetch row into an existing object.
     *
     * @param object|null $object The object to populate.
     * @return object|false The populated object or false.
     */
    private function fetchIntoObject(?object $object): object|false
    {
        $row = $this->internalFetchAssoc();
        if ($row === false) {
            return false;
        }
        return $this->fetchInto($row, $object);
    }

    /**
     * Fetch all rows into objects.
     * When object is null (FETCH_INTO not configured), behave like FETCH_OBJ.
     *
     * @param object|null $object The object template.
     * @return array The populated objects.
     */
    private function fetchAllIntoObjects(?object $object): array
    {
        $results = $this->internalFetchAllAssoc();
        if ($object === null) {
            return array_map(fn($row) => (object) $row, $results);
        }
        return array_map(fn($row) => $this->fetchInto($row, clone $object), $results);
    }

    /**
     * Split WHERE clause by AND operator, but preserve BETWEEN...AND constructs.
     *
     * @param string $clause The clause to split.
     * @return array The split conditions.
     */
    private function splitByTopLevelAndOperator(string $clause): array
    {
        $parts = [];
        $current = '';
        $index = 0;
        $len = strlen($clause);
        $inBetween = false;

        while ($index < $len) {
            // Check for BETWEEN keyword (start of BETWEEN...AND construct)
            if (!$inBetween && preg_match('/\bBETWEEN\b/i', substr($clause, $index, 7))) {
                $inBetween = true;
                $current .= substr($clause, $index, 7);
                $index += 7;
                continue;
            }

            // Check for AND keyword
            if (strtoupper(substr($clause, $index, 3)) === 'AND') {
                // Check if this is a word boundary
                $beforeOk = ($index === 0) || !ctype_alnum($clause[$index - 1]);
                $afterOk = ($index + 3 >= $len) || !ctype_alnum($clause[$index + 3]);

                if ($beforeOk && $afterOk) {
                    if ($inBetween) {
                        // This AND is part of BETWEEN...AND, include it
                        $current .= 'AND';
                        $index += 3;
                        $inBetween = false; // BETWEEN...AND complete
                        continue;
                    } else {
                        // This is a logical AND separator
                        if (!empty(trim($current))) {
                            $parts[] = trim($current);
                        }
                        $current = '';
                        $index += 3;
                        // Skip whitespace after AND
                        while ($index < $len && ctype_space($clause[$index])) {
                            $index++;
                        }
                        continue;
                    }
                }
            }

            $current .= $clause[$index];
            $index++;
        }

        if (!empty(trim($current))) {
            $parts[] = trim($current);
        }

        return !empty($parts) ? $parts : [$clause];
    }
}
