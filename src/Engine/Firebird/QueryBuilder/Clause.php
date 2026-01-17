<?php

namespace GenericDatabase\Engine\Firebird\QueryBuilder;

use GenericDatabase\Interfaces\IQueryBuilder;
use GenericDatabase\Core\Join;
use GenericDatabase\Core\Table;
use GenericDatabase\Core\Where;
use GenericDatabase\Core\Having;
use GenericDatabase\Core\Select;
use GenericDatabase\Core\Sorting;
use GenericDatabase\Core\Junction;
use GenericDatabase\Core\Condition;
use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Engine\FirebirdQueryBuilder;
use GenericDatabase\Generic\QueryBuilder\Query;
use GenericDatabase\Interfaces\QueryBuilder\IClause;

class Clause implements IClause
{
    use Query;

    public static function select(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new FirebirdQueryBuilder();
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
                        fn($key) => $self->query->select['columns'][] = $getSelect(['data' => $key]),
                        explode(',', $column)
                    );
                } else {
                    $self->query->select['columns'][] = $getSelect(['data' => $column]);
                }
            }
        }
        return $self;
    }

    public static function from(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new FirebirdQueryBuilder();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        foreach ($data as $table) {
            if (is_array($table)) {
                foreach ($table as $tableName) {
                    $self->query->from[] = Criteria::getFrom(['type' => Table::METADATA(), 'data' => trim($tableName)]);
                }
            } elseif (is_string($table)) {
                if (str_contains($table, ',')) {
                    foreach (explode(',', $table) as $tableName) {
                        $self->query->from[] = Criteria::getFrom(
                            ['type' => Table::METADATA(), 'data' => trim($tableName)]
                        );
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
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new FirebirdQueryBuilder();
        $type = array_key_exists('type', $arguments) ? $arguments['type'] : Join::DEFAULT();
        $junction = array_key_exists('junction', $arguments) ? $arguments['junction'] : Junction::NONE();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        if (Arrays::isMultidimensional(reset($data))) {
            $parsedJoin = Criteria::getJoin(['type' => $type, 'junction' => $junction, 'data' => $data]);
            $self->query->join[] = $parsedJoin['join'];
            $self->query->on[] = $parsedJoin['on'];
        } else {
            foreach ($data as $table) {
                if (is_array($table)) {
                    $self->query->join[] = Criteria::getJoin(
                        ['type' => $type, 'junction' => $junction, 'data' => $table]
                    );
                } elseif (is_string($table)) {
                    if (!str_contains($table, ',')) {
                        $self->query->join[] = Criteria::getJoin(
                            ['type' => $type, 'junction' => $junction, 'data' => $table]
                        );
                    } else {
                        $join = array_map(fn($tableName) => [trim($tableName)], explode(',', $table));
                        $parsedJoin = Criteria::getJoin(['type' => $type, 'junction' => $junction, 'data' => [$join]]);
                        $self->query->join[] = $parsedJoin['join'];
                        $self->query->on[] = $parsedJoin['on'];
                    }
                }
            }
        }
        return $self;
    }

    public static function on(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new FirebirdQueryBuilder();
        $junction = array_key_exists('junction', $arguments) ? $arguments['junction'] : Junction::NONE();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        foreach ($data as $table) {
            if (is_array($table)) {
                foreach ($table as $tableName) {
                    $parsedOns = Criteria::getOn(['junction' => $junction, 'data' => $tableName]);
                    $self->query->on[] = $parsedOns;
                }
            } elseif (is_string($table)) {
                if (str_contains($table, ',')) {
                    foreach (explode(',', $table) as $tableName) {
                        $parsedOns = Criteria::getOn(['junction' => $junction, 'data' => $tableName]);
                        $self->query->on[] = $parsedOns;
                    }
                } else {
                    $parsedOns = Criteria::getOn(['junction' => $junction, 'data' => $table]);
                    $self->query->on[] = $parsedOns;
                }
            }
        }
        return $self;
    }

    public static function makeWhere(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new FirebirdQueryBuilder();
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

    public static function makeHaving(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new FirebirdQueryBuilder();
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

    public static function group(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new FirebirdQueryBuilder();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        $getGroup = fn($value) => Criteria::getGroup($value);
        foreach ($data as $column) {
            if (is_array($column)) {
                array_map(fn($key) => $self->query->group[] = $getGroup(['data' => $key]), $column);
            } elseif (is_string($column)) {
                if (str_contains($column, ',')) {
                    array_map(fn($key) => $self->query->group[] = $getGroup(['data' => $key]), explode(',', $column));
                } else {
                    $self->query->group[] = $getGroup(['data' => $column]);
                }
            }
        }
        return $self;
    }

    public static function order(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new FirebirdQueryBuilder();
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

    public static function limit(array $arguments): IQueryBuilder
    {
        $self = array_key_exists('self', $arguments) ? $arguments['self'] : new FirebirdQueryBuilder();
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
