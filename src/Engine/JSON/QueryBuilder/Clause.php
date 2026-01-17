<?php

namespace GenericDatabase\Engine\JSON\QueryBuilder;

use GenericDatabase\Core\Join;
use GenericDatabase\Core\Table;
use GenericDatabase\Core\Where;
use GenericDatabase\Core\Having;
use GenericDatabase\Core\Select;
use GenericDatabase\Core\Sorting;
use GenericDatabase\Core\Junction;
use GenericDatabase\Core\Condition;
use GenericDatabase\Interfaces\IQueryBuilder;
use GenericDatabase\Engine\SQLiteQueryBuilder;
use GenericDatabase\Generic\QueryBuilder\Query;
use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Interfaces\QueryBuilder\IClause;

/**
 * Clause class for JSON QueryBuilder.
 * Handles building various SQL clauses for JSON data operations.
 *
 * @package GenericDatabase\Engine\JSON\QueryBuilder
 */
class Clause implements IClause
{
    use Query;

    /**
     * Build SELECT clause.
     *
     * @param array $arguments The arguments.
     * @return IQueryBuilder
     */
    public static function select(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new JSONQueryBuilder();
        $type = array_key_exists('type', $arguments) ? $arguments['type'] : Select::DEFAULT();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        $self->query->select['type'] = $type;
        $getSelect = fn($value) => Criteria::getSelect($value);
        foreach ($data as $column) {
            if (is_array($column)) {
                array_map(fn($key) => $self->query->select['columns'][] = $getSelect(['data' => $key]), $column);
            } elseif (is_string($column)) {
                if (str_contains($column, ',')) {
                    array_map(
                        fn($key) => $self->query->select['columns'][] = $getSelect(
                            ['data' => $key]
                        ),
                        explode(',', $column)
                    );
                } else {
                    $self->query->select['columns'][] = $getSelect(['data' => $column]);
                }
            }
        }
        return $self;
    }

    /**
     * Build FROM clause.
     *
     * @param array $arguments The arguments.
     * @return IQueryBuilder
     */
    public static function from(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new JSONQueryBuilder();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        foreach ($data as $table) {
            if (is_array($table)) {
                foreach ($table as $tableName) {
                    $self->query->from[] = Criteria::getFrom(['type' => Table::METADATA(), 'data' => trim($tableName)]);
                }
            } elseif (is_string($table)) {
                if (str_contains($table, ',')) {
                    foreach (explode(',', $table) as $tableName) {
                        $self->query->from[] = Criteria::getFrom(['type' => Table::METADATA(), 'data' => trim($tableName)]);
                    }
                } else {
                    $self->query->from[] = Criteria::getFrom(['type' => Table::METADATA(), 'data' => $table]);
                }
            }
        }
        return $self;
    }

    /**
     * Build JOIN clause (not supported for flat files, returns self).
     *
     * @param array $arguments The arguments.
     * @return IQueryBuilder
     */
    public static function join(array $arguments): IQueryBuilder
    {
        // JOIN not supported for flat files
        return array_key_exists('self', $arguments) ? $arguments['self'] : new JSONQueryBuilder();
    }

    /**
     * Build ON clause (not supported for flat files, returns self).
     *
     * @param array $arguments The arguments.
     * @return IQueryBuilder
     */
    public static function on(array $arguments): IQueryBuilder
    {
        // ON not supported for flat files
        return array_key_exists('self', $arguments) ? $arguments['self'] : new JSONQueryBuilder();
    }

    /**
     * Build WHERE clause.
     *
     * @param array $arguments The arguments.
     * @return IQueryBuilder
     */
    public static function makeWhere(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new JSONQueryBuilder();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        $enum = array_key_exists('enum', $arguments) ? $arguments['enum'] : Where::class;
        $condition = array_key_exists('condition', $arguments) ? $arguments['condition'] : Condition::NONE();
        $getWhere = fn($arrayData) => Criteria::getWhereHaving($arrayData);
        foreach ($data as $column) {
            if (is_array($column)) {
                array_map(fn($key) => $self->query->where[] = $getWhere([
                    'data' => $key,
                    'enum' => $enum,
                    'condition' => $condition
                ]), $column);
            } elseif (is_string($column)) {
                $self->query->where[] = $getWhere(['data' => $column, 'enum' => $enum, 'condition' => $condition]);
            }
        }
        return $self;
    }

    /**
     * Build WHERE clause with array support.
     *
     * @param array $arguments The arguments.
     * @return IQueryBuilder
     */
    public static function where(array $arguments): IQueryBuilder
    {
        $self = null;
        if (Arrays::isDepthArray($arguments) > 3) {
            foreach ($arguments as $argument) {
                $self = self::makeWhere($argument);
            }
        } else {
            $self = self::makeWhere($arguments);
        }
        return $self;
    }

    /**
     * Build HAVING clause.
     *
     * @param array $arguments The arguments.
     * @return IQueryBuilder
     */
    public static function makeHaving(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new JSONQueryBuilder();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        $enum = array_key_exists('enum', $arguments) ? $arguments['enum'] : Having::class;
        $condition = array_key_exists('condition', $arguments) ? $arguments['condition'] : Condition::NONE();
        $getHaving = fn($arrayData) => Criteria::getWhereHaving($arrayData);
        foreach ($data as $column) {
            if (is_array($column)) {
                array_map(fn($key) => $self->query->having[] = $getHaving([
                    'data' => $key,
                    'enum' => $enum,
                    'condition' => $condition
                ]), $column);
            } elseif (is_string($column)) {
                $self->query->having[] = $getHaving(['data' => $column, 'enum' => $enum, 'condition' => $condition]);
            }
        }
        return $self;
    }

    /**
     * Build HAVING clause with array support.
     *
     * @param array $arguments The arguments.
     * @return IQueryBuilder
     */
    public static function having(array $arguments): IQueryBuilder
    {
        $self = null;
        if (Arrays::isDepthArray($arguments) > 3) {
            foreach ($arguments as $argument) {
                $self = self::makeHaving($argument);
            }
        } else {
            $self = self::makeHaving($arguments);
        }
        return $self;
    }

    /**
     * Build GROUP BY clause.
     *
     * @param array $arguments The arguments.
     * @return IQueryBuilder
     */
    public static function group(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new JSONQueryBuilder();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        $getGroup = fn($value) => Criteria::getGroup($value);
        foreach ($data as $column) {
            if (is_array($column)) {
                array_map(fn($key) => $self->query->group[] = $getGroup(['data' => $key]), $column);
            } elseif (is_string($column)) {
                if (str_contains($column, ',')) {
                    $columns = explode(',', $column);
                    array_map(fn($key) => $self->query->group[] = $getGroup(['data' => $key]), $columns);
                } else {
                    $self->query->group[] = $getGroup(['data' => $column]);
                }
            }
        }
        return $self;
    }

    /**
     * Build ORDER BY clause.
     *
     * @param array $arguments The arguments.
     * @return IQueryBuilder
     */
    public static function order(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new JSONQueryBuilder();
        $sorting = array_key_exists('sorting', $arguments) ? $arguments['sorting'] : Sorting::NONE();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        $getOrder = fn($value) => Criteria::getOrder($value);
        foreach ($data as $column) {
            if (is_array($column)) {
                array_map(fn($key) => $self->query->order[] = $getOrder(
                    ['sorting' => $sorting, 'data' => $key]
                ), $column);
            } elseif (is_string($column)) {
                if (str_contains($column, ',')) {
                    array_map(
                        fn($key) => $self->query->order[] = $getOrder(
                            ['sorting' => $sorting, 'data' => $key]
                        ),
                        explode(',', $column)
                    );
                } else {
                    $self->query->order[] = $getOrder(['sorting' => $sorting, 'data' => $column]);
                }
            }
        }
        return $self;
    }

    /**
     * Build LIMIT clause.
     *
     * @param array $arguments The arguments.
     * @return IQueryBuilder
     */
    public static function limit(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new JSONQueryBuilder();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        if (Arrays::isDepthArray($data) === 1 && count($data) > 1) {
            $data = [implode(', ', $data)];
        }
        if (Arrays::isMultidimensional($data)) {
            $self->query->limit = Criteria::getLimit(['data' => implode(', ', reset($data))]);
        } else {
            $self->query->limit = Criteria::getLimit(['data' => reset($data)]);
        }
        return $self;
    }
}
