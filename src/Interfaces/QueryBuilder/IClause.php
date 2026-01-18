<?php

namespace GenericDatabase\Interfaces\QueryBuilder;

use GenericDatabase\Interfaces\IQueryBuilder;

interface IClause
{
    public static function select(array $arguments): IQueryBuilder;

    public static function from(array $arguments): IQueryBuilder;

    public static function join(array $arguments): IQueryBuilder;

    public static function on(array $arguments): IQueryBuilder;

    public static function makeWhere(array $arguments): IQueryBuilder;

    public static function where(array $arguments): IQueryBuilder;

    public static function makeHaving(array $arguments): IQueryBuilder;

    public static function having(array $arguments): IQueryBuilder;

    public static function group(array $arguments): IQueryBuilder;

    public static function order(array $arguments): IQueryBuilder;

    public static function limit(array $arguments): IQueryBuilder;
}

