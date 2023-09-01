<?php

namespace GenericDatabase\Helpers;

class Translater
{
    /**
     * SQL Diaclect used by MySQL, MariaDB, Percona and Other Forks,
     * also as Drizzle, Derby H2, HSQLDB and SQLite
     */
    public const SQL_DIALECT_BTICK = 1;

    /**
     * SQL Dialect used by IBM DB2, Firebird, PostgreSQL, Oracle,
     * also as Microsoft SQL Server and Sybase
     */
    public const SQL_DIALECT_DQUOTE = 2;

    /**
     * SQL Dialect used by Cassandra, MongoDB and other Hybrid, Databases.
     */
    public const SQL_DIALECT_SQUOTE = 3;

    /**
     * For none SQL Dialect and bypass character escape.
     */
    public const SQL_DIALECT_NONE = 4;

    /**
     * Regex pattern for only numbers
     */
    private static $patterns = [
        'binding' => '/(:[a-zA-Z]{1,})/i',
        'parameters' => '/(:\w+)/'
    ];

    private static function loadForbiddenWords(string $file)
    {
        $json = file_get_contents($file);
        return json_decode($json, true);
    }

    private static function escapeType(string $input, string $quote)
    {
        $forbiddenWords = self::loadForbiddenWords(__DIR__ . './SQLDict.json');
        $regex = '/(\w+)?\((.+)\)\s/m';
        $mask = '(_PARAM_) ';

        preg_match_all($regex, $input, $matches, PREG_SET_ORDER, 0);

        if (!empty($matches) && !in_array($matches[0][1], $forbiddenWords)) {
            $input = preg_replace($regex, $mask, $input);
            $pWords = array_map(fn ($word) => $quote . trim($word) . $quote, explode(',', trim($matches[0][2])));
            $input = str_replace($mask, '(' . implode(', ', $pWords) . ') ', $input);
        }

        $words = preg_split('/\s+/', $input);

        $insideSingleQuote = false;
        $insideDoubleQuote = false;

        $escapedWords = array_map(function ($word) use ($forbiddenWords, $quote, &$insideFunction, &$insideSingleQuote, &$insideDoubleQuote) {
            if ($insideFunction && strpos($word, ')') !== false) {
                $insideFunction = false;
                return $word;
            }

            if (!$insideFunction && strpos($word, '(') !== false) {
                $insideFunction = true;
                return $word;
            }

            if ($insideSingleQuote && strpos($word, "'") !== false) {
                $insideSingleQuote = false;
                return $word;
            }

            if (!$insideSingleQuote && strpos($word, "'") !== false) {
                $insideSingleQuote = true;
                return $word;
            }

            if ($insideDoubleQuote && strpos($word, '"') !== false) {
                $insideDoubleQuote = false;
                return $word;
            }

            if (!$insideDoubleQuote && strpos($word, '"') !== false) {
                $insideDoubleQuote = true;
                return $word;
            }

            if (strpos($word, ':') !== false) {
                return $word;
            }

            if (in_array($word, $forbiddenWords) || $insideFunction || $insideSingleQuote || $insideDoubleQuote) {
                return $word;
            } else {
                if (substr($word, -1) == ',') {
                    $word = $quote . substr($word, 0, -1) . $quote . ',';
                } else {
                    $word = $quote . $word . $quote;
                }
                return $word;
            }
        }, $words);

        return implode(' ', $escapedWords);
    }

    public static function escape(string $input, int $dialect = self::SQL_DIALECT_NONE)
    {
        $quote = match ($dialect) {
            self::SQL_DIALECT_DQUOTE => '"',
            self::SQL_DIALECT_BTICK => '`',
            self::SQL_DIALECT_SQUOTE => "'",
            default => '',
        };

        return self::escapeType($input, $quote);
    }


    public static function parameters(string $input, array $values = null)
    {
        preg_match_all(self::$patterns['parameters'], $input, $matches);
        if (is_null($values)) {
            return $matches[1];
        }
        return array_combine($matches[1], (array) $values);
    }

    /**
     * Replace binding param name to another bind type
     *
     * @param string $value The SQL Query with binding names
     * @param bool $bindType The binding type Value
     * @return string The SQL Query post processed with binding types
     */
    public static function binding(string $value, bool $bindType = true)
    {
        return $bindType
            ? preg_replace(self::$patterns['binding'], '?', $value)
            :   preg_replace_callback(self::$patterns['binding'], function () {
                static $count = 1;
                return '$' . $count++;
            }, $value);
    }
}
