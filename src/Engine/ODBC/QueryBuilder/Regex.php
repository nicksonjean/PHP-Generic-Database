<?php

namespace GenericDatabase\Engine\ODBC\QueryBuilder;

class Regex
{
    private const SQL_AS = '(?:\s+(?:(?:AS|as)\s+)?)?';
    private const SQL_FUNCTION_NAME = '(?<function_name>\w+)';
    private const SQL_FUNCTION_ARGUMENTS = '(?<function_arguments>.*)';
    private const SQL_FUNCTION_COLUMN_ALIAS = '(?<function_column_alias>\w+)';
    private const SQL_TABLE_PREFIX = '(?<table_prefix>\w+)[\.]';
    private const SQL_COLUMN_NAME = '(?<column_name>\w+|\*)';
    private const SQL_COLUMN_ALIAS = '(?<column_alias>\w+)';
    private const SQL_TABLE_NAME = '(?<table_name>\w+|\*)';
    private const SQL_TABLE_ALIAS = '(?<table_alias>\w+)';
    private const SQL_TABLE_PREFIX_HOST = '(?<table_prefix_host>\w+)[\.]';
    private const SQL_COLUMN_NAME_HOST = '(?<column_name_host>\w+|\*)';
    private const SQL_SIGNAL = '(?<signal>[=<>!]+)';
    private const SQL_SIGNAL_WITH_SPACE = '(?:\s+' . self::SQL_SIGNAL . '\s+)?';
    private const SQL_FUNCTION_SIGNAL = '(?<function_signal>[=<>!]+)';
    private const SQL_TABLE_PREFIX_CONSUMER = '(?<table_prefix_consumer>\w+)[\.]';
    private const SQL_COLUMN_NAME_CONSUMER = '(?<column_name_consumer>\w+|\*)';
    private const SQL_LIMIT = '(?<limit>\w+)';
    private const SQL_OFFSET = '(?:[\,]\s*(?<offset>\w+))?';
    private const SQL_AGGREGATION = '(?<aggregation>(?:(?:NOT\s+)?(?:LIKE|IN|BETWEEN)))';
    private const SQL_ARGUMENTS = '(?<arguments>[^\n,| AND ]+)';
    private const SQL_AND_OR_COMMA = '\s*(?:(?:AND)|(?:,))\s*';
    private const SQL_ARGUMENTS_EXTRA = '(?<arguments_extra>[^\)\n]+)';
    private const SQL_ARGUMENTS_UNLIMITED = '(?:\(?(?<arguments_unlimited>[^\)\n]+)\)?)';
    private const SQL_FUNCTION_TABLE_ALIAS = '(?:(?<function_table_alias>\w+)\.)?';
    private const SQL_FUNCTION_COLUMN_NAME = '(?<function_column_name>\w+)';
    private const SQL_FUNCTION_AGGREGATION = '(?<function_aggregation>(?:(?:NOT\s+)?(?:LIKE|IN|BETWEEN)))';
    private const SQL_FUNCTION_ARGUMENTS_WITHOUT_COMMA = '(?<function_arguments>[^\n,| AND ]+)';
    private const SQL_FUNCTION_ARGUMENTS_EXTRA = '(?<function_arguments_extra>[^\)\n]+)';
    private const SQL_FUNCTION_ARGUMENTS_WITHOUT = '(?:\(?(?<function_arguments_unlimited>[^\)\n]+)\)?)';

    private static function getRegex(string $regex, int $init = 1, int $term = 0): string
    {
        return substr($regex, $init - 1, strlen($regex) - $term);
    }

    private static function regexLimit(): string
    {
        return '^(?:' . self::SQL_LIMIT . self::SQL_OFFSET . ')$';
    }

    public static function getLimit(): string
    {
        return '/^' . self::getRegex(self::regexLimit(), 2, 2) . '$/i';
    }

    private static function regexFunctionWithoutAlias(): string
    {
        return '^(?:' . self::SQL_FUNCTION_NAME . '\(' . self::SQL_FUNCTION_ARGUMENTS . '\))$';
    }

    private static function regexMetadataWithoutAlias(): string
    {
        return '^(?:(?:' . self::SQL_TABLE_PREFIX . ')?' . self::SQL_COLUMN_NAME . ')$';
    }

    public static function getGroupOrder(): string
    {
        return '/^' . self::getRegex(self::regexFunctionWithoutAlias(), 2, 2) . '|'
            . self::getRegex(self::regexMetadataWithoutAlias(), 2, 2) . '$/i';
    }

    private static function regexFunction(): string
    {
        return '^(?:' . self::SQL_FUNCTION_NAME . '\(' . self::SQL_FUNCTION_ARGUMENTS . '\)(?:' . self::SQL_AS .
            self::SQL_FUNCTION_COLUMN_ALIAS . ')?)$';
    }

    private static function regexMetadata(): string
    {
        return '^(?:(?:' . self::SQL_TABLE_PREFIX . ')?' . self::SQL_COLUMN_NAME . '(?:' . self::SQL_AS .
            self::SQL_COLUMN_ALIAS . ')?)$';
    }

    public static function getSelect(): string
    {
        return '/^' . self::getRegex(self::regexFunction(), 2, 2) . '|'
            . self::getRegex(self::regexMetadata(), 2, 2) . '$/i';
    }

    private static function regexFrom(): string
    {
        return '^(?:' . self::SQL_TABLE_NAME . '(?:' . self::SQL_AS . self::SQL_TABLE_ALIAS . ')?)$';
    }

    public static function getFrom(): string
    {
        return '/^' . self::getRegex(self::regexFrom(), 2, 2) . '$/i';
    }

    private static function regexOn(): string
    {
        return '^(?:' . self::SQL_TABLE_PREFIX_HOST . ')?' . self::SQL_COLUMN_NAME_HOST . '?'
            . self::SQL_SIGNAL_WITH_SPACE . '(?:' . self::SQL_TABLE_PREFIX_CONSUMER . ')?'
            . self::SQL_COLUMN_NAME_CONSUMER . '$';
    }

    public static function getOn(): string
    {
        return '/^' . self::getRegex(self::regexOn(), 2, 2) . '$/i';
    }

    private static function regexWhereHaving(): string
    {
        return '^(?:(?:' . self::SQL_TABLE_ALIAS . '\.)?' . self::SQL_COLUMN_NAME . '?(?:\s+(?:'
            . self::SQL_AGGREGATION . '|' . self::SQL_SIGNAL . ')\s+)(?:(?:' . self::SQL_ARGUMENTS
            . '(?:' . self::SQL_AND_OR_COMMA . self::SQL_ARGUMENTS_EXTRA . ')?)|' . self::SQL_ARGUMENTS_UNLIMITED
            . ')?)$|^(?:' . self::SQL_FUNCTION_NAME . '\(' . self::SQL_FUNCTION_TABLE_ALIAS
            . self::SQL_FUNCTION_COLUMN_NAME . '\)?(?:\s+(?:' . self::SQL_FUNCTION_AGGREGATION . '|'
            . self::SQL_FUNCTION_SIGNAL . ')\s+)(?:(?:' . self::SQL_FUNCTION_ARGUMENTS_WITHOUT_COMMA
            . '(?:' . self::SQL_AND_OR_COMMA . self::SQL_FUNCTION_ARGUMENTS_EXTRA
            . ')?)|' . self::SQL_FUNCTION_ARGUMENTS_WITHOUT . ')?)$';
    }

    public static function getWhereHaving(): string
    {
        return '/^' . self::getRegex(self::regexWhereHaving(), 2, 2) . '$/i';
    }
}
