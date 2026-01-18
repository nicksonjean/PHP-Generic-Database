<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\JSON\QueryBuilder;

use GenericDatabase\Interfaces\QueryBuilder\IRegex;

/**
 * Regex class for JSON QueryBuilder.
 * Handles regex patterns for parsing SQL-like syntax.
 *
 * @package GenericDatabase\Engine\JSON\QueryBuilder
 */
class Regex implements IRegex
{
    /**
     * Pattern for parsing WHERE conditions.
     */
    public const WHERE_PATTERN = '/^
        (?:(\w+)\.)? # Optional table prefix
        (?:(\w+)\s*\(\s*)? # Optional function
        (\w+) # Column name
        (?:\s*\))? # Optional closing paren for function
        \s*
        (=|!=|<>|>|<|>=|<=|LIKE|NOT\s+LIKE|IN|NOT\s+IN|BETWEEN|NOT\s+BETWEEN|IS\s+NULL|IS\s+NOT\s+NULL) # Operator
        \s*
        (?:
            \(([^)]+)\) # IN values
            |
            ([^\s]+(?:\s+AND\s+[^\s]+)?) # Regular value or BETWEEN values
            |
            ([\'"][^\'"]*[\'"]) # Quoted string
        )?
    $/ix';

    /**
     * Pattern for simple column = value.
     * Note: operators must be ordered from longest to shortest to match correctly
     */
    public const SIMPLE_PATTERN = '/^(\w+)\s*(>=|<=|!=|<>|=|>|<)\s*(.+)$/';

    /**
     * Pattern for LIKE clause.
     * Matches: column LIKE 'value', column LIKE "value", column NOT LIKE 'value'
     */
    public const LIKE_PATTERN = '/^(\w+)\s+(NOT\s+)?LIKE\s+(?:[\'"]([^\'\"]+)[\'"]|(\S+))$/i';

    /**
     * Pattern for IN clause.
     */
    public const IN_PATTERN = '/^(\w+)\s+(NOT\s+)?IN\s*\(([^)]+)\)$/i';

    /**
     * Pattern for BETWEEN clause.
     */
    public const BETWEEN_PATTERN = '/^(\w+)\s+(NOT\s+)?BETWEEN\s+([^\s]+)\s+AND\s+([^\s]+)$/i';

    /**
     * Pattern for IS NULL/IS NOT NULL.
     */
    public const NULL_PATTERN = '/^(\w+)\s+IS\s+(NOT\s+)?NULL$/i';

    /**
     * Pattern for functions like COUNT(), SUM(), etc.
     */
    public const FUNCTION_PATTERN = '/^(\w+)\s*\(\s*(\*|[\w.]+)\s*\)$/i';

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

    /**
     * Parse a WHERE condition string.
     *
     * @param string $condition The condition string.
     * @return array|null
     */
    public static function parseWhereCondition(string $condition): ?array
    {
        $condition = trim($condition);

        // Try simple pattern first
        if (preg_match(self::SIMPLE_PATTERN, $condition, $matches)) {
            return [
                'column' => $matches[1],
                'operator' => $matches[2],
                'value' => trim($matches[3], "'\""),
                'prefix' => null
            ];
        }

        // Try LIKE pattern
        if (preg_match(self::LIKE_PATTERN, $condition, $matches)) {
            $operator = !empty($matches[2]) ? 'NOT LIKE' : 'LIKE';
            // Value is in group 3 (quoted) or group 4 (unquoted)
            $value = !empty($matches[3]) ? $matches[3] : ($matches[4] ?? '');
            return [
                'column' => $matches[1],
                'operator' => $operator,
                'value' => $value,
                'prefix' => null
            ];
        }

        // Try IN pattern
        if (preg_match(self::IN_PATTERN, $condition, $matches)) {
            $operator = !empty($matches[2]) ? 'NOT IN' : 'IN';
            return [
                'column' => $matches[1],
                'operator' => $operator,
                'value' => $matches[3],
                'prefix' => null
            ];
        }

        // Try BETWEEN pattern
        if (preg_match(self::BETWEEN_PATTERN, $condition, $matches)) {
            $operator = !empty($matches[2]) ? 'NOT BETWEEN' : 'BETWEEN';
            return [
                'column' => $matches[1],
                'operator' => $operator,
                'value' => trim($matches[3], "'\""),
                'value2' => trim($matches[4], "'\""),
                'prefix' => null
            ];
        }

        // Try IS NULL pattern
        if (preg_match(self::NULL_PATTERN, $condition, $matches)) {
            $operator = !empty($matches[2]) ? 'IS NOT NULL' : 'IS NULL';
            return [
                'column' => $matches[1],
                'operator' => $operator,
                'value' => null,
                'prefix' => null
            ];
        }

        // Try with table prefix (table.column)
        if (preg_match('/^(\w+)\.(\w+)\s*(=|!=|<>|>|<|>=|<=)\s*(.+)$/', $condition, $matches)) {
            return [
                'column' => $matches[2],
                'operator' => $matches[3],
                'value' => trim($matches[4], "'\""),
                'prefix' => $matches[1]
            ];
        }

        return null;
    }

    /**
     * Parse a SELECT column expression.
     *
     * @param string $column The column expression.
     * @return array|null
     */
    public static function parseSelectColumn(string $column): ?array
    {
        $column = trim($column);

        // Check for function
        if (preg_match(self::FUNCTION_PATTERN, $column, $matches)) {
            return [
                'function' => strtoupper($matches[1]),
                'argument' => $matches[2],
                'alias' => null
            ];
        }

        // Check for alias
        if (preg_match('/^(.+)\s+AS\s+(\w+)$/i', $column, $matches)) {
            $inner = self::parseSelectColumn($matches[1]);
            if ($inner !== null) {
                $inner['alias'] = $matches[2];
                return $inner;
            }
            return [
                'column' => trim($matches[1]),
                'alias' => $matches[2]
            ];
        }

        // Check for table.column
        if (preg_match('/^(\w+)\.(\w+)$/', $column, $matches)) {
            return [
                'prefix' => $matches[1],
                'column' => $matches[2],
                'alias' => null
            ];
        }

        // Simple column
        return [
            'column' => $column,
            'alias' => null
        ];
    }

    /**
     * Convert SQL LIKE pattern to regex.
     *
     * @param string $pattern The LIKE pattern.
     * @return string The regex pattern.
     */
    public static function likeToRegex(string $pattern): string
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
}
