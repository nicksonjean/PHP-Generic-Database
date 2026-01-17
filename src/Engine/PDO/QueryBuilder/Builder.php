<?php

namespace GenericDatabase\Engine\PDO\QueryBuilder;

use GenericDatabase\Core\Column;
use GenericDatabase\Core\Select;
use GenericDatabase\Core\Join;
use GenericDatabase\Core\Junction;
use GenericDatabase\Core\Sorting;
use GenericDatabase\Core\Grouping;
use GenericDatabase\Core\Where;
use GenericDatabase\Core\Having;
use GenericDatabase\Core\Condition;
use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Helpers\Parsers\SQL;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Connection;
use GenericDatabase\Engine\PDOConnection;
use GenericDatabase\Generic\QueryBuilder\Query;
use GenericDatabase\Interfaces\QueryBuilder\IBuilder;

class Builder implements IBuilder
{
    use Query;

    private static Connection|PDOConnection $context;

    public function __construct($query, $context)
    {
        $this->query = $query;
        self::$context = $context;
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
                $output[] = "{$data['table']} {$data['alias']}";
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
        foreach ($this->query->where as $data) {
            $conditionType = $data['condition'] === Condition::DISJUNCTION() ? 'OR' : 'AND';
            $condition = $data['condition'] === Condition::NONE() ? 'WHERE' : $conditionType;
            $alias = isset($data['alias']) ? trim($data['alias']) . '.' : '';
            $column = $data['column'] ?? ' ';
            $signal = isset($data['signal']) ? trim($data['signal']) : '';
            $assert = $data['aggregation']['assert'] === Where::NEGATION() ? 'NOT' : ' ';
            $function = $data['type'] === Where::FUNCTION() ? $data['function'] : ' ';
            $type = $data['type'] === Where::DEFAULT() ? "$alias$column" : "$function($alias$column)";
            $placeholders = isset($data['arguments']['unlimited']) ?
                implode(
                    ', ',
                    array_fill(0, count(explode(', ', $data['arguments']['unlimited'])), '?')
                ) : '';
            $output[] = match ($data['aggregation']['type']) {
                Where::NONE() => "$condition $type $signal ?",
                Where::BETWEEN() => "$condition $type $assert BETWEEN ? AND ?",
                Where::IN() => "$condition $type $assert IN ($placeholders)",
                Where::LIKE() => "$condition $type $assert LIKE ?",
                default => "",
            };
        }
        return $this->parse(implode(' ', $output)) . ' ';
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
            $conditionType = $data['condition'] === Condition::DISJUNCTION() ? 'OR' : 'AND';
            $condition = $data['condition'] === Condition::NONE() ? 'HAVING' : $conditionType;
            $alias = isset($data['alias']) ? trim($data['alias']) . '.' : '';
            $column = $data['column'] ?? ' ';
            $signal = isset($data['signal']) ? trim($data['signal']) : '';
            $assert = ($data['aggregation']['assert'] === Having::NEGATION()) ? 'NOT' : ' ';
            $function = $data['type'] === Having::FUNCTION() ? $data['function'] : ' ';
            $type = ($data['type'] === Having::DEFAULT()) ? "$alias$column" : "$function($alias$column)";
            $placeholders = isset($data['arguments']['unlimited']) ?
                implode(
                    ', ',
                    array_fill(0, count(explode(', ', $data['arguments']['unlimited'])), '?')
                ) : '';
            $output[] = match ($data['aggregation']['type']) {
                Having::NONE() => "$condition $type $signal ?",
                Having::BETWEEN() => "$condition $type $assert BETWEEN ? AND ?",
                Having::IN() => "$condition $type $assert IN ($placeholders)",
                Having::LIKE() => "$condition $type $assert LIKE ?",
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
     * @throws Exceptions
     */
    private function buildLimit(): string
    {
        if (empty($this->query->limit)) {
            throw new Exceptions("No limits specified in LIMIT clause.");
        }
        $output = [];

        $output[] = match (self::$context->getDriver()) {
            'firebird' => (isset($this->query->limit['offset'])) ? "ROWS ? TO ?" : "ROWS ?",
            'mysql' => (isset($this->query->limit['offset'])) ? "LIMIT ?, ?" : "LIMIT ?",
            'pgsql' => (isset($this->query->limit['offset'])) ? "OFFSET ? LIMIT ?" : "LIMIT ?",
            'sqlite' => (isset($this->query->limit['offset'])) ? "LIMIT ? OFFSET ?" : "LIMIT ?",
            'sqlsrv', 'oci' => (isset($this->query->limit['offset']))
                ? "OFFSET ? ROWS FETCH NEXT ? ROWS ONLY"
                : "FETCH NEXT ? ROWS ONLY",
            default => '',
        };
        return $this->parse(implode(', ', $output)) . ' ';
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
            is_numeric(trim($value)) => (int) trim($value),
            is_string($value) => "'" . trim($value) . "'",
            is_null($value) => 'NULL',
            default => throw new Exceptions("Unsupported value type: " . gettype($value))
        };
    }

    public function parse(
        string $query,
        int $quoteType = SQL::SQL_DIALECT_DOUBLE_QUOTE,
        ?int $quoteSkip = null
    ): string {
        return SQL::binding(SQL::escape(trim($query), $quoteType, $quoteSkip));
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
            foreach ($this->query->where as $value) {
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
            $limits = explode(', ', $this->query->limit['value']);
            if (self::$context->getDriver() === 'sqlite') {
                $values = array_merge(
                    $values,
                    array_map(fn($limit) => $limit, count($limits) === 1
                        ? $limits
                        : array_reverse($limits))
                );
            } else {
                $values = array_merge($values, $limits);
            }
        }
        return $values;
    }
}
