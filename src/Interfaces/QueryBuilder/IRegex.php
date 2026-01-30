<?php

declare(strict_types=1);

namespace GenericDatabase\Interfaces\QueryBuilder;

interface IRegex
{
    public static function getLimit(): string;

    public static function getGroupOrder(): string;

    public static function getSelect(): string;

    public static function getFrom(): string;

    public static function getOn(): string;

    public static function getWhereHaving(): string;
}
