<?php

declare(strict_types=1);

namespace GenericDatabase\Generic\FlatFiles;

use GenericDatabase\Helpers\Parsers\Schema;

/**
 * Data processor for flat file operations.
 * Handles filtering, sorting, and data manipulation for all flat file types.
 *
 * @package GenericDatabase\Generic\DataProcessor
 */
class DataProcessor
{
    /**
     * Ascending sort order
     */
    public const ASC = 1;

    /**
     * Descending sort order
     */
    public const DESC = 0;

    /**
     * @var array The data to process.
     */
    private array $data = [];

    /**
     * @var array|null The schema definition.
     */
    private ?array $schema = null;

    /**
     * Constructor.
     *
     * @param array $data The data to process.
     * @param array|null $schema The schema definition.
     */
    public function __construct(array $data = [], ?array $schema = null)
    {
        $this->data = $data;
        $this->schema = $schema;
    }

    /**
     * Set the data to process.
     *
     * @param array $data The data.
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get the current data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set the schema.
     *
     * @param array|null $schema The schema.
     * @return self
     */
    public function setSchema(?array $schema): self
    {
        $this->schema = $schema;
        return $this;
    }

    /**
     * Apply schema types to data.
     *
     * @return self
     */
    public function applySchema(): self
    {
        if ($this->schema !== null) {
            $this->data = Schema::applySchema($this->data, $this->schema);
        }
        return $this;
    }

    /**
     * Select specific columns from data.
     *
     * @param array|string $columns The columns to select ('*' for all).
     * @return self
     */
    public function select(array|string $columns = '*'): self
    {
        if ($columns === '*' || (is_array($columns) && in_array('*', $columns))) {
            return $this;
        }

        if (is_string($columns)) {
            $columns = array_map('trim', explode(',', $columns));
        }

        $this->data = array_map(function ($row) use ($columns) {
            $result = [];
            foreach ($columns as $col) {
                $col = trim($col);

                // Check if column has an alias (e.g., "column AS alias")
                if (preg_match('/^(.+?)\s+AS\s+(.+)$/i', $col, $matches)) {
                    $originalColumn = trim($matches[1]);
                    $alias = trim($matches[2]);

                    // Remove quotes from column names and aliases
                    $originalColumn = trim($originalColumn, '"\'`');
                    $alias = trim($alias, '"\'`');

                    if (isset($row[$originalColumn])) {
                        $result[$alias] = $row[$originalColumn];
                    }
                } else {
                    // No alias, use column name as is
                    $cleanCol = trim($col, '"\'`');
                    if (isset($row[$cleanCol])) {
                        $result[$cleanCol] = $row[$cleanCol];
                    }
                }
            }
            return $result;
        }, $this->data);

        return $this;
    }

    /**
     * Filter data based on WHERE conditions.
     *
     * @param array $conditions The conditions to filter by.
     * @param string $logic The logic operator (AND/OR).
     * @return self
     */
    public function where(array $conditions, string $logic = 'OR'): self
    {
        if (empty($conditions)) {
            return $this;
        }

        $conditionList = $this->normalizeConditions($conditions);

        $this->data = array_filter($this->data, function ($row) use ($conditionList, $logic) {
            return $this->matchesConditions((array) $row, $conditionList, $logic);
        });

        $this->data = array_values($this->data);
        return $this;
    }

    /**
     * Check if a row matches the conditions.
     *
     * @param array $row The row to check.
     * @param array $conditions The conditions.
     * @param string $logic The logic operator.
     * @return bool
     */
    private function matchesConditions(array $row, array $conditions, string $logic): bool
    {
        $results = [];

        foreach ($conditions as $condition) {
            $column = $condition['column'] ?? null;
            if ($column === null) {
                continue;
            }

            $operator = strtoupper((string) ($condition['operator'] ?? '='));
            $value = $condition['value'] ?? null;

            if (!isset($row[$column])) {
                $results[] = false;
                continue;
            }

            $rowValue = $row[$column];

            $result = match ($operator) {
                'IN' => $this->isInList($rowValue, (array) $value),
                'NOT IN' => !$this->isInList($rowValue, (array) $value),
                'LIKE' => $this->matchesLike($rowValue, $value),
                'NOT LIKE' => !$this->matchesLike($rowValue, $value),
                'BETWEEN' => $this->matchesBetween($rowValue, $value),
                'NOT BETWEEN' => !$this->matchesBetween($rowValue, $value),
                '!=', '<>' => !$this->valuesEqual($rowValue, $value),
                '>' => $this->compareValues($rowValue, $value) > 0,
                '>=' => $this->compareValues($rowValue, $value) >= 0,
                '<' => $this->compareValues($rowValue, $value) < 0,
                '<=' => $this->compareValues($rowValue, $value) <= 0,
                'IS NULL' => $rowValue === null,
                'IS NOT NULL' => $rowValue !== null,
                default => $this->valuesEqual($rowValue, $value),
            };

            $results[] = $result;
        }

        if ($logic === 'AND') {
            return !in_array(false, $results, true);
        }

        return in_array(true, $results, true);
    }

    /**
     * Normalize conditions to a list of condition entries.
     *
     * @param array $conditions The conditions to normalize.
     * @return array
     */
    private function normalizeConditions(array $conditions): array
    {
        if (array_is_list($conditions)) {
            return array_values(array_filter($conditions, fn($condition) => is_array($condition)));
        }

        $normalized = [];
        foreach ($conditions as $column => $value) {
            if (is_array($value) && array_key_exists('operator', $value)) {
                $normalized[] = [
                    'column' => $column,
                    'operator' => $value['operator'],
                    'value' => $value['value'] ?? null
                ];
            } else {
                $normalized[] = [
                    'column' => $column,
                    'operator' => '=',
                    'value' => $value
                ];
            }
        }

        return $normalized;
    }

    private function valuesEqual(mixed $left, mixed $right): bool
    {
        if (is_string($left) && is_string($right)) {
            return strcasecmp($left, $right) === 0;
        }

        return $left == $right;
    }

    private function compareValues(mixed $left, mixed $right): int
    {
        if (is_numeric($left) && is_numeric($right)) {
            return $left <=> $right;
        }

        return strcmp((string) $left, (string) $right);
    }

    private function isInList(mixed $value, array $values): bool
    {
        if (is_string($value)) {
            foreach ($values as $candidate) {
                if (is_string($candidate) && strcasecmp($value, $candidate) === 0) {
                    return true;
                }
            }
        }

        return in_array($value, $values);
    }

    private function matchesLike(mixed $value, mixed $pattern): bool
    {
        if (is_object($pattern) && isset($pattern->is_regex) && $pattern->is_regex) {
            return (bool) preg_match($pattern->value, (string) $value, $_, $pattern->options);
        }

        $regex = $this->likeToRegex((string) $pattern);
        return (bool) preg_match($regex, (string) $value);
    }

    private function matchesBetween(mixed $value, mixed $range): bool
    {
        if (!is_array($range)) {
            return false;
        }

        $min = $range['min'] ?? null;
        $max = $range['max'] ?? null;
        if ($min === null || $max === null) {
            return false;
        }

        return $this->compareValues($value, $min) >= 0 && $this->compareValues($value, $max) <= 0;
    }

    private function likeToRegex(string $pattern): string
    {
        // Convert SQL LIKE pattern to regex
        // % = any characters (zero or more), _ = single character
        $regex = '';
        $len = strlen($pattern);
        $i = 0;

        while ($i < $len) {
            $char = $pattern[$i];

            if ($char === '%') {
                $regex .= '.*';
            } elseif ($char === '_') {
                $regex .= '.';
            } else {
                // Escape regex special characters
                $regex .= preg_quote($char, '/');
            }

            $i++;
        }

        return '/^' . $regex . '$/i';
    }

    /**
     * Order data by columns.
     *
     * @param string $column The column to order by.
     * @param int $direction The sort direction (self::ASC or self::DESC).
     * @return self
     */
    public function orderBy(string $column, int $direction = self::ASC): self
    {
        usort($this->data, function ($a, $b) use ($column, $direction) {
            $a = (array) $a;
            $b = (array) $b;

            $aVal = $a[$column] ?? null;
            $bVal = $b[$column] ?? null;

            if ($aVal === $bVal) {
                return 0;
            }

            if ($direction === self::ASC) {
                return $aVal < $bVal ? -1 : 1;
            }

            return $aVal > $bVal ? -1 : 1;
        });

        return $this;
    }

    /**
     * Limit the number of results.
     *
     * @param int $limit The maximum number of results.
     * @param int $offset The starting offset.
     * @return self
     */
    public function limit(int $limit, int $offset = 0): self
    {
        $this->data = array_slice($this->data, $offset, $limit);
        return $this;
    }

    /**
     * Get distinct values.
     *
     * @return self
     */
    public function distinct(): self
    {
        $this->data = array_values(array_unique($this->data, SORT_REGULAR));
        return $this;
    }

    /**
     * Count the number of rows.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Get the first row.
     *
     * @return array|null
     */
    public function first(): ?array
    {
        return $this->data[0] ?? null;
    }

    /**
     * Update rows matching conditions.
     *
     * @param array $data The data to update.
     * @param array $conditions The conditions to match.
     * @param string $logic The logic operator.
     * @return int The number of affected rows.
     */
    public function update(array $data, array $conditions = [], string $logic = 'OR'): int
    {
        $affected = 0;

        foreach ($this->data as $index => &$row) {
            $rowArray = (array) $row;

            if (empty($conditions) || $this->matchesConditions($rowArray, $conditions, $logic)) {
                foreach ($data as $key => $value) {
                    if (is_array($row)) {
                        $this->data[$index][$key] = $value;
                    } else {
                        $this->data[$index]->$key = $value;
                    }
                }
                $affected++;
            }
        }

        return $affected;
    }

    /**
     * Delete rows matching conditions.
     *
     * @param array $conditions The conditions to match.
     * @param string $logic The logic operator.
     * @return int The number of deleted rows.
     */
    public function delete(array $conditions = [], string $logic = 'OR'): int
    {
        $originalCount = count($this->data);

        if (empty($conditions)) {
            $this->data = [];
            return $originalCount;
        }

        $this->data = array_filter($this->data, function ($row) use ($conditions, $logic) {
            return !$this->matchesConditions((array) $row, $conditions, $logic);
        });

        $this->data = array_values($this->data);
        return $originalCount - count($this->data);
    }

    /**
     * Insert a new row.
     *
     * @param array $row The row to insert.
     * @return bool
     */
    public function insert(array $row): bool
    {
        // Validate against first row schema if exists
        if (!empty($this->data)) {
            $firstRow = (array) $this->data[0];
            foreach ($firstRow as $column => $value) {
                if (!isset($row[$column])) {
                    $row[$column] = null;
                }
            }
        }

        $this->data[] = $row;
        return true;
    }

    /**
     * Group data by a column.
     *
     * @param string $column The column to group by.
     * @return array The grouped data.
     */
    public function groupBy(string $column): array
    {
        $grouped = [];

        foreach ($this->data as $row) {
            $row = (array) $row;
            $key = $row[$column] ?? '';
            $grouped[$key][] = $row;
        }

        return $grouped;
    }

    /**
     * Get aggregate value (SUM, AVG, MIN, MAX, COUNT).
     *
     * @param string $function The aggregate function.
     * @param string $column The column to aggregate.
     * @return mixed The aggregate value.
     */
    public function aggregate(string $function, string $column): mixed
    {
        $values = array_column($this->data, $column);
        $values = array_filter($values, fn($v) => $v !== null);

        return match (strtoupper($function)) {
            'SUM' => array_sum($values),
            'AVG' => count($values) > 0 ? array_sum($values) / count($values) : 0,
            'MIN' => count($values) > 0 ? min($values) : null,
            'MAX' => count($values) > 0 ? max($values) : null,
            'COUNT' => count($values),
            default => null
        };
    }
}
