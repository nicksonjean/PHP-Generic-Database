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
        'sqlGroups' => '/(\w+)?\((.+)\)\s/m',
        'sqlGroupsNonGreedy' => '/(\w+)?\((.+?)\)\s/m'
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
     * Analyze raw query and return all parameters in sequential order.
     * Extracts:
     * - Named parameters (:name) and positional placeholders (?)
     * - Literal values: numbers, single-quoted strings; double-quoted when dialect is BACKTICK.
     *
     * For SQL_DIALECT_DOUBLE_QUOTE (PostgreSQL, SQLite): double quotes = identifiers, skip them.
     * For SQL_DIALECT_BACKTICK (MySQL): double quotes = string literals, extract them.
     *
     * @param string $query The raw SQL query to analyze.
     * @param int|null $dialect SQL dialect for quote interpretation. Default: DOUBLE_QUOTE.
     * @return array Parameters in positional order of appearance.
     */
    public static function parseParameters(string $query, ?int $dialect = null): array
    {
        $params = self::arguments($query, null);
        $literals = self::extractLiteralValues($query, $dialect ?? self::SQL_DIALECT_DOUBLE_QUOTE);

        return empty($literals) ? $params : array_merge($params, $literals);
    }

    /**
     * Extract all literal values (numbers and quoted strings) from the query in order of appearance.
     *
     * @param string $query The raw SQL query.
     * @param int $dialect SQL dialect to interpret double-quoted strings.
     * @return array Values in sequential, positional order.
     */
    private static function extractLiteralValues(string $query, int $dialect = self::SQL_DIALECT_DOUBLE_QUOTE): array
    {
        $found = [];

        if (preg_match_all("/'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'/s", $query, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $found[] = ['pos' => $match[1], 'value' => $match[0]];
            }
        }

        if ($dialect === self::SQL_DIALECT_BACKTICK &&
            preg_match_all('/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/s', $query, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $found[] = ['pos' => $match[1], 'value' => $match[0]];
            }
        }

        $masked = preg_replace("/'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'/s", "''", $query);
        $masked = $masked !== null ? preg_replace('/"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"/s', '""', $masked) : '';
        if ($masked !== null && preg_match_all('/\b(\d+(?:\.\d+)?)\b/', $masked, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $num = $match[0];
                $found[] = [
                    'pos' => $match[1],
                    'value' => str_contains($num, '.') ? (float) $num : (int) $num
                ];
            }
        }

        usort($found, fn($a, $b) => $a['pos'] <=> $b['pos']);
        return array_map(fn($item) => $item['value'], $found);
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
        $input = self::quoteSubqueryAliasAndOrderBy($input, $quote);
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
     * Adds quotes to subquery alias and ORDER BY column.
     *
     * @param string $input The input string to be processed.
     * @param string $quote The quote character to be used for enclosing words.
     * @return string The input string with quotes added to subquery alias and ORDER BY column.
     */
    private static function quoteSubqueryAliasAndOrderBy(string $input, string $quote): string
    {
        if ($quote === '') {
            return $input;
        }
        $ident = '[a-zA-Z_][a-zA-Z0-9_]*';
        $input = preg_replace('/\bAS\s+(' . $ident . ')(?=\s|,|$)/i', 'AS ' . $quote . '${1}' . $quote, $input);
        $input = preg_replace(
            '/\)\s+(' . $ident . ')\s+(?=ORDER|FROM|WHERE|GROUP|HAVING|LIMIT|FETCH|OFFSET|UNION|$)/i',
            ') ' . $quote . '${1}' . $quote . ' ',
            $input
        );
        $input = preg_replace_callback(
            '/\b(ORDER|GROUP)\s+BY\s+(' . $ident . '(?:\.' . $ident . ')?)(?=\s|$)/i',
            static function (array $m) use ($quote): string {
                $expr = $m[2];
                if (str_contains($expr, '.')) {
                    $expr = self::applyQuotes($expr, $quote);
                } elseif ($quote !== '') {
                    $expr = $quote . $expr . $quote;
                }
                return $m[1] . ' BY ' . $expr;
            },
            $input
        );
        return $input;
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
        $result = '';
        $offset = 0;
        $pattern = self::$patternMap['sqlGroupsNonGreedy'];

        while ($offset < strlen($input) && preg_match($pattern, $input, $matches, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $fullMatch = $matches[0][0];
            $fullStart = (int) $matches[0][1];
            $prefix = trim($matches[1][0] ?? '');
            $content = trim($matches[2][0] ?? '');
            $isSubquerySelect = preg_match('/^\s*SELECT\b/i', $content);
            $isExistsLike = strtoupper($prefix) === 'EXISTS' || (strtoupper($prefix) === 'NOT' && preg_match('/^\s*EXISTS\s/i', $content));
            if ($quote !== '' && $isExistsLike && $isSubquerySelect && substr_count($content, '(') !== substr_count($content, ')')) {
                $prefixLen = strlen($matches[1][0] ?? '');
                $openParenPos = $fullStart + $prefixLen + 1;
                $contentStart = $openParenPos + 1;
                $depth = 1;
                $p = $contentStart;
                while ($p < strlen($input) && $depth > 0) {
                    $ch = $input[$p];
                    if ($ch === '(') {
                        $depth++;
                    } elseif ($ch === ')') {
                        $depth--;
                        if ($depth === 0) {
                            $content = trim(substr($input, $contentStart, $p - $contentStart));
                            $fullMatch = substr($input, $fullStart, $p - $fullStart + 1) . ' ';
                            break;
                        }
                    }
                    $p++;
                }
            }
            $replacement = self::replaceParametersProcessMatch($prefix, $content, $fullMatch, $quote);
            $result .= substr($input, $offset, $fullStart - $offset) . $replacement;
            $offset = $fullStart + strlen($fullMatch);
        }
        $result .= substr($input, $offset);
        return $result;
    }

    /**
     * Processa um único match (...) de replaceParameters.
     *
     * @param string $prefix O prefixo do match.
     * @param string $content O conteúdo do match.
     * @param string $fullMatch O match completo.
     * @param string $quote O quote character to be used for enclosing words.
     * @return string O match processado.
     */
    private static function replaceParametersProcessMatch(string $prefix, string $content, string $fullMatch, string $quote): string
    {
        $isSubquerySelect = preg_match('/^\s*SELECT\b/i', $content);
        $isExistsSubquery = $isSubquerySelect && strtoupper($prefix) === 'EXISTS';
        $isNotExistsSubquery = strtoupper($prefix) === 'NOT' && preg_match('/^\s*EXISTS\s/i', $content);
        $isAnonymousSubquery = $isSubquerySelect && $prefix === '';
        if ($quote !== '' && ($isExistsSubquery || $isNotExistsSubquery || $isAnonymousSubquery)) {
            return $prefix . ($prefix !== '' ? ' ' : '') . '(' . self::escapeType($content, $quote) . ') ';
        }
        if ($content === '?' || preg_match('/^:\w+$/', $content)) {
            return $fullMatch;
        }
        $pWords = array_map(function ($word) use ($quote): string {
            $w = trim($word);
            if (preg_match('/^-?\d+(\.\d+)?\s*$/D', $w)) {
                return $w;
            }
            if ($quote !== '' && str_contains($w, '.')) {
                $parts = explode('.', $w);
                return implode('.', array_map(fn(string $p): string => $quote . trim($p) . $quote, $parts));
            }
            return $quote !== '' ? $quote . $w . $quote : $w;
        }, explode(',', $content));
        $quotedContent = implode(', ', $pWords);
        $inner = '(' . $quotedContent . ') ';
        return $prefix !== '' ? $prefix . $inner : $inner;
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
        $original = $input;
        $suffix = '';
        while ($input !== '' && str_contains('),', substr($input, -1))) {
            $suffix = substr($input, -1) . $suffix;
            $input = substr($input, 0, -1);
        }
        $parts = explode('.', $input);
        if (count($parts) !== 2) {
            return $original;
        }
        $alreadyQuoted = static function (string $p, string $q) {
            if ($q === '' || $p === '') {
                return false;
            }
            $trimmed = trim($p);
            return str_starts_with($trimmed, $q) && str_ends_with($trimmed, $q) && strlen($trimmed) >= 2;
        };
        if ($alreadyQuoted($parts[0], $quote) && $alreadyQuoted($parts[1], $quote)) {
            return $original;
        }
        $left = trim($parts[0]);
        $right = trim($parts[1]);
        return "$quote$left$quote.$quote$right$quote" . $suffix;
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
        $content = str_ends_with($input, ',') ? substr($input, 0, -1) : $input;
        if ($quote !== '' && $content !== '' && str_starts_with($content, $quote) && str_ends_with($content, $quote) && strlen($content) >= 2) {
            return $input;
        }
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
        if ($quote === '') {
            return $input;
        }
        if (preg_match(self::$patternFunction, $input, $matches)) {
            $matches = Arrays::arraySafe($matches);
            $table = trim($matches['table'] ?? '');
            $column = trim($matches['column'] ?? '');
            $tableQuoted = $table !== '' && str_starts_with($table, $quote) && str_ends_with($table, $quote);
            $columnQuoted = $column !== '' && str_starts_with($column, $quote) && str_ends_with($column, $quote);
            if ($tableQuoted && $columnQuoted) {
                return $input;
            }
            if ($columnQuoted && $table === '') {
                return $input;
            }
            $result = (isset($matches['table']) && $matches['table'] !== '')
                ? preg_replace(self::$patternFunction, "$1($quote$2$quote.$quote$3$quote)", $input)
                : preg_replace(self::$patternFunction, "$1($quote$3$quote)", $input);
            return $result;
        }
        return $input;
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
        if ($inFunction) {
            $result = (str_contains($word, "'") || str_contains($word, '"'))
                ? self::processCondition(
                    $object,
                    $word,
                    (substr_count($word, "'") % 2) === 1 || (substr_count($word, '"') % 2) === 1
                )
                : $word;
        } else {
            $trailing = (str_ends_with($word, ',')) ? ',' : '';
            $w = $trailing !== '' ? substr($word, 0, -1) : $word;
            $w = trim($w);
            $stripped = $quote !== '' ? trim($w, $quote) : $w;
            if ($stripped !== '' && in_array(mb_strtoupper($stripped), $resWords)) {
                $result = mb_strtoupper($stripped) . $trailing;
            } else {
                $result = match (true) {
                    self::isFunction($word) => self::applyQuotesFunction($word, $quote),
                    str_contains($word, '*') => self::applyWildCard($word, $quote),
                    str_contains($word, '.') => self::applyQuotes($word, $quote),
                    str_contains($word, ']') => self::processCondition($object, $word, false),
                    str_contains($word, '[') => self::processCondition($object, $word, true),
                    $inFunction && str_contains($word, ')') => self::processCondition($object, $word, false),
                    !$inFunction && str_contains($word, '(') => self::processCondition($object, $word, true),
                    str_contains($word, "'") => self::processCondition(
                        $object,
                        $word,
                        (substr_count($word, "'") % 2) === 1
                    ),
                    str_contains($word, '"') => self::processCondition(
                        $object,
                        $word,
                        (substr_count($word, '"') % 2) === 1
                    ),
                    str_contains($word, ':') => $word,
                    in_array(mb_strtoupper($word), $resWords) => mb_strtoupper($word) . $trailing,
                    preg_match('/^[=<>!]=?$|^<>$/', trim($word)) => $word,
                    is_numeric($word) || preg_match('/\d+/im', $word) => $word,
                    str_contains($word, $bindQm) => str_replace($quote . $bindQm . $quote, $bindQm, $word),
                    default => self::encloseWord($word, $quote),
                };
            }
        }

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
        if ($dialect === self::SQL_DIALECT_DOUBLE_QUOTE) {
            $input = self::normalizeStringLiteralsToSingleQuotes($input);
        }
        $quote = self::$quoteMap[$dialect] ?? '';
        return self::escapeType($input, $quote);
    }

    /**
     * Converts double-quoted string literals to single-quoted for dialects that use
     * double quotes for identifiers (PostgreSQL, SQLite, Firebird, etc.).
     *
     * @param string $input The SQL string.
     * @return string The string with literals normalized.
     */
    private static function normalizeStringLiteralsToSingleQuotes(string $input): string
    {
        $pattern = '/(=\s*|(?:NOT\s+)?(?:I?LIKE)\s+|!=\s*|<>?\s*|>\s*|<\s*|>=\s*|<=\s*|,\s*)\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/i';
        return preg_replace_callback($pattern, static function (array $m): string {
            $content = $m[2];
            if (preg_match('/^\w+$/D', $content)) {
                return $m[0];
            }
            $value = str_replace("'", "''", $content);
            return $m[1] . "'" . $value . "'";
        }, $input);
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
