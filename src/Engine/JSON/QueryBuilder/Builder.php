<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\JSON\QueryBuilder;

use GenericDatabase\Core\Column;
use GenericDatabase\Core\Select;
use GenericDatabase\Core\Sorting;
use GenericDatabase\Core\Grouping;
use GenericDatabase\Core\Where;
use GenericDatabase\Core\Having;
use GenericDatabase\Core\Condition;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Helpers\Parsers\SQL;
use GenericDatabase\Generic\QueryBuilder\Query;
use GenericDatabase\Interfaces\QueryBuilder\IBuilder;
use GenericDatabase\Engine\JSON\Connection\JSON;
use GenericDatabase\Generic\FlatFiles\DataProcessor;
use GenericDatabase\Engine\JSON\QueryBuilder\Regex;

/**
 * Builder class for JSON QueryBuilder.
 * Converts query object to executable operations on JSON data.
 *
 * @package GenericDatabase\Engine\JSON\QueryBuilder
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
                    if (!empty($data['alias'])) {
                        $columns[] = $prefix . $data['column'] . ' AS ' . $data['alias'];
                    } else {
                        $columns[] = $prefix . $data['column'];
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

        $from = reset($this->query->from);
        if (!is_array($from)) {
            return $from;
        }

        $table = $from['table'] ?? '';
        $alias = $from['alias'] ?? null;

        return $alias !== null && $alias !== ''
            ? $table . ' AS ' . $alias
            : $table;
    }

    /**
     * Build the where conditions.
     *
     * @return array
     */
    private function buildWhere(): array
    {
        if (empty($this->query->where)) {
            return [];
        }

        $conditions = [];
        foreach ($this->query->where as $data) {
            $column = $data['column'] ?? '';
            if ($column === '') {
                continue;
            }

            $arguments = $data['arguments'] ?? [];
            $default = $arguments['default'] ?? null;
            $extra = $arguments['extra'] ?? null;
            $unlimited = $arguments['unlimited'] ?? null;
            $aggregationType = $data['aggregation']['type'] ?? Where::NONE();
            $aggregationAssert = $data['aggregation']['assert'] ?? Where::AFFIRMATION();
            $signal = strtoupper((string) ($data['signal'] ?? '='));

            $alias = $data['alias'] ?? null;

            if ($aggregationType === Where::IN()) {
                $values = $unlimited ?? $default;
                if (is_string($values)) {
                    $values = array_map('trim', explode(',', $values));
                }
                $conditions[] = [
                    'column' => $column,
                    'alias' => $alias,
                    'operator' => $aggregationAssert === Where::NEGATION() ? 'NOT IN' : 'IN',
                    'value' => is_array($values) ? $values : [$values]
                ];
                continue;
            }

            if ($aggregationType === Where::LIKE()) {
                $pattern = $default ?? '';
                $regex = JSON::regex(Regex::likeToRegex((string) $pattern));
                $conditions[] = [
                    'column' => $column,
                    'alias' => $alias,
                    'operator' => $aggregationAssert === Where::NEGATION() ? 'NOT LIKE' : 'LIKE',
                    'value' => $regex
                ];
                continue;
            }

            if ($aggregationType === Where::BETWEEN()) {
                $conditions[] = [
                    'column' => $column,
                    'alias' => $alias,
                    'operator' => $aggregationAssert === Where::NEGATION() ? 'NOT BETWEEN' : 'BETWEEN',
                    'value' => ['min' => $default, 'max' => $extra]
                ];
                continue;
            }

            if ($default !== null || in_array($signal, ['IS NULL', 'IS NOT NULL'], true)) {
                $conditions[] = [
                    'column' => $column,
                    'alias' => $alias,
                    'operator' => $signal !== '' ? $signal : '=',
                    'value' => $default
                ];
            }
        }

        return $conditions;
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
     * Build the order by clause.
     *
     * @return array|null
     */
    private function buildOrderBy(): ?array
    {
        if (empty($this->query->order)) {
            return null;
        }

        $order = reset($this->query->order);
        $column = $order['column'] ?? '';
        $direction = match ($order['sorting'] ?? null) {
            Sorting::DESCENDING() => JSON::DESC,
            default => JSON::ASC
        };

        return ['column' => $column, 'direction' => $direction];
    }

    /**
     * Build the limit clause.
     *
     * @return array|null
     */
    private function buildLimit(): ?array
    {
        if (empty($this->query->limit)) {
            return null;
        }

        $limitValue = $this->query->limit['value'] ?? '0';
        $parts = explode(',', $limitValue);

        if (count($parts) === 2) {
            return ['offset' => (int) trim($parts[0]), 'limit' => (int) trim($parts[1])];
        }

        return ['offset' => 0, 'limit' => (int) trim($parts[0])];
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
    public function execute(array $data): array
    {
        $processor = new DataProcessor($data);

        // Apply WHERE
        $where = $this->buildWhere();
        if (!empty($where)) {
            $processor->where($where, $this->getWhereLogic());
        }

        // Apply ORDER BY
        $orderBy = $this->buildOrderBy();
        if ($orderBy !== null) {
            $processor->orderBy($orderBy['column'], $orderBy['direction']);
        }

        // Apply LIMIT
        $limit = $this->buildLimit();
        if ($limit !== null) {
            $processor->limit($limit['limit'], $limit['offset']);
        }

        // Apply SELECT - no prefix: DataProcessor looks up $row['id'], not $row['e.id']
        $columns = $this->buildSelect(false);
        if (!in_array('*', $columns)) {
            $processor->select($columns);
        }

        // Apply DISTINCT
        if ($this->isDistinct()) {
            $processor->distinct();
        }

        return $processor->getData();
    }

    /**
     * Parse the query string using SQL::escape for identifier quoting.
     * Matches SQLiteQueryBuilder behavior (SQL::SQL_DIALECT_DOUBLE_QUOTE).
     *
     * @param string $query The query.
     * @param int $quoteType The quote type.
     * @param int|null $quoteSkip The quote skip.
     * @return string
     */
    public function parse(
        string $query,
        int $quoteType = SQL::SQL_DIALECT_DOUBLE_QUOTE,
        ?int $quoteSkip = null
    ): string {
        return SQL::escape(trim($query), $quoteType, $quoteSkip);
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

        // WHERE
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
            $logic = $this->getWhereLogic();
            $parts[] = "WHERE " . implode(" {$logic} ", $whereParts);
        }

        // ORDER BY
        $orderBy = $this->buildOrderBy();
        if ($orderBy !== null) {
            $direction = $orderBy['direction'] === JSON::ASC ? 'ASC' : 'DESC';
            $parts[] = "ORDER BY {$orderBy['column']} {$direction}";
        }

        // LIMIT
        $limit = $this->buildLimit();
        if ($limit !== null) {
            if ($limit['offset'] > 0) {
                $parts[] = "LIMIT {$limit['offset']}, {$limit['limit']}";
            } else {
                $parts[] = "LIMIT {$limit['limit']}";
            }
        }

        return $this->parse(implode(' ', $parts));
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
    public function getValues(): array
    {
        $values = [];

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
                    $values[] = $value->value;
                    continue;
                }

                $values[] = $value;
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

        if (!empty($this->query->limit)) {
            foreach (explode(', ', $this->query->limit['value']) as $limit) {
                $values[] = $limit;
            }
        }

        return $values;
    }
}
