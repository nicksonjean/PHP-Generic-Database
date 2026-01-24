<?php

declare(strict_types=1);

namespace GenericDatabase\Helpers\Parsers;

/**
 * Robust SQL query type detector that handles complex query patterns.
 * Supports CTE queries, compound queries (INSERT...SELECT), subqueries,
 * UNION/INTERSECT/EXCEPT operations, and nested queries.
 *
 * @package GenericDatabase\Helpers\Parsers
 */
class QueryTypeDetector
{
    public const TYPE_SELECT = 'SELECT';
    public const TYPE_INSERT = 'INSERT';
    public const TYPE_UPDATE = 'UPDATE';
    public const TYPE_DELETE = 'DELETE';
    public const TYPE_UNKNOWN = 'UNKNOWN';

    /**
     * Detect the primary type of an SQL query.
     * Handles CTE queries, comments, and complex patterns.
     *
     * @param string $query The SQL query to analyze.
     * @return string The primary query type (SELECT, INSERT, UPDATE, DELETE, or UNKNOWN).
     */
    public static function detect(string $query): string
    {
        $query = self::stripComments(trim($query));

        if (empty($query)) {
            return self::TYPE_UNKNOWN;
        }

        // Handle WITH (CTE) queries - find the main operation after CTE
        if (self::startsWithCTE($query)) {
            return self::detectCTEOperation($query);
        }

        // Get the first keyword at depth 0 (outside parentheses)
        $firstKeyword = self::getFirstKeywordAtDepthZero($query);

        return match ($firstKeyword) {
            'SELECT' => self::TYPE_SELECT,
            'INSERT' => self::TYPE_INSERT,
            'UPDATE' => self::TYPE_UPDATE,
            'DELETE' => self::TYPE_DELETE,
            default => self::TYPE_UNKNOWN
        };
    }

    /**
     * Check if query returns results (SELECT-like).
     *
     * @param string $query The SQL query.
     * @return bool
     */
    public static function isSelectQuery(string $query): bool
    {
        return self::detect($query) === self::TYPE_SELECT;
    }

    /**
     * Check if query modifies data (INSERT, UPDATE, DELETE).
     *
     * @param string $query The SQL query.
     * @return bool
     */
    public static function isDmlQuery(string $query): bool
    {
        return in_array(self::detect($query), [
            self::TYPE_INSERT,
            self::TYPE_UPDATE,
            self::TYPE_DELETE
        ]);
    }

    /**
     * Perform detailed analysis of a query.
     *
     * @param string $query The SQL query to analyze.
     * @return QueryInfo Complete query information.
     */
    public static function analyze(string $query): QueryInfo
    {
        $cleanQuery = self::stripComments(trim($query));

        $primaryType = self::detect($query);
        $isCompound = self::isCompoundQuery($cleanQuery);
        $hasSubquery = self::hasSubquery($cleanQuery);
        $operations = self::getOperations($cleanQuery);
        $tables = self::extractTables($cleanQuery);

        return new QueryInfo(
            primaryType: $primaryType,
            isCompound: $isCompound,
            hasSubquery: $hasSubquery,
            operations: $operations,
            tables: $tables
        );
    }

    /**
     * Check if the query contains subqueries.
     *
     * @param string $query The SQL query.
     * @return bool
     */
    public static function hasSubquery(string $query): bool
    {
        $cleanQuery = self::stripComments(trim($query));

        // Look for SELECT inside parentheses
        if (preg_match('/\(\s*SELECT\b/i', $cleanQuery)) {
            return true;
        }

        // Check for EXISTS with SELECT
        if (preg_match('/\bEXISTS\s*\(\s*SELECT\b/i', $cleanQuery)) {
            return true;
        }

        // Check for IN with SELECT
        if (preg_match('/\bIN\s*\(\s*SELECT\b/i', $cleanQuery)) {
            return true;
        }

        return false;
    }

    /**
     * Get all SQL operations found in the query.
     *
     * @param string $query The SQL query.
     * @return array List of operations in order of appearance.
     */
    public static function getOperations(string $query): array
    {
        $cleanQuery = self::stripComments(trim($query));
        $operations = [];

        // Track keywords at all levels
        $keywords = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'UNION', 'INTERSECT', 'EXCEPT', 'WITH'];

        foreach ($keywords as $keyword) {
            if (preg_match_all('/\b' . $keyword . '\b/i', $cleanQuery, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $operations[] = [
                        'type' => strtoupper($match[0]),
                        'position' => $match[1]
                    ];
                }
            }
        }

        // Sort by position
        usort($operations, fn($a, $b) => $a['position'] <=> $b['position']);

        // Return just the types
        return array_map(fn($op) => $op['type'], $operations);
    }

    /**
     * Strip SQL comments from query (single-line and multi-line).
     *
     * @param string $query The SQL query.
     * @return string Query without comments.
     */
    private static function stripComments(string $query): string
    {
        // Remove multi-line comments /* ... */
        $query = preg_replace('/\/\*.*?\*\//s', '', $query);

        // Remove single-line comments -- ...
        $query = preg_replace('/--[^\n]*/', '', $query);

        // Remove # comments (MySQL style)
        $query = preg_replace('/#[^\n]*/', '', $query);

        return trim($query);
    }

    /**
     * Check if query starts with WITH (CTE).
     *
     * @param string $query The SQL query.
     * @return bool
     */
    private static function startsWithCTE(string $query): bool
    {
        return preg_match('/^\s*WITH\b/i', $query) === 1;
    }

    /**
     * Detect the main operation in a CTE query.
     *
     * @param string $query The SQL query with CTE.
     * @return string The primary operation type.
     */
    private static function detectCTEOperation(string $query): string
    {
        // Find the position after all CTE definitions
        // CTEs are in format: WITH name AS (...), name2 AS (...) SELECT/INSERT/UPDATE/DELETE
        $depth = 0;
        $length = strlen($query);
        $i = 0;

        // Skip "WITH" keyword
        if (preg_match('/^\s*WITH\s+/i', $query, $match)) {
            $i = strlen($match[0]);
        }

        // Track parenthesis depth to find the end of CTE definitions
        $inCte = true;
        $lastCloseParenPos = -1;

        while ($i < $length && $inCte) {
            $char = $query[$i];

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    $lastCloseParenPos = $i;
                }
            }

            // When at depth 0 and we find a main keyword, that's our operation
            if ($depth === 0 && $lastCloseParenPos >= 0) {
                $remaining = substr($query, $i);
                if (preg_match('/^\s*(SELECT|INSERT|UPDATE|DELETE)\b/i', $remaining, $match)) {
                    return strtoupper($match[1]);
                }
            }

            $i++;
        }

        // If we get here, look for the final operation after all CTEs
        if ($lastCloseParenPos >= 0) {
            $remaining = substr($query, $lastCloseParenPos + 1);
            if (preg_match('/\s*(SELECT|INSERT|UPDATE|DELETE)\b/i', $remaining, $match)) {
                return strtoupper($match[1]);
            }
        }

        // Default to SELECT for WITH queries
        return self::TYPE_SELECT;
    }

    /**
     * Get the first SQL keyword at parenthesis depth 0.
     * Also handles queries that start with parentheses by looking inside.
     *
     * @param string $query The SQL query.
     * @return string The first keyword found.
     */
    private static function getFirstKeywordAtDepthZero(string $query): string
    {
        $keywords = ['SELECT', 'INSERT', 'UPDATE', 'DELETE'];
        $length = strlen($query);
        $depth = 0;
        $i = 0;

        // Check if the query starts with a parenthesis - in this case look for first keyword
        // at any depth, as it's likely a parenthesized SELECT in a UNION/EXCEPT/INTERSECT
        $startsWithParen = preg_match('/^\s*\(/', $query);

        while ($i < $length) {
            $char = $query[$i];

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }

            // For queries starting with paren, find first keyword at depth 1
            // For normal queries, find first keyword at depth 0
            $targetDepth = $startsWithParen ? 1 : 0;

            if ($depth === $targetDepth || ($startsWithParen && $depth >= 1)) {
                // Check if we're at a keyword
                foreach ($keywords as $keyword) {
                    $keywordLen = strlen($keyword);
                    if ($i + $keywordLen <= $length) {
                        $substr = substr($query, $i, $keywordLen);
                        if (strcasecmp($substr, $keyword) === 0) {
                            // Make sure it's a word boundary
                            $before = $i > 0 ? $query[$i - 1] : ' ';
                            $after = $i + $keywordLen < $length ? $query[$i + $keywordLen] : ' ';

                            if (!ctype_alnum($before) && $before !== '_' && !ctype_alnum($after) && $after !== '_') {
                                return $keyword;
                            }
                        }
                    }
                }
            }

            $i++;
        }

        return '';
    }

    /**
     * Check if this is a compound query (e.g., INSERT...SELECT).
     *
     * @param string $query The SQL query.
     * @return bool
     */
    private static function isCompoundQuery(string $query): bool
    {
        // Check for INSERT...SELECT pattern
        if (preg_match('/\bINSERT\b.*\bSELECT\b/is', $query)) {
            // Make sure SELECT is not just in a subquery in VALUES
            $insertPos = stripos($query, 'INSERT');
            $valuesPos = stripos($query, 'VALUES');
            $selectPos = stripos($query, 'SELECT');

            // If SELECT comes after INSERT but before VALUES (or no VALUES), it's compound
            if ($selectPos !== false && $selectPos > $insertPos) {
                if ($valuesPos === false || $selectPos < $valuesPos) {
                    return true;
                }
            }
        }

        // Check for UNION/INTERSECT/EXCEPT (set operations)
        if (preg_match('/\b(UNION|INTERSECT|EXCEPT)\b/i', $query)) {
            return true;
        }

        return false;
    }

    /**
     * Extract table names from the query.
     *
     * @param string $query The SQL query.
     * @return array List of table names found.
     */
    private static function extractTables(string $query): array
    {
        $tables = [];

        // Match FROM table
        if (preg_match_all('/\bFROM\s+([`"\']?)(\w+)\1/i', $query, $matches)) {
            $tables = array_merge($tables, $matches[2]);
        }

        // Match JOIN table
        if (preg_match_all('/\bJOIN\s+([`"\']?)(\w+)\1/i', $query, $matches)) {
            $tables = array_merge($tables, $matches[2]);
        }

        // Match INSERT INTO table
        if (preg_match('/\bINSERT\s+INTO\s+([`"\']?)(\w+)\1/i', $query, $matches)) {
            $tables[] = $matches[2];
        }

        // Match UPDATE table
        if (preg_match('/\bUPDATE\s+([`"\']?)(\w+)\1/i', $query, $matches)) {
            $tables[] = $matches[2];
        }

        // Match DELETE FROM table
        if (preg_match('/\bDELETE\s+FROM\s+([`"\']?)(\w+)\1/i', $query, $matches)) {
            $tables[] = $matches[2];
        }

        return array_unique($tables);
    }
}
