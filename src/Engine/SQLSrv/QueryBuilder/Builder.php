<?php

namespace GenericDatabase\Engine\SQLSrv\QueryBuilder;

use GenericDatabase\Core\Column;
use GenericDatabase\Core\Select;
use GenericDatabase\Core\Join;
use GenericDatabase\Core\Junction;
use GenericDatabase\Core\Sorting;
use GenericDatabase\Core\Grouping;
use GenericDatabase\Core\Where;
use GenericDatabase\Core\Having;
use GenericDatabase\Core\Condition;
use GenericDatabase\Core\Union;
use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Helpers\Parsers\SQL\Parse;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Generic\QueryBuilder\Query;
use GenericDatabase\Interfaces\QueryBuilder\IBuilder;
use GenericDatabase\Interfaces\IQueryBuilder;

class Builder implements IBuilder
{
    use Query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * @throws Exceptions
     */
    private function buildSelect(): string
    {
        if (empty($this->query->select)) {
            throw new Exceptions("No columns specified in SELECT clause.");
        }
        $output = [];
        $distinct = isset($this->query->select['type']) && $this->query->select['type'] === Select::DISTINCT()
            ? 'DISTINCT'
            : '';
        foreach ($this->query->select['columns'] as $data) {
            if (is_array($data)) {
                if ($data['type'] === Column::METADATA()) {
                    $prefix = isset($data['prefix']) ? $data['prefix'] . '.' : ' ';
                    if (isset($data['alias'])) {
                        $output[] = "$prefix{$data['column']} AS {$data['alias']}";
                    } else {
                        $output[] = "$prefix{$data['column']}";
                    }
                } else {
                    $output[] = "{$data['value']}";
                }
            }
        }
        return $this->parse("SELECT $distinct " . implode(', ', $output)) . ' ';
    }

    /**
     * @throws Exceptions
     */
    private function buildFrom(): string
    {
        if (empty($this->query->from)) {
            throw new Exceptions("No tables specified in FROM clause.");
        }
        $output = [];
        foreach ($this->query->from as $data) {
            if (is_array($data)) {
                if ($data['type'] === Column::METADATA()) {
                    if (isset($data['alias'])) {
                        $output[] = "{$data['table']} AS {$data['alias']}";
                    } else {
                        $output[] = "{$data['table']}";
                    }
                } else {
                    $output[] = $data['value'];
                }
            }
        }
        return $this->parse("FROM " . implode(', ', $output)) . ' ';
    }

    /**
     * @throws Exceptions
     */
    private function buildJoin(): string
    {
        if (empty($this->query->join)) {
            throw new Exceptions("No tables specified in JOIN clause.");
        }
        $output = [];
        $type = '';
        foreach ($this->query->join as $data) {
            $type = match ($data['type']) {
                Join::SELF() => 'SELF',
                Join::LEFT() => 'LEFT',
                Join::RIGHT() => 'RIGHT',
                Join::INNER() => 'INNER',
                Join::OUTER() => 'OUTER',
                Join::CROSS() => 'CROSS',
                default => ''
            };
            if ($data['alias']) {
                $output[] = "{$data['table']} AS {$data['alias']}";
            } else {
                $output[] = "{$data['table']}";
            }
        }
        return $this->parse("$type JOIN " . implode(', ', $output)) . ' ';
    }

    /**
     * @throws Exceptions
     */
    private function buildOn(): string
    {
        if (empty($this->query->on)) {
            throw new Exceptions("No tables specified in ON clause.");
        }
        $output = [];
        foreach ($this->query->on as $data) {
            $junctionType = $data['junction'] === Junction::DISJUNCTION() ? 'OR' : 'AND';
            $junction = $data['junction'] === Junction::NONE() ? 'ON ' : $junctionType . ' ';
            $tableHost = ($data['host']['table']) ? $data['host']['table'] . '.' : ' ';
            $host = $tableHost . $data['host']['column'];
            $tableConsumer = ($data['consumer']['table']) ? $data['consumer']['table'] . '.' : ' ';
            $consumer = $tableConsumer . $data['consumer']['column'];
            $output[] = "$junction $host {$data['signal']} $consumer";
        }
        return $this->parse(implode(' ', $output)) . ' ';
    }

    /**
     * @throws Exceptions
     */
    private function buildWhere(): string
    {
        if (empty($this->query->where)) {
            throw new Exceptions("No conditions specified in WHERE clause.");
        }
        $output = [];
        foreach ($this->query->where as $whereIndex => $data) {
            if (empty($data) || !is_array($data)) {
                continue;
            }
            $conditionType = ($data['condition'] ?? Condition::NONE()) === Condition::DISJUNCTION() ? 'OR' : 'AND';
            $condition = ($data['condition'] ?? Condition::NONE()) === Condition::NONE() ? 'WHERE' : $conditionType;
            if (isset($data['type']) && $data['type'] === Where::EXISTS()) {
                $output[] = $this->buildExistsCondition($data, $condition, $whereIndex);
                continue;
            }
            $alias = isset($data['alias']) ? trim($data['alias']) . '.' : '';
            $column = $data['column'] ?? ' ';
            $signal = isset($data['signal']) ? trim($data['signal']) : '';
            $aggregation = $data['aggregation'] ?? [];
            $assert = ($aggregation['assert'] ?? Where::AFFIRMATION()) === Where::NEGATION() ? 'NOT' : ' ';
            $function = ($data['type'] ?? Where::DEFAULT()) === Where::FUNCTION() ? ($data['function'] ?? '') : ' ';
            $type = ($data['type'] ?? Where::DEFAULT()) === Where::DEFAULT() ? "$alias$column" : "$function($alias$column)";
            $placeholders = isset($data['arguments']['unlimited']) ?
                implode(
                    ', ',
                    array_fill(0, count(explode(', ', $data['arguments']['unlimited'])), '?')
                ) : '';
            $output[] = match ($aggregation['type'] ?? Where::NONE()) {
                Where::NONE() => "$condition $type $signal ?",
                Where::BETWEEN() => "$condition $type $assert BETWEEN ? AND ?",
                Where::IN() => "$condition $type $assert IN ($placeholders)",
                Where::LIKE() => "$condition $type $assert LIKE ?",
                Where::EXISTS() => $this->buildExistsCondition($data, $condition, $whereIndex),
                default => "",
            };
        }
        return $this->parse(implode(' ', $output)) . ' ';
    }

    /**
     * Builds EXISTS condition from $query->where data, following Builder/Clause/Criteria pattern.
     * Uses $data['subquery'] from $this->query (IQueryBuilder or raw string).
     *
     * @param array<string, mixed> $data Item from $this->query->where
     * @param string $condition WHERE|AND|OR
     */
    private function buildExistsCondition(array $data, string $condition, int $whereIndex = 0): string
    {
        $negate = $data['negate'] ?? false;
        $subquery = $data['subquery'] ?? '';
        $existsClause = $negate ? 'NOT EXISTS' : 'EXISTS';
        $resolved = $this->resolvedSubqueries['where'][$whereIndex] ?? null;
        if ($resolved !== null) {
            return "$condition $existsClause ($resolved)";
        }
        if ($subquery instanceof IQueryBuilder) {
            return "$condition $existsClause (" . $subquery->build() . ")";
        }
        return "$condition $existsClause ($subquery)";
    }

    /**
     * @throws Exceptions
     */
    private function buildHaving(): string
    {
        if (empty($this->query->having)) {
            throw new Exceptions("No conditions specified in HAVING clause.");
        }
        $output = [];
        foreach ($this->query->having as $data) {
            if (empty($data) || !is_array($data)) {
                continue;
            }
            $conditionType = ($data['condition'] ?? Condition::NONE()) === Condition::DISJUNCTION() ? 'OR' : 'AND';
            $condition = ($data['condition'] ?? Condition::NONE()) === Condition::NONE() ? 'HAVING' : $conditionType;
            if (isset($data['type']) && $data['type'] === Having::EXISTS()) {
                $output[] = $this->buildExistsCondition($data, $condition);
                continue;
            }
            $aggregation = $data['aggregation'] ?? [];
            $alias = isset($data['alias']) ? trim($data['alias']) . '.' : '';
            $column = $data['column'] ?? ' ';
            $signal = isset($data['signal']) ? trim($data['signal']) : '';
            $assert = ($aggregation['assert'] ?? Having::AFFIRMATION()) === Having::NEGATION() ? 'NOT' : ' ';
            $function = ($data['type'] ?? Having::DEFAULT()) === Having::FUNCTION() ? ($data['function'] ?? '') : ' ';
            $type = ($data['type'] ?? Having::DEFAULT()) === Having::DEFAULT() ? "$alias$column" : "$function($alias$column)";
            $placeholders = isset($data['arguments']['unlimited']) ?
                implode(
                    ', ',
                    array_fill(0, count(explode(', ', $data['arguments']['unlimited'])), '?')
                ) : '';
            $output[] = match ($aggregation['type'] ?? Having::NONE()) {
                Having::NONE() => "$condition $type $signal ?",
                Having::BETWEEN() => "$condition $type $assert BETWEEN ? AND ?",
                Having::IN() => "$condition $type $assert IN ($placeholders)",
                Having::LIKE() => "$condition $type $assert LIKE ?",
                Having::EXISTS() => $this->buildExistsCondition($data, $condition),
                default => "",
            };
        }
        return $this->parse(implode(' ', $output)) . ' ';
    }

    /**
     * @throws Exceptions
     */
    private function buildGroup(): string
    {
        if (empty($this->query->group)) {
            throw new Exceptions("No columns specified in GROUP clause.");
        }
        $output = [];
        foreach ($this->query->group as $data) {
            if (is_array($data)) {
                if ($data['type'] === Grouping::METADATA()) {
                    $prefix = ($data['prefix']) ? $data['prefix'] . '.' : ' ';
                    $output[] = "$prefix{$data['column']}";
                } else {
                    $output[] = "{$data['value']}";
                }
            }
        }
        return $this->parse("GROUP BY " . implode(', ', $output)) . ' ';
    }

    /**
     * @throws Exceptions
     */
    private function buildOrder(): string
    {
        if (empty($this->query->order)) {
            throw new Exceptions("No columns specified in ORDER clause.");
        }
        $output = [];
        foreach ($this->query->order as $data) {
            $type = match ($data['sorting']) {
                Sorting::ASCENDING() => 'ASC',
                Sorting::DESCENDING() => 'DESC',
                default => ''
            };
            if (is_array($data)) {
                if ($data['type'] === Column::METADATA()) {
                    $prefix = ($data['prefix']) ? $data['prefix'] . '.' : ' ';
                    $output[] = "$prefix{$data['column']} $type";
                } else {
                    $output[] = "{$data['value']} $type";
                }
            }
        }
        return $this->parse("ORDER BY " . implode(', ', $output)) . ' ';
    }

    /**
     * Builds UNION clause from $this->query->union, following Builder/Clause/Criteria pattern.
     *
     * @throws Exceptions
     */
    private function buildUnion(): string
    {
        if (empty($this->query->union)) {
            return '';
        }
        $output = [];
        foreach ($this->query->union as $union) {
            $queryObj = $union['query'] ?? null;
            $inner = ($union['type'] ?? '') === Union::SUBQUERY() && $queryObj instanceof IQueryBuilder
                ? $queryObj->build()
                : ($queryObj ?? '');
            $output[] = $inner;
        }
        return $this->parse(' UNION ' . implode(' UNION ', $output)) . ' ';
    }

    /**
     * Builds UNION ALL clause from $this->query->unionAll, following Builder/Clause/Criteria pattern.
     *
     * @throws Exceptions
     */
    private function buildUnionAll(): string
    {
        if (empty($this->query->unionAll)) {
            return '';
        }
        $output = [];
        foreach ($this->query->unionAll as $unionAll) {
            $queryObj = $unionAll['query'] ?? null;
            $inner = ($unionAll['type'] ?? '') === Union::SUBQUERY() && $queryObj instanceof IQueryBuilder
                ? $queryObj->build()
                : ($queryObj ?? '');
            $output[] = $inner;
        }
        return $this->parse(' UNION ALL ' . implode(' UNION ALL ', $output)) . ' ';
    }

    /**
     * @throws Exceptions
     */
    private function buildLimit(): string
    {
        if (empty($this->query->limit)) {
            throw new Exceptions("No limits specified in LIMIT clause.");
        }
        $orderBy = empty($this->query->order) ? 'ORDER BY (SELECT NULL) ' : '';
        if (isset($this->query->limit['offset'])) {
            return $orderBy . $this->parse('OFFSET ? ROWS FETCH NEXT ? ROWS ONLY') . ' ';
        }
        return $orderBy . $this->parse('FETCH FIRST ? ROWS ONLY') . ' ';
    }

    /**
     * @throws Exceptions
     */
    private function buildQuery(): string
    {
        $query = "";
        if (!empty($this->query->select)) {
            $query .= $this->buildSelect();
        }
        if (!empty($this->query->from)) {
            $query .= $this->buildFrom();
        }
        if (!empty($this->query->join)) {
            $query .= $this->buildJoin();
        }
        if (!empty($this->query->on)) {
            $query .= $this->buildOn();
        }
        if (!empty($this->query->where)) {
            $query .= $this->buildWhere();
        }
        if (!empty($this->query->group)) {
            $query .= $this->buildGroup();
        }
        if (!empty($this->query->having)) {
            $query .= $this->buildHaving();
        }
        if (!empty($this->query->union)) {
            $query .= $this->buildUnion();
        }
        if (!empty($this->query->unionAll)) {
            $query .= $this->buildUnionAll();
        }
        if (!empty($this->query->order)) {
            $query .= $this->buildOrder();
        }
        if (!empty($this->query->limit)) {
            $query .= $this->buildLimit();
        }
        return trim($query);
    }

    private function setPlaceholders(string $query, array $values): string
    {
        $formatValue = fn($value) => $this->formatValue($value);
        if (Arrays::isMultidimensional($values)) {
            foreach ($values as $val) {
                $query = array_reduce($val, fn($query, $key) =>
                preg_replace('/\?/', $formatValue($key), $query, 1), $query);
            }
        } else {
            $query = array_reduce($values, fn($query, $key) =>
            preg_replace('/\?/', $formatValue($key), $query, 1), $query);
        }
        return $query;
    }

    /**
     * @throws Exceptions
     */
    private function formatValue($value): int|string
    {
        return match (true) {
            is_bool($value) => $value ? '1' : '0',
            is_numeric(trim((string) $value)) => (int) trim((string) $value),
            is_string($value) && preg_match('/^\w+\.\w+$/', trim($value)) => trim($value),
            is_string($value) => "'" . trim($value) . "'",
            is_null($value) => 'NULL',
            default => throw new Exceptions("Unsupported value type: " . gettype($value))
        };
    }

    public function parse(
        string $query,
        int $quoteType = Parse::SQL_DIALECT_DOUBLE_QUOTE,
        ?int $quoteSkip = null
    ): string {
        return Parse::binding(Parse::escape(trim($query), $quoteType, $quoteSkip));
    }

    /**
     * @throws Exceptions
     */
    public function build(): string
    {
        return $this->buildQuery();
    }

    /**
     * @throws Exceptions
     */
    public function buildRaw(): string
    {
        $sql = $this->buildQuery();
        $values = $this->getValues();
        if (!empty($values)) {
            $sql = $this->setPlaceholders($sql, $values);
        }
        return $sql;
    }

    public function getValues(): array
    {
        $values = [];
        if (!empty($this->query->where)) {
            foreach ($this->query->where as $whereIndex => $value) {
                if (isset($value['type']) && $value['type'] === Where::EXISTS()) {
                    $subquery = $value['subquery'] ?? null;
                    if ($subquery instanceof IQueryBuilder) {
                        $values = array_merge($values, $subquery->getValues());
                    }
                } elseif (isset($value['arguments']['default'])) {
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

        if (!empty($this->query->union)) {
            foreach ($this->query->union as $union) {
                if (($union['type'] ?? '') === Union::SUBQUERY() && ($union['query'] ?? null) instanceof IQueryBuilder) {
                    $values = array_merge($values, $union['query']->getValues());
                }
            }
        }
        if (!empty($this->query->unionAll)) {
            foreach ($this->query->unionAll as $unionAll) {
                if (($unionAll['type'] ?? '') === Union::SUBQUERY() && ($unionAll['query'] ?? null) instanceof IQueryBuilder) {
                    $values = array_merge($values, $unionAll['query']->getValues());
                }
            }
        }
        if (!empty($this->query->limit)) {
            $limits = explode(', ', $this->query->limit['value']);
            foreach ($limits as $limit) {
                $values[] = trim($limit);
            }
        }
        return $values;
    }
}
