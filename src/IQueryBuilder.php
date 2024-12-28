<?php

namespace GenericDatabase;

interface IQueryBuilder
{
    public static function select(array|string ...$data): IQueryBuilder;
    public static function distinct(array|string ...$data): IQueryBuilder;
    public static function from(array|string ...$data): IQueryBuilder;
    public static function join(array|string ...$data): IQueryBuilder;
    public static function selfJoin(array|string ...$data): IQueryBuilder;
    public static function leftJoin(array|string ...$data): IQueryBuilder;
    public static function rightJoin(array|string ...$data): IQueryBuilder;
    public static function innerJoin(array|string ...$data): IQueryBuilder;
    public static function outerJoin(array|string ...$data): IQueryBuilder;
    public static function crossJoin(array|string ...$data): IQueryBuilder;
    public static function on(array|string ...$data): IQueryBuilder;
    public static function andOn(array|string ...$data): IQueryBuilder;
    public static function orOn(array|string ...$data): IQueryBuilder;
    public static function where(array|string ...$data): IQueryBuilder;
    public static function andWhere(array|string ...$data): IQueryBuilder;
    public static function orWhere(array|string ...$data): IQueryBuilder;
    public static function having(array|string ...$data): IQueryBuilder;
    public static function andHaving(array|string ...$data): IQueryBuilder;
    public static function orHaving(array|string ...$data): IQueryBuilder;
    public static function group(array|string ...$data): IQueryBuilder;
    public static function order(array|string ...$data): IQueryBuilder;
    public static function orderAsc(array|string ...$data): IQueryBuilder;
    public static function orderDesc(array|string ...$data): IQueryBuilder;
    public static function limit(array|string ...$data): IQueryBuilder;
    public function build(): string;
    public function buildRaw(): string;
    public function getValues(): array;
    public function getAllMetadata(): object;
    public function fetch(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed;
    public function fetchAll(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool;
}
