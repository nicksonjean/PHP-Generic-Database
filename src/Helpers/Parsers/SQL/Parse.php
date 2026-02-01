<?php

declare(strict_types=1);

namespace GenericDatabase\Helpers\Parsers\SQL;

use stdClass;
use GenericDatabase\Helpers\Types\Compounds\Arrays;

/**
 * The `GenericDatabase\Helpers\Parsers\SQL\Parse` class is responsible for
 * escaping SQL strings and replacing parameters and binds in the SQL queries.
 * It provides methods to escape SQL strings based on different SQL dialects,
 * extract SQL arguments, and replace SQL binds with different bind types.
 *
 * Example Usage:
 * <code>
 * // Escape an SQL query using the default dialect
 * $escapedQuery = Parse::escape("SELECT * FROM users WHERE id = :id");
 *
 * // Extract parameters from a raw SQL query
 * $parameters = Parse::parseParameters("SELECT * FROM users WHERE id = :id");
 *
 * // Bind parameters in an SQL query with question marks
 * $boundQuery = Parse::binding("SELECT * FROM users WHERE id = :id");
 * </code>
 *
 * Main functionalities:
 * - Escaping SQL strings by replacing certain characters with their escaped versions.
 * - Extracting SQL arguments from an SQL string.
 * - Replacing SQL binds with the specified bind type.
 * - Analyzing raw queries to return parameters.
 * - Loading reserved words from Lexicon and using them to escape the input string.
 *
 * @package GenericDatabase\Helpers\Parsers\SQL
 */
class Parse
{
    /**
     * SQL Dialect used by MySQL, MariaDB, Percona and Other Forks,
     * also as Drizzle, Derby H2, HSQLDB and SQLite
     */
    public const SQL_DIALECT_BACKTICK = 1;

    /**
     * SQL Dialect used by IBM DB2, Firebird, PostgreSQL, Oracle,
     * also as Microsoft SQL Server and Sybase
     */
    public const SQL_DIALECT_DOUBLE_QUOTE = 2;

    /**
     * SQL Dialect used by Cassandra, MongoDB and other Hybrid, Databases.
     */
    public const SQL_DIALECT_SINGLE_QUOTE = 3;

    /**
     * For none SQL Dialect and bypass character escape.
     */
    public const SQL_DIALECT_NONE = 4;

    /**
     * For the dialects that need question marks notation
     */
    public const BIND_QUESTION_MARK = 1;

    /**
     * For the dialects that need dollar sign notation
     */
    public const BIND_DOLLAR_SIGN = 2;

    /**
     * Regex patterns for use in class
     */
    private static array $patternMap = [
        'sqlBinds' => '/(:[a-zA-Z_][a-zA-Z0-9_]*)/',
        'sqlArgs' => '/(:\w+)/',
        'sqlGroups' => '/(\w+)?\((.+)\)\s/m'
    ];

    private static string $patternFunction =
    '/(?<function>\w+)\s*\(\s*(?:(?<table>["]?[a-zA-Z0-9_]+["]?)\.)?(?<column>["]?[a-zA-Z0-9_]+["]?)\s*\)/m';

    /**
     * SQL dialect array map
     */
    private static array $quoteMap = [
        self::SQL_DIALECT_BACKTICK => '`',
        self::SQL_DIALECT_DOUBLE_QUOTE => '"',
        self::SQL_DIALECT_SINGLE_QUOTE => "'",
        self::SQL_DIALECT_NONE => ''
    ];

    /**
     * Bind characters array map
     */
    private static array $bindingMap = [
        self::BIND_QUESTION_MARK => '?',
        self::BIND_DOLLAR_SIGN => '$'
    ];

    /**
     * Instance of reserved word dictionary
     */
    private static mixed $resWords;

    /**
     * Load reserved words from Lexicon.
     *
     * @return array The reserved words
     */
    private static function loadReservedWords(): array
    {
        if (!isset(self::$resWords)) {
            self::$resWords = Lexicon::getReservedWords();
        }
        return self::$resWords;
    }

    /**
     * Analyze raw query and return parameters (placeholders) found.
     * Supports both named parameters (:name) and positional placeholders (?).
     *
     * @param string $query The raw SQL query to analyze.
     * @return array Named parameters as keys without colon, or positional indices (0, 1, 2...).
     */
    public static function parseParameters(string $query): array
    {
        return self::arguments($query, null);
    }

    /**
     * Escape the input string
     *
     * @param string $input The input string that needs to be escaped
     * @param string $quote The quote character to be used for escaping
     * @return string The escaped string
     */
    private static function escapeType(string $input, string $quote): string
    {
        $resWords = self::loadReservedWords();
        $input = self::replaceParameters($input, $quote, $resWords);
        $words = preg_split('/\s+/', $input);
        $inSingleQt = false;
        $inDoubleQt = false;
        $inFunction = false;

        $escapedWords = array_map(function ($word) use ($resWords, $quote, &$inFunction, &$inSingleQt, &$inDoubleQt) {
            return self::processWord($word, $resWords, $quote, $inFunction, $inSingleQt, $inDoubleQt);
        }, $words);

        return implode(' ', $escapedWords);
    }

    /**
     * Replaces parameters in a given input string and returns the modified string.
     *
     * @param string $input The input string containing parameters to be replaced.
     * @param string $quote The quote character used for enclosing words.
     * @param array $resWords An array of words that should not be processed or enclosed.
     * @return string The modified input string with parameter groups replaced by enclosed words.
     */
    private static function replaceParameters(string $input, string $quote, array $resWords): string
    {
        return preg_replace_callback(
            self::$patternMap['sqlGroups'],
            function ($matches) use ($quote, $resWords) {
                if (!empty($matches) && !in_array($matches[1], $resWords)) {
                    $pWords = array_map(fn($word) => $quote . trim($word) . $quote, explode(',', trim($matches[2])));
                    return '(' . implode(', ', $pWords) . ') ';
                }
                return $matches[0];
            },
            $input
        );
    }

    /**
     * Applies the quotes in columns with alias.
     *
     * @param string $input The input SQL string.
     * @return string The input string with quotes in columns with alias.
     * @noinspection PhpUnused
     */
    private static function applyQuotes(string $input, string $quote): string
    {
        $wordWithComma = explode('.', substr($input, 0, -1));
        $wordWithoutComma = explode('.', $input);
        return (str_ends_with($input, ','))
            ? "$quote$wordWithComma[0]$quote.$quote$wordWithComma[1]$quote,"
            : "$quote$wordWithoutComma[0]$quote.$quote$wordWithoutComma[1]$quote";
    }

    /**
     * Encloses a word with quotes or backticks, depending on the SQL dialect.
     *
     * @param string $input The word to be enclosed.
     * @param string $quote The quote character to be used.
     * @return string The enclosed word.
     * @noinspection PhpUnused
     */
    private static function encloseWord(string $input, string $quote): string
    {
        $wordWithComma = substr($input, 0, -1);
        $wordWithoutComma = $input;
        return (str_ends_with($input, ','))
            ? "$quote$wordWithComma$quote,"
            : "$quote$wordWithoutComma$quote";
    }

    /**
     * Applies a wildcard to the input string if it contains a dot.
     *
     * @param string $input The input string to apply the wildcard to.
     * @param string $quote The quote character to use for enclosing the words.
     * @return string The input string with the wildcard applied, or the original input string if no wildcard is needed.
     * @noinspection PhpUnused
     */
    private static function applyWildCard(string $input, string $quote): string
    {
        if (str_contains($input, '.')) {
            $wordWithComma = explode('.', $input);
            return "$quote$wordWithComma[0]$quote.$wordWithComma[1]";
        } else {
            return $input;
        }
    }

    /** @noinspection PhpUnused */
    private static function isFunction(string $input): bool
    {
        $result = false;
        if (preg_match('/\w+\(.*\)/m', $input)) {
            $result = true;
        }
        return $result;
    }

    /** @noinspection PhpUnused */
    private static function applyQuotesFunction(string $input, string $quote): string
    {
        $result = '';
        if (preg_match(self::$patternFunction, $input, $matches)) {
            $matches = Arrays::arraySafe($matches);
            $result = (isset($matches['table']))
                ? preg_replace(self::$patternFunction, "$1($quote$2$quote.$quote$3$quote)", $input)
                : preg_replace(self::$patternFunction, "$1($quote$3$quote)", $input);
        }
        return $result;
    }

    /**
     * Processes a word based on certain conditions and returns the processed word.
     *
     * @param string $word The word to be processed.
     * @param array $resWords An array of words that should not be processed or enclosed.
     * @param string $quote The quote character used for enclosing words.
     * @param bool &$inFunction A flag indicating if the word is inside a function.
     * @param bool &$inSingleQt A flag indicating if the word is inside single quotes.
     * @param bool &$inDoubleQt A flag indicating if the word is inside double quotes.
     * @return string The processed word, either as is or enclosed with the quote character.
     */
    private static function processWord(
        string $word,
        array $resWords,
        string $quote,
        bool &$inFunction,
        bool $inSingleQt,
        bool $inDoubleQt
    ): string {
        $object = new stdClass();
        $bindQm = self::$bindingMap[self::BIND_QUESTION_MARK];
        $result = match (true) {
            self::isFunction($word) => self::applyQuotesFunction($word, $quote),
            str_contains($word, '*') => self::applyWildCard($word, $quote),
            str_contains($word, '.') => self::applyQuotes($word, $quote),
            str_contains($word, ']') => self::processCondition($object, $word, false),
            str_contains($word, '[') => self::processCondition($object, $word, true),
            $inFunction && str_contains($word, ')') => self::processCondition($object, $word, false),
            !$inFunction && str_contains($word, '(') => self::processCondition($object, $word, true),
            $inSingleQt && str_contains($word, "'") => self::processCondition($object, $word, false),
            !$inSingleQt && str_contains($word, "'") => self::processCondition($object, $word, true),
            $inDoubleQt && str_contains($word, '"') => self::processCondition($object, $word, false),
            !$inDoubleQt && str_contains($word, '"') => self::processCondition($object, $word, true),
            str_contains($word, ':') => $word,
            in_array(mb_strtoupper($word), $resWords) => mb_strtoupper($word),
            is_numeric($word) || preg_match('/\d+/im', $word) => $word,
            str_contains($word, $bindQm) => str_replace($quote . $bindQm . $quote, $bindQm, $word),
            default => self::encloseWord($word, $quote),
        };

        if (is_bool($result)) {
            $inFunction = $result;
        } else {
            $object->processedWord = $result;
        }

        return $object->processedWord;
    }

    /**
     * Processes a condition based on certain conditions and returns the processed condition.
     *
     * @param stdClass $object The object containing the processed word and the processed condition.
     * @param string $processedWord The processed word.
     * @param bool $processedCondition The processed condition.
     * @return bool The processed condition.
     * @noinspection PhpUnused
     */
    private static function processCondition(stdClass $object, string $processedWord, bool $processedCondition): bool
    {
        $object->processedWord = $processedWord;
        return $processedCondition;
    }

    /**
     * Escapes the SQL string by replacing parameters with their quoted versions.
     *
     * @param string $input The SQL string to be escaped.
     * @param int $dialect The SQL dialect to be used for escaping. Defaults to `Parse::SQL_DIALECT_NONE`.
     * @return string The escaped SQL string.
     */
    public static function escape(string $input, int $dialect = self::SQL_DIALECT_NONE, ?int $quoteSkip = null): string
    {
        foreach (self::$quoteMap as $char) {
            if (!is_null($quoteSkip) && $char !== self::$quoteMap[$quoteSkip]) {
                $input = str_replace($char, self::$quoteMap[self::SQL_DIALECT_NONE], $input);
            }
        }
        $quote = self::$quoteMap[$dialect] ?? '';
        return self::escapeType($input, $quote);
    }

    /**
     * Extracts the SQL arguments from the input string.
     *
     * @param string $input The SQL string to extract arguments from.
     * @param array|null $values The values to be used for replacing the SQL arguments. Defaults to `null`.
     * @return array The extracted SQL arguments.
     */
    public static function arguments(string $input, ?array $values = null): array
    {
        preg_match_all(self::$patternMap['sqlArgs'], $input, $matches);

        if (!empty($matches[1])) {
            if (is_null($values)) {
                return $matches[1];
            }
            if (count($matches[1]) !== count($values)) {
                throw new \ValueError(sprintf(
                    'array_combine(): Argument #1 ($keys) and argument #2 ($values) must have the same number of elements. Keys: %d, Values: %d',
                    count($matches[1]),
                    count($values)
                ));
            }
            return array_combine($matches[1], $values);
        }

        preg_match_all('/\?/', $input, $questionMatches);
        $placeholderCount = count($questionMatches[0]);

        if ($placeholderCount > 0) {
            if (is_null($values)) {
                return range(0, $placeholderCount - 1);
            }

            if (count($values) !== $placeholderCount) {
                $values = array_slice(array_pad($values, $placeholderCount, null), 0, $placeholderCount);
            }

            return array_combine(range(0, $placeholderCount - 1), $values);
        }

        if (is_null($values)) {
            return [];
        }

        return array_combine(array_keys($values), array_values($values));
    }

    /**
     * Replaces the SQL binds with the specified bind type.
     *
     * @param string $input The SQL string to replace the binds in.
     * @param int $bindType The type of binding to be used. Defaults to `Parse::BIND_QUESTION_MARK`.
     * @return string The SQL string with the binds replaced.
     */
    public static function binding(string $input, int $bindType = self::BIND_QUESTION_MARK): string
    {
        $bind = self::$bindingMap[$bindType] ?? '';
        return ($bindType === self::BIND_QUESTION_MARK)
            ? self::bindWithQuestionMark($input, $bind)
            : self::bindWithDollarSign($input, $bind);
    }

    /**
     * Replaces the SQL binds with question marks.
     *
     * @param string $input The SQL string to replace the binds in.
     * @param string $bindType The bind type to be used.
     * @return string The SQL string with the binds replaced.
     */
    private static function bindWithQuestionMark(string $input, string $bindType): string
    {
        return preg_replace(self::$patternMap['sqlBinds'], $bindType, $input);
    }

    /**
     * Replaces the SQL binds with dollar signs.
     *
     * @param string $input The SQL string to replace the binds in.
     * @param string $bindType The bind type to be used.
     * @return string The SQL string with the binds replaced.
     */
    private static function bindWithDollarSign(string $input, string $bindType): string
    {
        return preg_replace_callback(self::$patternMap['sqlBinds'], function () use (&$bindType) {
            static $dollarCount = 1;
            return sprintf("$bindType%d", $dollarCount++);
        }, $input);
    }
}
