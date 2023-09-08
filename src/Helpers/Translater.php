<?php

namespace GenericDatabase\Helpers;

/**
 * The `Translater` class is responsible for escaping SQL queries and extracting parameters from them.
 * It supports different SQL dialects and provides methods for binding parameters with question marks or dollar signs.
 *
 * Example Usage:
 *
 * Escape an SQL query using the default dialect
 * $escapedQuery = Translater::escape("SELECT * FROM users WHERE id = :id");
 *
 * Escape an SQL query using a specific dialect
 * $escapedQuery = Translater::escape("SELECT * FROM users WHERE id = :id", Translater::SQL_DIALECT_DQUOTE);
 *
 * Extract parameters from an SQL query
 * $parameters = Translater::parameters("SELECT * FROM users WHERE id = :id");
 *
 * Bind parameters in an SQL query with question marks
 * $boundQuery = Translater::binding("SELECT * FROM users WHERE id = :id");
 *
 * Bind parameters in an SQL query with dollar signs
 * $boundQuery = Translater::binding("SELECT * FROM users WHERE id = :id", false);
 *
 * Main functionalities:
 * - Escaping SQL queries based on the specified SQL dialect or quote character.
 * - Extracting parameters from SQL queries.
 * - Binding parameters in SQL queries with question marks or dollar signs.
 *
 * Methods:
 * - `escape(string $input, int $dialect = self::SQL_DIALECT_NONE): string`:
 * Escapes the input SQL query based on the specified SQL dialect. Returns the escaped query.
 * - `parameters(string $input, array $values = null): array`: Extracts the parameters from the input SQL query.
 * Returns an array of parameters or a combined array of parameters and values.
 * - `binding(string $value, bool $bindType = true): string`: Binds the parameters in the input SQL query with
 * question marks or dollar signs based on the specified bind type. Returns the bound query.
 *
 * Fields:
 * - `SQL_DIALECT_BTICK`: Constant representing the SQL dialect using backticks.
 * - `SQL_DIALECT_DQUOTE`: Constant representing the SQL dialect using double quotes.
 * - `SQL_DIALECT_SQUOTE`: Constant representing the SQL dialect using single quotes.
 * - `SQL_DIALECT_NONE`: Constant representing no SQL dialect.
 * - `patternMap`: Array containing regex patterns for binding and parameter extraction.
 * - `quoteMap`: Array mapping SQL dialects to quote characters.
 * - `forbiddenWords`: Array containing forbidden words loaded from a JSON file.
 */
class Translater
{
    /**
     * SQL Dialect used by MySQL, MariaDB, Percona and Other Forks,
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
    private static $patternMap = [
        'sqlBinds' => '/(:[a-zA-Z]{1,})/i',
        'sqlArgs' => '/(:\w+)/',
        'sqlGroups' => '/(\w+)?\((.+)\)\s/m',
        'sqlMask' => '(_PARAM_) '
    ];

    /**
     * SQL dialect array map
     */
    private static $quoteMap = [
        self::SQL_DIALECT_DQUOTE => '"',
        self::SQL_DIALECT_BTICK => '`',
        self::SQL_DIALECT_SQUOTE => "'",
        self::SQL_DIALECT_NONE => ''
    ];

    /**
     * Bind characters array map
     */
    private static $bindingMap = [
        self::BIND_QUESTION_MARK => '?',
        self::BIND_DOLLAR_SIGN => '$'
    ];

    /**
     * Instance of reserved word dictionary
     */
    private static $forbiddenWords;

    /**
     * Load forbidden words from JSON file
     * This method loads the forbidden words from a JSON file and returns them as an array.
     *
     * @return array The forbidden words
     */
    private static function loadForbiddenWords(): array
    {
        if (!isset(self::$forbiddenWords)) {
            $json = file_get_contents(__DIR__ . '/Translater/Dictionary.json');
            self::$forbiddenWords = json_decode($json, true);
        }
        return self::$forbiddenWords;
    }

    /**
     * Escape the input string
     *
     * This method escapes the input string by replacing certain characters with their escaped versions.
     * It uses the `loadForbiddenWords()` method to get the forbidden words and then splits the input
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
        $forbiddenWords = self::loadForbiddenWords();
        $input = self::replaceParameters($input, $quote, $forbiddenWords);
        $words = preg_split('/\s+/', $input);
        $insideSingleQuote = false;
        $insideDoubleQuote = false;
        $insideFunction = false;

        $escapedWords = array_map(function ($word) use (
            $forbiddenWords,
            $quote,
            &$insideFunction,
            &$insideSingleQuote,
            &$insideDoubleQuote
        ) {
            return self::processWord(
                $word,
                $forbiddenWords,
                $quote,
                $insideFunction,
                $insideSingleQuote,
                $insideDoubleQuote
            );
        }, $words);

        return implode(' ', $escapedWords);
    }

    /**
     * Replaces parameters in a given input string and returns the modified string.
     *
     * @param string $input The input string containing parameters to be replaced.
     * @param string $quote The quote character used for enclosing words.
     * @param array $forbiddenWords An array of words that should not be processed or enclosed.
     * @return string The modified input string with parameter groups replaced by enclosed words.
     */
    private static function replaceParameters(string $input, string $quote, array $forbiddenWords): string
    {
        $mask = self::$patternMap['sqlMask'];
        return preg_replace_callback(
            self::$patternMap['sqlGroups'],
            function ($matches) use ($mask, $quote, $forbiddenWords) {
                if (!empty($matches) && !in_array($matches[1], $forbiddenWords)) {
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
     * @param array $forbiddenWords An array of words that should not be processed or enclosed.
     * @param string $quote The quote character used for enclosing words.
     * @param bool &$insideFunction A flag indicating if the word is inside a function.
     * @param bool &$insideSingleQuote A flag indicating if the word is inside single quotes.
     * @param bool &$insideDoubleQuote A flag indicating if the word is inside double quotes.
     * @return string The processed word, either as is or enclosed with the quote character.
     */
    private static function processWord(
        string $word,
        array $forbiddenWords,
        string $quote,
        bool &$insideFunction,
        bool &$insideSingleQuote,
        bool &$insideDoubleQuote
    ): string {
        $object = new \stdClass();

        $result = match (true) {
            $insideFunction && strpos($word, ')') !== false => self::processCondition($object, $word, false),
            !$insideFunction && strpos($word, '(') !== false => self::processCondition($object, $word, true),
            $insideSingleQuote && strpos($word, "'") !== false => self::processCondition($object, $word, false),
            !$insideSingleQuote && strpos($word, "'") !== false => self::processCondition($object, $word, true),
            $insideDoubleQuote && strpos($word, '"') !== false => self::processCondition($object, $word, false),
            !$insideDoubleQuote && strpos($word, '"') !== false => self::processCondition($object, $word, true),
            strpos($word, ':') !== false => $word,
            in_array($word, $forbiddenWords) || $insideFunction || $insideSingleQuote || $insideDoubleQuote => $word,
            default => self::encloseWord($word, $quote),
        };

        if (is_bool($result)) {
            $insideFunction = $result;
        } else {
            $object->processedWord = $result;
        }

        return $object->processedWord;
    }

    private static function processCondition(\stdClass $object, string $processedWord, bool $processedCondition)
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
        return (substr($word, -1) == ',') ? $quote . substr($word, 0, -1) . $quote . ',' : $quote . $word . $quote;
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
        return array_combine($matches[1], (array) $values);
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
