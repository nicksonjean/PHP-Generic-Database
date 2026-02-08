<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\NEON\QueryBuilder;

use GenericDatabase\Core\Column;
use GenericDatabase\Core\Join;
use GenericDatabase\Core\Junction;
use GenericDatabase\Core\Select;
use GenericDatabase\Core\Sorting;
use GenericDatabase\Core\Grouping;
use GenericDatabase\Core\Where;
use GenericDatabase\Core\Having;
use GenericDatabase\Core\Condition;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Helpers\Parsers\SQL\Parse;
use GenericDatabase\Generic\QueryBuilder\Query;
use GenericDatabase\Interfaces\QueryBuilder\IBuilder;
use GenericDatabase\Interfaces\IQueryBuilder;
use GenericDatabase\Engine\NEON\Connection\NEON;
use GenericDatabase\Generic\FlatFiles\DataProcessor;
use GenericDatabase\Engine\NEON\QueryBuilder\Regex;

/**
 * Builder class for NEON QueryBuilder.
 * Converts query object to executable operations on NEON data.
 *
 * @package GenericDatabase\Engine\NEON\QueryBuilder
 */
class Builder implements IBuilder
{
    use Query;

    /**
     * Constructor.
     *
     * @param mixed $query The query object.
     */
    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * Build the select clause.
     * For execute (DataProcessor): use column names without prefix - data keys are "id", "nome", etc.
     * For build (SQL string): use prefix "e." for aliased queries.
     *
     * @param bool $forSql When true, include table prefix for SQL output; when false, omit for data lookup.
     * @return array
     * @throws Exceptions
     */
    private function buildSelect(bool $forSql = false): array
    {
        if (empty($this->query->select)) {
            return ['*'];
        }

        $columns = [];
        foreach ($this->query->select['columns'] as $data) {
            if (is_array($data)) {
                if ($data['type'] === Column::METADATA()) {
                    $prefix = ($forSql && isset($data['prefix']) && $data['prefix'] !== '')
                        ? $data['prefix'] . '.'
                        : '';
                    $col = isset($data['column']) ? trim($data['column'], "'\"") : '';
                    if (!empty($data['alias'])) {
                        $columns[] = $prefix . $col . ' AS ' . $data['alias'];
                    } else {
                        $columns[] = $prefix . $col;
                    }
                } elseif (isset($data['type']) && $data['type'] === 'literal') {
                    $expr = $data['value'] ?? '';
                    if ($expr === '' && !empty($data['alias'])) {
                        $expr = '*';
                    }
                    if ($forSql && $expr !== '*') {
                        $columns[] = '?' . (!empty($data['alias']) ? ' AS ' . $data['alias'] : '');
                    } else {
                        $columns[] = $expr . (!empty($data['alias']) ? ' AS ' . $data['alias'] : '');
                    }
                } else {
                    $columns[] = $data['value'] ?? '*';
                }
            }
        }

        return $columns ?: ['*'];
    }

    /**
     * Build the from clause (table/file name with optional alias).
     *
     * @return string
     * @throws Exceptions
     */
    private function buildFrom(): string
    {
        if (empty($this->query->from)) {
            throw new Exceptions("No file specified in FROM clause.");
        }

        $parts = [];
        foreach ($this->query->from as $from) {
            if (!is_array($from)) {
                $parts[] = $from;
                continue;
            }
            $table = $from['table'] ?? '';
            $alias = $from['alias'] ?? null;
            $parts[] = $alias !== null && $alias !== ''
                ? $table . ' ' . $alias
                : $table;
        }
        return implode(', ', $parts);
    }

    private function buildSelectFromOnly(): string
    {
        $columns = $this->buildSelect(true);
        $distinct = $this->isDistinct() ? 'DISTINCT ' : '';
        $from = $this->buildFrom();
        return "SELECT {$distinct}" . implode(', ', $columns) . " FROM {$from}";
    }

    /**
     * Build the JOIN clause (SQL fragment).
     *
     * @return string
     * @throws Exceptions
     */
    private function buildJoin(): string
    {
        if (empty($this->query->join)) {
            return '';
        }
        $output = [];
        $type = '';
        foreach ($this->query->join as $data) {
            $type = match ($data['type'] ?? null) {
                Join::DEFAULT() => '',
                Join::SELF() => 'SELF',
                Join::LEFT() => 'LEFT',
                Join::RIGHT() => 'RIGHT',
                Join::INNER() => 'INNER',
                Join::OUTER() => 'OUTER',
                Join::CROSS() => 'CROSS',
                default => ''
            };
            $alias = $data['alias'] ?? null;
            $t = $data['table'] ?? '';
            $output[] = ($alias !== null && $alias !== '') ? "{$t} {$alias}" : $t;
        }
        $type = $type !== '' ? $type . ' ' : '';
        return $type . 'JOIN ' . implode(', ', $output);
    }

    /**
     * Build the ON clause (SQL fragment).
     *
     * @return string
     * @throws Exceptions
     */
    private function buildOn(): string
    {
        if (empty($this->query->on)) {
            return '';
        }
        $output = [];
        foreach ($this->query->on as $data) {
            $junction = $data['junction'] ?? Junction::NONE();
            $junctionType = $junction === Junction::DISJUNCTION() ? 'OR' : 'AND';
            $junctionStr = $junction === Junction::NONE() ? 'ON ' : $junctionType . ' ';
            $host = $data['host'] ?? [];
            $consumer = $data['consumer'] ?? [];
            $tableHost = !empty($host['table']) ? $host['table'] . '.' : '';
            $tableConsumer = !empty($consumer['table']) ? $consumer['table'] . '.' : '';
            $h = $tableHost . ($host['column'] ?? '');
            $c = $tableConsumer . ($consumer['column'] ?? '');
            $sig = $data['signal'] ?? '=';
            $output[] = $junctionStr . $h . ' ' . $sig . ' ' . $c;
        }
        return implode(' ', $output);
    }

    /**
     * Build the GROUP BY clause (SQL fragment).
     *
     * @return string
     * @throws Exceptions
     */
    private function buildGroup(): string
    {
        if (empty($this->query->group)) {
            return '';
        }
        $output = [];
        foreach ($this->query->group as $data) {
            if (!is_array($data)) {
                continue;
            }
            $gt = $data['type'] ?? null;
            if ($gt === Grouping::METADATA()) {
                $prefix = !empty($data['prefix']) ? $data['prefix'] . '.' : '';
                $output[] = $prefix . ($data['column'] ?? '');
            } else {
                $output[] = $data['value'] ?? '';
            }
        }
        return $output === [] ? '' : 'GROUP BY ' . implode(', ', $output);
    }

    /**
     * Build the HAVING clause (SQL fragment with ? placeholders).
     *
     * @return string
     * @throws Exceptions
     */
    private function buildHaving(): string
    {
        if (empty($this->query->having)) {
            return '';
        }
        $output = [];
        foreach ($this->query->having as $data) {
            $conditionType = ($data['condition'] ?? null) === Condition::DISJUNCTION() ? 'OR' : 'AND';
            $condition = ($data['condition'] ?? null) === Condition::NONE() ? 'HAVING' : $conditionType;
            $alias = isset($data['alias']) && $data['alias'] !== '' ? trim($data['alias']) . '.' : '';
            $column = $data['column'] ?? ' ';
            $signal = isset($data['signal']) ? trim($data['signal']) : '';
            $assert = ($data['aggregation']['assert'] ?? null) === Having::NEGATION() ? 'NOT' : ' ';
            $fn = ($data['type'] ?? null) === Having::FUNCTION() ? ($data['function'] ?? '') : ' ';
            $type = ($data['type'] ?? null) === Having::DEFAULT()
                ? $alias . $column
                : $fn . '(' . $alias . $column . ')';
            $unl = $data['arguments']['unlimited'] ?? null;
            $placeholders = $unl !== null
                ? implode(', ', array_fill(0, count(explode(', ', (string) $unl)), '?'))
                : '';
            $output[] = match ($data['aggregation']['type'] ?? null) {
                Having::NONE() => $condition . ' ' . $type . ' ' . $signal . ' ?',
                Having::BETWEEN() => $condition . ' ' . $type . ' ' . $assert . ' BETWEEN ? AND ?',
                Having::IN() => $condition . ' ' . $type . ' ' . $assert . ' IN (' . $placeholders . ')',
                Having::LIKE() => $condition . ' ' . $type . ' ' . $assert . ' LIKE ?',
                default => '',
            };
        }
        return $output === [] ? '' : implode(' ', $output);
    }

    /**
     * Build the where conditions (optionally resolve outer refs from contextRow for EXISTS subqueries).
     *
     * @param array $contextRow Current outer row for correlated subqueries.
     * @return array
     */
    private function buildWhere(array $contextRow = []): array
    {
        if (empty($this->query->where)) {
            return [];
        }

        $conditions = [];
        foreach ($this->query->where as $data) {
            if (isset($data['type']) && $data['type'] === Where::EXISTS()) {
                continue;
            }
            $column = $data['column'] ?? '';
            if ($column === '') {
                continue;
            }

            $arguments = $data['arguments'] ?? [];
            $default = $this->resolveContextValue($arguments['default'] ?? null, $contextRow);
            $extra = $this->resolveContextValue($arguments['extra'] ?? null, $contextRow);
            $unlimited = $arguments['unlimited'] ?? null;
            if (is_string($unlimited)) {
                $unlimited = $this->resolveContextValue($unlimited, $contextRow);
            } elseif (is_array($unlimited)) {
                $unlimited = array_map(fn($v) => $this->resolveContextValue($v, $contextRow), $unlimited);
            }
            $aggregationType = $data['aggregation']['type'] ?? Where::NONE();
            $aggregationAssert = $data['aggregation']['assert'] ?? Where::AFFIRMATION();
            $signal = strtoupper((string) ($data['signal'] ?? '='));

            $alias = $data['alias'] ?? null;
            $condition = $data['condition'] ?? null;

            if ($aggregationType === Where::IN()) {
                $values = $unlimited ?? $default;
                if (is_string($values)) {
                    $values = array_map('trim', explode(',', $values));
                }
                $conditions[] = [
                    'column' => $column,
                    'alias' => $alias,
                    'operator' => $aggregationAssert === Where::NEGATION() ? 'NOT IN' : 'IN',
                    'value' => is_array($values) ? $values : [$values],
                    'condition' => $condition
                ];
                continue;
            }

            if ($aggregationType === Where::LIKE()) {
                $pattern = $default ?? '';
                $regex = NEON::regex(Regex::likeToRegex((string) $pattern));
                $conditions[] = [
                    'column' => $column,
                    'alias' => $alias,
                    'operator' => $aggregationAssert === Where::NEGATION() ? 'NOT LIKE' : 'LIKE',
                    'value' => $regex,
                    'valueForRaw' => $pattern,
                    'condition' => $condition
                ];
                continue;
            }

            if ($aggregationType === Where::BETWEEN()) {
                $conditions[] = [
                    'column' => $column,
                    'alias' => $alias,
                    'operator' => $aggregationAssert === Where::NEGATION() ? 'NOT BETWEEN' : 'BETWEEN',
                    'value' => ['min' => $default, 'max' => $extra],
                    'condition' => $condition
                ];
                continue;
            }

            if ($default !== null || in_array($signal, ['IS NULL', 'IS NOT NULL'], true)) {
                $conditions[] = [
                    'column' => $column,
                    'alias' => $alias,
                    'operator' => $signal !== '' ? $signal : '=',
                    'value' => $default,
                    'condition' => $condition
                ];
            }
        }

        return $conditions;
    }

    /**
     * Build WHERE clause fragment (content after "WHERE ") with quoted identifiers.
     * Used to append WHERE to EXISTS subquery when build() omits it.
     *
     * @param array $contextRow Current outer row for correlated subqueries.
     * @return string
     */
    private function buildWhereClauseFragment(array $contextRow = []): string
    {
        $where = $this->buildWhere($contextRow);
        if (empty($where)) {
            return '';
        }
        $whereParts = [];
        foreach ($where as $condition) {
            $alias = $condition['alias'] ?? null;
            $colBase = $condition['column'] ?? '';
            $column = ($alias !== null && $alias !== '' ? '"' . $alias . '"."' . $colBase . '"' : '"' . $colBase . '"');
            $operator = strtoupper((string) ($condition['operator'] ?? '='));
            $value = $condition['value'] ?? null;
            if ($operator === 'IN' || $operator === 'NOT IN') {
                $values = is_array($value) ? $value : [$value];
                $placeholders = implode(', ', array_fill(0, count($values), '?'));
                $whereParts[] = "{$column} {$operator} ({$placeholders})";
            } elseif ($operator === 'BETWEEN' || $operator === 'NOT BETWEEN') {
                $whereParts[] = "{$column} {$operator} ? AND ?";
            } elseif ($operator === 'IS NULL' || $operator === 'IS NOT NULL') {
                $whereParts[] = "{$column} {$operator}";
            } elseif (is_string($value) && preg_match('/^\w+\.\w+$/', $value)) {
                $rightCol = '"' . str_replace('.', '"."', $value) . '"';
                $whereParts[] = "{$column} {$operator} {$rightCol}";
            } else {
                $whereParts[] = "{$column} {$operator} ?";
            }
        }
        $whereStr = $whereParts[0];
        for ($i = 1; $i < count($whereParts); $i++) {
            $connector = ($where[$i]['condition'] ?? null) === Condition::DISJUNCTION() ? 'OR' : 'AND';
            $whereStr .= ' ' . $connector . ' ' . $whereParts[$i];
        }
        return $whereStr;
    }

    /**
     * Get the where logic (AND/OR).
     *
     * @return string
     */
    private function getWhereLogic(): string
    {
        if (empty($this->query->where)) {
            return 'AND';
        }

        foreach ($this->query->where as $data) {
            if (isset($data['condition'])) {
                if ($data['condition'] === Condition::DISJUNCTION()) {
                    return 'OR';
                }
            }
        }

        return 'AND';
    }

    /**
     * Build the order by clause (for execute / DataProcessor).
     *
     * @return array{column: string, direction: int}|null
     */
    private function buildOrderBy(): ?array
    {
        if (empty($this->query->order)) {
            return null;
        }

        $order = reset($this->query->order);
        $expr = $this->orderExpr($order);
        $direction = match ($order['sorting'] ?? null) {
            Sorting::DESCENDING() => NEON::DESC,
            default => NEON::ASC
        };
        if ($expr !== '' && preg_match('/^(.+?)\s+(ASC|DESC)\s*$/i', $expr, $m)) {
            $expr = trim($m[1]);
            $direction = strtoupper($m[2]) === 'DESC' ? NEON::DESC : NEON::ASC;
        }

        return ['column' => $expr, 'direction' => (int) $direction];
    }

    /**
     * Build ORDER BY SQL fragment for build().
     *
     * @return string
     */
    private function buildOrderByString(): string
    {
        if (empty($this->query->order)) {
            return '';
        }
        $out = [];
        foreach ($this->query->order as $order) {
            $expr = $this->orderExpr($order);
            if ($expr === '') {
                continue;
            }
            $dir = 'ASC';
            if (preg_match('/^(.+?)\s+(ASC|DESC)\s*$/i', $expr, $m)) {
                $expr = trim($m[1]);
                $dir = strtoupper($m[2]);
            } elseif (($order['sorting'] ?? null) === Sorting::DESCENDING()) {
                $dir = 'DESC';
            }
            $out[] = $expr . ' ' . $dir;
        }
        return $out === [] ? '' : 'ORDER BY ' . implode(', ', $out);
    }

    /**
     * @param array<string, mixed> $order
     */
    private function orderExpr(array $order): string
    {
        $type = $order['type'] ?? null;
        if ($type === Sorting::METADATA() || (isset($order['column']) && !isset($order['function']))) {
            $prefix = !empty($order['prefix']) ? $order['prefix'] . '.' : '';
            $col = $order['column'] ?? '';
            return $prefix . $col;
        }
        if ($type === Sorting::FUNCTION() || isset($order['function'])) {
            $fn = $order['function'] ?? '';
            $args = $order['arguments'] ?? '';
            return $fn . '(' . $args . ')';
        }
        return (string) ($order['value'] ?? '');
    }

    /**
     * Build the limit clause (for execute / getValues).
     *
     * @return array{offset: int, limit: int}|null
     */
    private function buildLimit(): ?array
    {
        if (empty($this->query->limit)) {
            return null;
        }

        $limitValue = $this->query->limit['value'] ?? '0';
        $parts = array_map('trim', explode(',', $limitValue));

        if (count($parts) === 2) {
            return ['offset' => (int) $parts[0], 'limit' => (int) $parts[1]];
        }

        return ['offset' => 0, 'limit' => (int) $parts[0]];
    }

    /**
     * Build LIMIT SQL fragment for build(): "LIMIT x OFFSET y".
     *
     * @return string
     */
    private function buildLimitString(): string
    {
        $lim = $this->buildLimit();
        if ($lim === null) {
            return '';
        }
        return 'LIMIT ' . $lim['limit'] . ' OFFSET ' . $lim['offset'];
    }

    /**
     * Check if query is for distinct results.
     *
     * @return bool
     */
    private function isDistinct(): bool
    {
        return isset($this->query->select['type']) && $this->query->select['type'] === Select::DISTINCT();
    }

    /**
     * Execute the query on data and return results.
     *
     * @param array $data The data to query.
     * @return array The query results.
     * @throws Exceptions
     */
    public function execute(array $data, array $contextRow = []): array
    {
        $mainResults = $this->executeSingle($data, $contextRow);

        if (!empty($this->query->union) || !empty($this->query->unionAll)) {
            $allResults = [$mainResults];
            if (!empty($this->query->union)) {
                foreach ($this->query->union as $union) {
                    $allResults[] = $this->executeUnionQuery($union, $data);
                }
            }
            if (!empty($this->query->unionAll)) {
                foreach ($this->query->unionAll as $unionAll) {
                    $allResults[] = $this->executeUnionQuery($unionAll, $data);
                }
            }
            return $this->mergeResults($allResults, !empty($this->query->union));
        }

        return $mainResults;
    }

    private function executeSingle(array $data, array $contextRow = []): array
    {
        $processor = new DataProcessor($data);

        $where = $this->buildWhere($contextRow);
        if (!empty($where)) {
            $processor->where($where, $this->getWhereLogic());
        }

        $data = $processor->getData();
        $data = $this->applyExistsFilter($data);
        $processor->setData($data);

        $orderBy = $this->buildOrderBy();
        if ($orderBy !== null) {
            $processor->orderBy($orderBy['column'], $orderBy['direction']);
        }

        $limit = $this->buildLimit();
        if ($limit !== null) {
            $processor->limit($limit['limit'], $limit['offset']);
        }

        $columns = $this->buildSelect(false);
        if (!in_array('*', $columns)) {
            $processor->select($columns);
        }

        if ($this->isDistinct()) {
            $processor->distinct();
        }

        return $processor->getData();
    }

    private function resolveContextValue(mixed $value, array $contextRow): mixed
    {
        if (!is_string($value) || $value === '' || empty($contextRow)) {
            return $value;
        }
        if (preg_match('/^\w+\.(\w+)$/', $value, $m) && array_key_exists($m[1], $contextRow)) {
            return $contextRow[$m[1]];
        }
        return $value;
    }

    private function applyExistsFilter(array $data): array
    {
        if (empty($this->query->where)) {
            return $data;
        }
        $existsItems = [];
        foreach ($this->query->where as $item) {
            if (!isset($item['type']) || $item['type'] !== Where::EXISTS()) {
                continue;
            }
            $subquery = $item['subquery'] ?? null;
            if (!$subquery instanceof IQueryBuilder) {
                continue;
            }
            $existsItems[] = [
                'subquery' => $subquery,
                'negate' => (bool) ($item['negate'] ?? false),
            ];
        }
        if (empty($existsItems)) {
            return $data;
        }
        $filtered = [];
        foreach ($data as $row) {
            $rowArr = (array) $row;
            $pass = true;
            foreach ($existsItems as $item) {
                $subResult = $item['subquery']->execute(null, $rowArr);
                $hasRows = !empty($subResult);
                $pass = $item['negate'] ? !$hasRows : $hasRows;
                if (!$pass) {
                    break;
                }
            }
            if ($pass) {
                $filtered[] = $row;
            }
        }
        return $filtered;
    }

    private function executeUnionQuery(array $union, array $data): array
    {
        if ($union['type'] === 'subquery' && $union['query'] instanceof IQueryBuilder) {
            return $union['query']->execute();
        }
        throw new Exceptions("Raw SQL UNION queries not yet supported for flat files");
    }

    private function mergeResults(array $resultSets, bool $removeDuplicates): array
    {
        $merged = [];
        $seen = [];
        foreach ($resultSets as $results) {
            foreach ($results as $row) {
                $key = serialize($row);
                if ($removeDuplicates) {
                    if (!isset($seen[$key])) {
                        $merged[] = $row;
                        $seen[$key] = true;
                    }
                } else {
                    $merged[] = $row;
                }
            }
        }
        return $merged;
    }

    /**
     * Parse the query string using Parse::escape for identifier quoting.
     * Matches SQLiteQueryBuilder behavior (Parse::SQL_DIALECT_DOUBLE_QUOTE).
     *
     * @param string $query The query.
     * @param int $quoteType The quote type.
     * @param int|null $quoteSkip The quote skip.
     * @return string
     */
    public function parse(
        string $query,
        int $quoteType = Parse::SQL_DIALECT_DOUBLE_QUOTE,
        ?int $quoteSkip = null
    ): string {
        return Parse::escape(trim($query), $quoteType, $quoteSkip);
    }

    /**
     * Build the query as a descriptive string.
     *
     * @return string
     * @throws Exceptions
     */
    public function build(): string
    {
        $parts = [];

        // SELECT - with prefix for SQL string (e.id AS Codigo, etc.)
        $columns = $this->buildSelect(true);
        $distinct = $this->isDistinct() ? 'DISTINCT ' : '';
        $parts[] = "SELECT {$distinct}" . implode(', ', $columns);

        // FROM
        $from = $this->buildFrom();
        $parts[] = "FROM {$from}";

        // JOIN
        $join = $this->buildJoin();
        if ($join !== '') {
            $parts[] = $join;
        }

        // ON
        $on = $this->buildOn();
        if ($on !== '') {
            $parts[] = $on;
        }

        // WHERE – use per-condition logic (AND/OR) from each item
        $where = $this->buildWhere();
        if (!empty($where)) {
            $whereParts = [];
            foreach ($where as $condition) {
                $alias = $condition['alias'] ?? null;
                $colBase = $condition['column'] ?? '';
                $column = ($alias !== null && $alias !== '' ? $alias . '.' : '') . $colBase;
                $operator = strtoupper((string) ($condition['operator'] ?? '='));
                $value = $condition['value'] ?? null;

                if ($operator === 'IN' || $operator === 'NOT IN') {
                    $values = is_array($value) ? $value : [$value];
                    $placeholders = implode(', ', array_fill(0, count($values), '?'));
                    $whereParts[] = "{$column} {$operator} ({$placeholders})";
                    continue;
                }

                if ($operator === 'BETWEEN' || $operator === 'NOT BETWEEN') {
                    $whereParts[] = "{$column} {$operator} ? AND ?";
                    continue;
                }

                if ($operator === 'IS NULL' || $operator === 'IS NOT NULL') {
                    $whereParts[] = "{$column} {$operator}";
                    continue;
                }

                if (is_object($value) && isset($value->is_regex)) {
                    $whereParts[] = "{$column} {$operator} ?";
                    continue;
                }

                $whereParts[] = "{$column} {$operator} ?";
            }
            $whereStr = $whereParts[0];
            for ($i = 1; $i < count($whereParts); $i++) {
                $connector = ($where[$i]['condition'] ?? null) === Condition::DISJUNCTION() ? 'OR' : 'AND';
                $whereStr .= ' ' . $connector . ' ' . $whereParts[$i];
            }
            $parts[] = "WHERE " . $whereStr;
        }
        $existsStr = $this->buildExistsWhereString();
        if ($existsStr !== '') {
            if (!empty($where)) {
                $parts[count($parts) - 1] .= ' AND ' . $existsStr;
            } else {
                $parts[] = "WHERE " . $existsStr;
            }
        }

        // GROUP BY
        $group = $this->buildGroup();
        if ($group !== '') {
            $parts[] = $group;
        }

        // HAVING
        $having = $this->buildHaving();
        if ($having !== '') {
            $parts[] = $having;
        }

        $hasUnion = !empty($this->query->union) || !empty($this->query->unionAll);
        if (!$hasUnion) {
            $orderByStr = $this->buildOrderByString();
            if ($orderByStr !== '') {
                $parts[] = $orderByStr;
            }
            $limitStr = $this->buildLimitString();
            if ($limitStr !== '') {
                $parts[] = $limitStr;
            }
        }

        $this->appendUnionToParts($parts);

        if ($hasUnion) {
            $orderByStr = $this->buildOrderByString();
            if ($orderByStr !== '') {
                $parts[] = $orderByStr;
            }
            $limitStr = $this->buildLimitString();
            if ($limitStr !== '') {
                $parts[] = $limitStr;
            }
        }

        return Parse::escape(trim(implode(' ', $parts)), Parse::SQL_DIALECT_DOUBLE_QUOTE);
    }

    /**
     * Build WHERE fragment from a query's raw where array (for EXISTS subquery).
     *
     * @param object $query Query object with where array.
     * @return string
     */
    private function buildExistsSubqueryWhereFragment(object $query): string
    {
        if (empty($query->where)) {
            return '';
        }
        $parts = [];
        foreach ($query->where as $data) {
            if (isset($data['type']) && $data['type'] === Where::EXISTS()) {
                continue;
            }
            $col = trim((string) ($data['column'] ?? ''), "'\"");
            if ($col === '') {
                continue;
            }
            $alias = isset($data['alias']) ? trim((string) $data['alias'], "'\"") : null;
            $left = ($alias !== null && $alias !== '') ? $alias . '.' . $col : $col;
            $signal = strtoupper((string) ($data['signal'] ?? '='));
            $right = '?';
            $rawCondition = trim((string) ($data['value'] ?? ''));
            if ($rawCondition !== '' && preg_match('/=\s*(\w+\.\w+)\s*$/s', $rawCondition, $m)) {
                $right = trim($m[1]);
            } else {
                $args = $data['arguments'] ?? [];
                $value = $args['default'] ?? null;
                if (is_string($value) && preg_match('/^\w+\.\w+$/D', trim($value))) {
                    $right = trim($value);
                }
            }
            $parts[] = $left . ' ' . $signal . ' ' . $right;
        }
        if ($parts === []) {
            return '';
        }
        $fragment = $parts[0];
        for ($i = 1; $i < count($parts); $i++) {
            $connector = (isset($query->where[$i]['condition']) && $query->where[$i]['condition'] === Condition::DISJUNCTION()) ? 'OR' : 'AND';
            $fragment .= ' ' . $connector . ' ' . $parts[$i];
        }
        return $fragment;
    }

    private function buildExistsWhereString(): string
    {
        if (empty($this->query->where)) {
            return '';
        }
        $existsClauses = [];
        foreach ($this->query->where as $data) {
            if (!isset($data['type']) || $data['type'] !== Where::EXISTS()) {
                continue;
            }
            $negate = $data['negate'] ?? false;
            $subquery = $data['subquery'] ?? null;
            if ($subquery === null) {
                continue;
            }
            $existsClause = $negate ? 'NOT EXISTS' : 'EXISTS';
            if ($subquery instanceof IQueryBuilder) {
                if (property_exists($subquery, 'query')) {
                    $subBuilder = new Builder($subquery->query);
                    $sql = $subBuilder->buildSelectFromOnly();
                    $sql = preg_replace('/^SELECT\s+1\s+FROM/i', 'SELECT ? FROM', $sql, 1);
                    $whereFragment = $this->buildExistsSubqueryWhereFragment($subquery->query);
                    if ($whereFragment !== '') {
                        $sql .= ' WHERE ' . $whereFragment;
                    }
                } else {
                    $sql = $subquery->build();
                }
                $existsClauses[] = $existsClause . ' (' . $sql . ')';
            } else {
                $existsClauses[] = $existsClause . ' (' . (string) $subquery . ')';
            }
        }
        return $existsClauses === [] ? '' : implode(' AND ', $existsClauses);
    }

    private function appendUnionToParts(array &$parts): void
    {
        if (!empty($this->query->union)) {
            foreach ($this->query->union as $union) {
                if ($union['type'] === 'subquery' && $union['query'] instanceof IQueryBuilder) {
                    $parts[] = 'UNION ' . $union['query']->build();
                } else {
                    $parts[] = 'UNION ' . (string) $union['query'];
                }
            }
        }
        if (!empty($this->query->unionAll)) {
            foreach ($this->query->unionAll as $unionAll) {
                if ($unionAll['type'] === 'subquery' && $unionAll['query'] instanceof IQueryBuilder) {
                    $parts[] = 'UNION ALL ' . $unionAll['query']->build();
                } else {
                    $parts[] = 'UNION ALL ' . (string) $unionAll['query'];
                }
            }
        }
    }

    /**
     * Build the query without identifier quoting (for internal execution).
     * Used by the NEON engine so parseAndExecuteQuery receives unquoted identifiers.
     *
     * @return string
     * @throws Exceptions
     */
    public function buildForExecution(): string
    {
        $parts = [];

        $columns = $this->buildSelect(true);
        $distinct = $this->isDistinct() ? 'DISTINCT ' : '';
        $parts[] = "SELECT {$distinct}" . implode(', ', $columns);

        $from = $this->buildFrom();
        $parts[] = "FROM {$from}";

        $join = $this->buildJoin();
        if ($join !== '') {
            $parts[] = $join;
        }

        $on = $this->buildOn();
        if ($on !== '') {
            $parts[] = $on;
        }

        $where = $this->buildWhere();
        if (!empty($where)) {
            $whereParts = [];
            foreach ($where as $condition) {
                $alias = $condition['alias'] ?? null;
                $colBase = $condition['column'] ?? '';
                $column = ($alias !== null && $alias !== '' ? $alias . '.' : '') . $colBase;
                $operator = strtoupper((string) ($condition['operator'] ?? '='));
                $value = $condition['value'] ?? null;

                if ($operator === 'IN' || $operator === 'NOT IN') {
                    $values = is_array($value) ? $value : [$value];
                    $placeholders = implode(', ', array_fill(0, count($values), '?'));
                    $whereParts[] = "{$column} {$operator} ({$placeholders})";
                    continue;
                }

                if ($operator === 'BETWEEN' || $operator === 'NOT BETWEEN') {
                    $whereParts[] = "{$column} {$operator} ? AND ?";
                    continue;
                }

                if ($operator === 'IS NULL' || $operator === 'IS NOT NULL') {
                    $whereParts[] = "{$column} {$operator}";
                    continue;
                }

                if (is_object($value) && isset($value->is_regex)) {
                    $whereParts[] = "{$column} {$operator} ?";
                    continue;
                }

                $whereParts[] = "{$column} {$operator} ?";
            }
            $whereStr = $whereParts[0];
            for ($i = 1; $i < count($whereParts); $i++) {
                $connector = ($where[$i]['condition'] ?? null) === Condition::DISJUNCTION() ? 'OR' : 'AND';
                $whereStr .= ' ' . $connector . ' ' . $whereParts[$i];
            }
            $parts[] = "WHERE " . $whereStr;
        }
        $existsStr = $this->buildExistsWhereString();
        if ($existsStr !== '') {
            if (!empty($where)) {
                $parts[count($parts) - 1] .= ' AND ' . $existsStr;
            } else {
                $parts[] = "WHERE " . $existsStr;
            }
        }

        $group = $this->buildGroup();
        if ($group !== '') {
            $parts[] = $group;
        }

        $having = $this->buildHaving();
        if ($having !== '') {
            $parts[] = $having;
        }

        $hasUnion = !empty($this->query->union) || !empty($this->query->unionAll);
        if (!$hasUnion) {
            $orderByStr = $this->buildOrderByString();
            if ($orderByStr !== '') {
                $parts[] = $orderByStr;
            }
            $limitStr = $this->buildLimitString();
            if ($limitStr !== '') {
                $parts[] = $limitStr;
            }
        }

        $this->appendUnionToParts($parts);

        if ($hasUnion) {
            $orderByStr = $this->buildOrderByString();
            if ($orderByStr !== '') {
                $parts[] = $orderByStr;
            }
            $limitStr = $this->buildLimitString();
            if ($limitStr !== '') {
                $parts[] = $limitStr;
            }
        }

        return trim(implode(' ', $parts));
    }

    /**
     * Build the raw query with values substituted, without identifier quoting (for execution).
     *
     * @return string
     * @throws Exceptions
     */
    public function buildRawForExecution(): string
    {
        $query = $this->buildForExecution();
        $values = $this->getValues();

        foreach ($values as $value) {
            $formatted = match (true) {
                is_null($value) => 'NULL',
                is_bool($value) => $value ? '1' : '0',
                is_numeric($value) => (string) $value,
                is_string($value) => "'" . addslashes($value) . "'",
                default => "'" . addslashes((string) $value) . "'",
            };

            $query = preg_replace('/\?/', $formatted, $query, 1);
        }

        return $query;
    }

    /**
     * Build the raw query with values.
     *
     * @return string
     * @throws Exceptions
     */
    public function buildRaw(): string
    {
        $query = $this->build();
        $values = $this->getValues();

        foreach ($values as $value) {
            $formatted = match (true) {
                is_null($value) => 'NULL',
                is_bool($value) => $value ? '1' : '0',
                is_numeric($value) => (string) $value,
                is_string($value) => "'" . addslashes($value) . "'",
                default => "'" . addslashes((string) $value) . "'",
            };

            $query = preg_replace('/\?/', $formatted, $query, 1);
        }

        return $query;
    }

    /**
     * Get the values for placeholders.
     *
     * @return array
     */
    private function getSelectLiteralValues(object $query): array
    {
        $literals = [];
        if (empty($query->select['columns'])) {
            return $literals;
        }
        foreach ($query->select['columns'] as $data) {
            if (is_array($data) && isset($data['type']) && $data['type'] === 'literal') {
                $v = $data['value'] ?? null;
                if ($v !== null && $v !== '') {
                    $literals[] = trim((string) $v, "'\"");
                }
            }
        }
        return $literals;
    }

    public function getValues(): array
    {
        $values = [];

        foreach ($this->getSelectLiteralValues($this->query) as $literal) {
            $values[] = $literal;
        }

        if (!empty($this->query->where)) {
            foreach ($this->query->where as $whereItem) {
                if (isset($whereItem['type']) && $whereItem['type'] === Where::EXISTS()) {
                    $values[] = 1;
                }
            }
        }

        $where = $this->buildWhere();
        if (!empty($where)) {
            foreach ($where as $condition) {
                $operator = strtoupper((string) ($condition['operator'] ?? '='));
                $value = $condition['value'] ?? null;

                if ($operator === 'IN' || $operator === 'NOT IN') {
                    $values = array_merge($values, is_array($value) ? $value : [$value]);
                    continue;
                }

                if ($operator === 'BETWEEN' || $operator === 'NOT BETWEEN') {
                    $values[] = $value['min'] ?? null;
                    $values[] = $value['max'] ?? null;
                    continue;
                }

                if ($operator === 'IS NULL' || $operator === 'IS NOT NULL') {
                    continue;
                }

                if (is_object($value) && isset($value->is_regex)) {
                    $values[] = $condition['valueForRaw'] ?? ((array) $value)['value'] ?? $value;
                    continue;
                }

                if (is_string($value) && preg_match('/^\w+\.\w+$/', $value)) {
                    continue;
                }

                $values[] = $value;
            }
        }

        if (!empty($this->query->union)) {
            foreach ($this->query->union as $union) {
                if (isset($union['type']) && $union['type'] === 'subquery' && $union['query'] instanceof IQueryBuilder) {
                    $values = array_merge($values, $union['query']->getValues());
                }
            }
        }

        if (!empty($this->query->unionAll)) {
            foreach ($this->query->unionAll as $unionAll) {
                if (isset($unionAll['type']) && $unionAll['type'] === 'subquery' && $unionAll['query'] instanceof IQueryBuilder) {
                    $values = array_merge($values, $unionAll['query']->getValues());
                }
            }
        }

        if (!empty($this->query->having)) {
            foreach ($this->query->having as $value) {
                if (isset($value['arguments']['default'])) {
                    $values[] = trim($value['arguments']['default']);
                }
                if (isset($value['arguments']['extra'])) {
                    $values[] = trim($value['arguments']['extra']);
                }
                if (isset($value['arguments']['unlimited'])) {
                    foreach (explode(',', $value['arguments']['unlimited']) as $val) {
                        $values[] = trim($val);
                    }
                }
            }
        }

        $lim = $this->buildLimit();
        if ($lim !== null) {
            $values[] = $lim['limit'];
            $values[] = $lim['offset'];
        }

        return $this->normalizeNumericArguments($values);
    }

    private function normalizeNumericArguments(array $values): array
    {
        foreach ($values as $i => $v) {
            if (is_string($v) && is_numeric($v)) {
                $f = (float) $v;
                $values[$i] = $f === (float) (int) $f ? (int) $f : $f;
            }
        }
        return $values;
    }
}
