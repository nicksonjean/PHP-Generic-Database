<?php

namespace GenericDatabase\Helpers;

use stdClass;

/**
 * The `GenericDatabase\Helpers\Translater` class is responsible for
 * escaping SQL strings and replacing parameters and binds in the SQL queries.
 * It provides methods to escape SQL strings based on different SQL dialects,
 * extract SQL arguments, and replace SQL binds with different bind types.
 *
 * Example Usage:
 * <code>
 * //Escape an SQL query using the default dialect
 * $escapedQuery = Translater::escape("SELECT * FROM users WHERE id = :id");
 *
 * //Escape an SQL query using a specific dialect
 * $escapedQuery = Translater::escape("SELECT * FROM users WHERE id = :id", Translater::SQL_DIALECT_DQUOTE);
 *
 * //Extract parameters from an SQL query
 * $parameters = Translater::arguments("SELECT * FROM users WHERE id = :id");
 *
 * //Bind parameters in an SQL query with question marks
 * $boundQuery = Translater::binding("SELECT * FROM users WHERE id = :id");
 *
 * //Bind parameters in an SQL query with dollar signs
 * $boundQuery = Translater::binding("SELECT * FROM users WHERE id = :id", Translater::BIND_QUESTION_MARK);
 * </code>
 *
 * Main functionalities:
 * - Escaping SQL strings by replacing certain characters with their escaped versions.
 * - Extracting SQL arguments from an SQL string.
 * - Replacing SQL binds with the specified bind type.
 * - Processing words based on certain conditions, such as whether they are inside quotes or a function.
 * - Loading reserved words from a JSON file and using them to escape the input string.
 *
 * Methods:
 * - `loadReservedWords():`
 * Loads reserved words from a JSON file and returns them as an array.
 * - `escapeType(string $input, string $quote):`
 * Escapes the input string by replacing certain characters with their escaped versions.
 * - `replaceParameters(string $input, string $quote, array $resWords):`
 * Replaces parameters in a given input string and returns the modified string.
 * - `processWord(string $word, array $resWords, string $quote, bool &$inFunction, bool $inSingleQt, bool $inDoubleQt):`
 * Processes a word based on certain conditions and returns the processed word.
 * - `processCondition(stdClass $object, string $processedWord, bool $processedCondition):`
 * Processes a condition based on certain conditions and returns the processed condition.
 * - `encloseWord(string $word, string $quote):`
 * Encloses a word with quotes or backticks, depending on the SQL dialect.
 * - `escape(string $input, int $dialect = self::SQL_DIALECT_NONE):`
 * Escapes the SQL string by replacing parameters with their quoted versions.
 * - `arguments(string $input, array $values = null):`
 * Extracts the SQL arguments from the input string.
 * - `binding(string $input, int $bindType = self::BIND_QUESTION_MARK):`
 * Replaces the SQL binds with the specified bind type.
 * - `bindWithQuestionMark(string $input, string $bindType):`
 * Replaces the SQL binds with question marks.
 * - `bindWithDollarSign(string $input, string $bindType):`
 * Replaces the SQL binds with dollar signs.
 *
 * Fields:
 * - `SQL_DIALECT_BTICK`: Constant representing the SQL dialect using backticks.
 * - `SQL_DIALECT_DQUOTE`: Constant representing the SQL dialect using double quotes.
 * - `SQL_DIALECT_SQUOTE`: Constant representing the SQL dialect using single quotes.
 * - `SQL_DIALECT_NONE`: Constant representing no SQL dialect.
 * - `BIND_QUESTION_MARK`: Constant representing the bind type using question marks.
 * - `BIND_DOLLAR_SIGN`: Constant representing the bind type using dollar signs.
 * - `$patternMap`: An array mapping regex patterns used in the class.
 * - `$quoteMap`: An array mapping SQL dialects to their corresponding quote characters.
 * - `$bindingMap`: An array mapping bind types to their corresponding bind characters.
 * - `$resWords`: An instance of the reserved word dictionary, loaded from a JSON file.
 *
 * @package Translater
 */
class Translater
{
    /**
     * SQL Dialect used by MySQL, MariaDB, Percona and Other Forks,
     * also as Drizzle, Derby H2, HSQLDB and SQLite
     */
    final public const SQL_DIALECT_BTICK = 1;

    /**
     * SQL Dialect used by IBM DB2, Firebird, PostgreSQL, Oracle,
     * also as Microsoft SQL Server and Sybase
     */
    final public const SQL_DIALECT_DQUOTE = 2;

    /**
     * SQL Dialect used by Cassandra, MongoDB and other Hybrid, Databases.
     */
    final public const SQL_DIALECT_SQUOTE = 3;

    /**
     * For none SQL Dialect and bypass character escape.
     */
    final public const SQL_DIALECT_NONE = 4;

    /**
     * For the dialects that need question marks notation
     */
    final public const BIND_QUESTION_MARK = 1;

    /**
     * For the dialects that need dollar sign notation
     */
    final public const BIND_DOLLAR_SIGN = 2;

    /**
     * Regex patterns for use in class
     */
    private static array $patternMap = [
        'sqlBinds' => '/(:[a-zA-Z]{1,})/i',
        'sqlArgs' => '/(:\w+)/',
        'sqlGroups' => '/(\w+)?\((.+)\)\s/m',
        'sqlMask' => '(_PARAM_) '
    ];

    /**
     * SQL dialect array map
     */
    private static array $quoteMap = [
        self::SQL_DIALECT_DQUOTE => '"',
        self::SQL_DIALECT_BTICK => '`',
        self::SQL_DIALECT_SQUOTE => "'",
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
     * Load reserved words from JSON file
     * This method loads the reserved words from a JSON file and returns them as an array.
     *
     * @return array The reserved words
     */
    private static function loadReservedWords(): array
    {
        if (!isset(self::$resWords)) {
            $json = file_get_contents(__DIR__ . '/Translater/Dictionary.json');
            self::$resWords = json_decode($json, true);
        }
        return self::$resWords;
    }

    /**
     * Escape the input string
     *
     * This method escapes the input string by replacing certain characters with their escaped versions.
     * It uses the `loadReservedWords()` method to get the reserved words and then splits the input
     * string into individual words.
     * It then iterates over each word and processes it based on certain conditions, such as whether it
     * is inside quotes or a function.
     * The processed words are then joined back together and returned as the escaped string.
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
        $mask = self::$patternMap['sqlMask'];
        return preg_replace_callback(
            self::$patternMap['sqlGroups'],
            function ($matches) use ($mask, $quote, $resWords) {
                if (!empty($matches) && !in_array($matches[1], $resWords)) {
                    $pWords = array_map(fn ($word) => $quote . trim($word) . $quote, explode(',', trim($matches[2])));
                    return str_replace($mask, '(' . implode(', ', $pWords) . ') ', $matches[0]);
                }
                return $matches[0];
            },
            $input
        );
    }

    /**
     * Processes a word based on certain conditions and returns the processed word.
     *
     * @param string $word The word to be processed.
     * @param array $resWords An array of words that should not be processed or enclosed.
     * @param string $quote The quote character used for enclosing words.
     * @param bool &$inFunction A flag indicating if the word is inside a function.
     * @param bool $inSingleQt A flag indicating if the word is inside single quotes.
     * @param bool $inDoubleQt A flag indicating if the word is inside double quotes.
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

        $result = match (true) {
            $inFunction && str_contains($word, ')') => self::processCondition($object, $word, false),
            !$inFunction && str_contains($word, '(') => self::processCondition($object, $word, true),
            $inSingleQt && str_contains($word, "'") => self::processCondition($object, $word, false),
            !$inSingleQt && str_contains($word, "'") => self::processCondition($object, $word, true),
            $inDoubleQt && str_contains($word, '"') => self::processCondition($object, $word, false),
            !$inDoubleQt && str_contains($word, '"') => self::processCondition($object, $word, true),
            str_contains($word, ':') => $word,
            in_array($word, $resWords) || $inFunction || $inSingleQt || $inDoubleQt => $word,
            default => self::encloseWord($word, $quote),
        };

        if (is_bool($result)) {
            $inFunction = $result;
        } else {
            $object->processedWord = $result;
        }

        return $object->processedWord;
    }

    private static function processCondition(stdClass $object, string $processedWord, bool $processedCondition): bool
    {
        $object->processedWord = $processedWord;
        return $processedCondition;
    }

    /**
     * Encloses a word with quotes or backticks, depending on the SQL dialect.
     *
     * @param string $word The word to be enclosed.
     * @param string $quote The quote character to be used.
     * @return string The enclosed word.
     */
    private static function encloseWord(string $word, string $quote): string
    {
        return (str_ends_with($word, ',')) ? $quote . substr($word, 0, -1) . $quote . ',' : $quote . $word . $quote;
    }

    /**
     * Escapes the SQL string by replacing parameters with their quoted versions.
     *
     * @param string $input The SQL string to be escaped.
     * @param int $dialect The SQL dialect to be used for escaping. Defaults to `Translater::SQL_DIALECT_NONE`.
     * @return string The escaped SQL string.
     */
    public static function escape(string $input, int $dialect = self::SQL_DIALECT_NONE): string
    {
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
    public static function arguments(string $input, array $values = null): array
    {
        preg_match_all(self::$patternMap['sqlArgs'], $input, $matches);
        if (is_null($values)) {
            return $matches[1];
        }
        return array_combine($matches[1], $values);
    }

    /**
     * Replaces the SQL binds with the specified bind type.
     *
     * @param string $input The SQL string to replace the binds in.
     * @param int $bindType The type of binding to be used. Defaults to `Translater::BIND_QUESTION_MARK`.
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
