<?php

declare(strict_types=1);

namespace GenericDatabase\Interfaces\QueryBuilder;

interface ICriteria
{
    public static function getSelect(array $arguments): array;

    public static function getFrom(array $arguments): array;

    public static function getJoin(array $arguments): array;

    public static function getOn(array $arguments): array;

    public static function getWhereHaving(array $arguments): array;

    public static function getGroup(array $arguments): array;

    public static function getOrder(array $arguments): array;

    public static function getLimit(array $arguments): array;
}
