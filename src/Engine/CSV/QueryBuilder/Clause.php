<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\CSV\QueryBuilder;

use GenericDatabase\Interfaces\IQueryBuilder;
use GenericDatabase\Core\Table;
use GenericDatabase\Core\Where;
use GenericDatabase\Core\Having;
use GenericDatabase\Core\Select;
use GenericDatabase\Core\Sorting;
use GenericDatabase\Core\Condition;
use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Engine\CSVQueryBuilder;
use GenericDatabase\Generic\QueryBuilder\Query;
use GenericDatabase\Interfaces\QueryBuilder\IClause;
use GenericDatabase\Engine\JSON\QueryBuilder\Criteria;

/**
 * Clause class for CSV QueryBuilder.
 *
 * @package GenericDatabase\Engine\CSV\QueryBuilder
 */
class Clause implements IClause
{
    use Query;

    public static function select(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new CSVQueryBuilder();
        $type = array_key_exists('type', $arguments) ? $arguments['type'] : Select::DEFAULT();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        $self->query->select['type'] = $type;
        foreach ($data as $column) {
            if (is_array($column)) {
                array_map(fn($key) => $self->query->select['columns'][] = Criteria::getSelect(['data' => $key]), $column);
            } elseif (is_string($column)) {
                if (str_contains($column, ',')) {
                    array_map(fn($key) => $self->query->select['columns'][] = Criteria::getSelect(['data' => $key]), explode(',', $column));
                } else {
                    $self->query->select['columns'][] = Criteria::getSelect(['data' => $column]);
                }
            }
        }
        return $self;
    }

    public static function from(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new CSVQueryBuilder();
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

    public static function join(array $arguments): IQueryBuilder
    {
        return array_key_exists('self', $arguments) ? $arguments['self'] : new CSVQueryBuilder();
    }

    public static function on(array $arguments): IQueryBuilder
    {
        return array_key_exists('self', $arguments) ? $arguments['self'] : new CSVQueryBuilder();
    }

    public static function makeWhere(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new CSVQueryBuilder();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        $enum = array_key_exists('enum', $arguments) ? $arguments['enum'] : Where::class;
        $condition = array_key_exists('condition', $arguments) ? $arguments['condition'] : Condition::NONE();
        foreach ($data as $column) {
            if (is_array($column)) {
                array_map(fn($key) => $self->query->where[] = Criteria::getWhereHaving(['data' => $key, 'enum' => $enum, 'condition' => $condition]), $column);
            } elseif (is_string($column)) {
                $self->query->where[] = Criteria::getWhereHaving(['data' => $column, 'enum' => $enum, 'condition' => $condition]);
            }
        }
        return $self;
    }

    public static function where(array $arguments): IQueryBuilder
    {
        if (Arrays::isDepthArray($arguments) > 3) {
            $self = null;
            foreach ($arguments as $argument) {
                $self = self::makeWhere($argument);
            }
            return $self;
        }
        return self::makeWhere($arguments);
    }

    public static function makeHaving(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new CSVQueryBuilder();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        $enum = array_key_exists('enum', $arguments) ? $arguments['enum'] : Having::class;
        $condition = array_key_exists('condition', $arguments) ? $arguments['condition'] : Condition::NONE();
        foreach ($data as $column) {
            if (is_array($column)) {
                array_map(fn($key) => $self->query->having[] = Criteria::getWhereHaving(['data' => $key, 'enum' => $enum, 'condition' => $condition]), $column);
            } elseif (is_string($column)) {
                $self->query->having[] = Criteria::getWhereHaving(['data' => $column, 'enum' => $enum, 'condition' => $condition]);
            }
        }
        return $self;
    }

    public static function having(array $arguments): IQueryBuilder
    {
        if (Arrays::isDepthArray($arguments) > 3) {
            $self = null;
            foreach ($arguments as $argument) {
                $self = self::makeHaving($argument);
            }
            return $self;
        }
        return self::makeHaving($arguments);
    }

    public static function group(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new CSVQueryBuilder();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        foreach ($data as $column) {
            if (is_array($column)) {
                array_map(fn($key) => $self->query->group[] = Criteria::getGroup(['data' => $key]), $column);
            } elseif (is_string($column)) {
                if (str_contains($column, ',')) {
                    array_map(fn($key) => $self->query->group[] = Criteria::getGroup(['data' => $key]), explode(',', $column));
                } else {
                    $self->query->group[] = Criteria::getGroup(['data' => $column]);
                }
            }
        }
        return $self;
    }

    public static function order(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new CSVQueryBuilder();
        $sorting = array_key_exists('sorting', $arguments) ? $arguments['sorting'] : Sorting::NONE();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        foreach ($data as $column) {
            if (is_array($column)) {
                array_map(fn($key) => $self->query->order[] = Criteria::getOrder(['sorting' => $sorting, 'data' => $key]), $column);
            } elseif (is_string($column)) {
                if (str_contains($column, ',')) {
                    array_map(fn($key) => $self->query->order[] = Criteria::getOrder(['sorting' => $sorting, 'data' => $key]), explode(',', $column));
                } else {
                    $self->query->order[] = Criteria::getOrder(['sorting' => $sorting, 'data' => $column]);
                }
            }
        }
        return $self;
    }

    public static function limit(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new CSVQueryBuilder();
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


