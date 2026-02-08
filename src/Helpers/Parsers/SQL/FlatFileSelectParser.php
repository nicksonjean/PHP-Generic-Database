<?php

declare(strict_types=1);

namespace GenericDatabase\Helpers\Parsers\SQL;

/**
 * Parser helpers for FlatFiles: split SELECT by UNION/UNION ALL and extract EXISTS from WHERE.
 * Used by FetchHandler to execute raw SQL and QueryBuilder-generated SQL with subqueries.
 *
 * @package GenericDatabase\Helpers\Parsers\SQL
 */
final class FlatFileSelectParser
{
    /**
     * Split a compound SELECT (with UNION / UNION ALL) into segments and return trailing ORDER BY/LIMIT.
     * Only splits at top level (depth 0), respecting parentheses and string literals.
     *
     * @param string $sql Full SQL e.g. "SELECT a FROM t1 UNION SELECT a FROM t2 ORDER BY a LIMIT 10"
     * @return array{segments: list<string>, types: list<string>, order_by_limit_suffix: string}
     *   types[i] = 'union'|'union_all' between segments[i] and segments[i+1]; order_by_limit_suffix is the trailing part to re-apply.
     */
    public static function splitUnionSegments(string $sql): array
    {
        $sql = trim($sql);
        $segments = [];
        $types = [];

        $len = strlen($sql);
        if ($len === 0 || !preg_match('/^\s*SELECT\s+/i', $sql)) {
            return ['segments' => [$sql], 'types' => [], 'order_by_limit_suffix' => ''];
        }

        $depth = 0;
        $inSingle = false;
        $inDouble = false;
        $escape = false;
        $i = 0;
        $start = 0;
        $orderByLimitSuffix = '';

        while ($i < $len) {
            $c = $sql[$i];

            if ($escape) {
                $escape = false;
                $i++;
                continue;
            }

            if ($c === '\\' && ($inSingle || $inDouble)) {
                $escape = true;
                $i++;
                continue;
            }

            if (!$inSingle && !$inDouble) {
                if ($c === "'") {
                    $inSingle = true;
                    $i++;
                    continue;
                }
                if ($c === '"') {
                    $inDouble = true;
                    $i++;
                    continue;
                }
                if ($c === '(') {
                    $depth++;
                    $i++;
                    continue;
                }
                if ($c === ')') {
                    $depth--;
                    $i++;
                    continue;
                }

                if ($depth === 0) {
                    $rest = substr($sql, $i);
                    $restTrim = ltrim($rest);
                    $skip = strlen($rest) - strlen($restTrim);
                    $i += $skip;

                    if (preg_match('/^\s*ORDER\s+BY\s+/i', $restTrim)) {
                        $orderByLimitSuffix = substr($sql, $i);
                        $segments[] = trim(substr($sql, $start, $i - $start));
                        break;
                    }
                    if (preg_match('/^\s*LIMIT\s+/i', $restTrim)) {
                        $orderByLimitSuffix = substr($sql, $i);
                        $segments[] = trim(substr($sql, $start, $i - $start));
                        break;
                    }

                    if (preg_match('/^\s*UNION\s+ALL\s+/i', $restTrim, $um)) {
                        $segments[] = trim(substr($sql, $start, $i - $start));
                        $types[] = 'union_all';
                        $i += $skip + strlen($um[0]);
                        $start = $i;
                        continue;
                    }
                    if (preg_match('/^\s*UNION\s+(?!ALL\s)/i', $restTrim, $um)) {
                        $segments[] = trim(substr($sql, $start, $i - $start));
                        $types[] = 'union';
                        $i += $skip + strlen($um[0]);
                        $start = $i;
                        continue;
                    }
                }
            } else {
                if ($inSingle && $c === "'" && ($i === 0 || $sql[$i - 1] !== '\\')) {
                    $inSingle = false;
                }
                if ($inDouble && $c === '"' && ($i === 0 || $sql[$i - 1] !== '\\')) {
                    $inDouble = false;
                }
            }

            $i++;
        }

        if ($start < $len && $orderByLimitSuffix === '') {
            $segments[] = trim(substr($sql, $start));
        }

        $segments = array_values(array_filter($segments, fn(string $s) => $s !== ''));

        if (count($segments) === 0) {
            return ['segments' => [$sql], 'types' => [], 'order_by_limit_suffix' => ''];
        }

        return [
            'segments' => $segments,
            'types' => $types,
            'order_by_limit_suffix' => trim($orderByLimitSuffix),
        ];
    }

    /**
     * Extract EXISTS (subquery) and NOT EXISTS (subquery) from a WHERE clause string.
     * Returns list of {negate: bool, subquery: string}.
     *
     * @param string $whereClause Raw WHERE clause (e.g. "e.id = 1 AND EXISTS (SELECT 1 FROM c WHERE c.eid = e.id)")
     * @return list<array{negate: bool, subquery: string}>
     */
    public static function extractExistsConditions(string $whereClause): array
    {
        $result = [];
        $whereClause = trim($whereClause);
        if ($whereClause === '') {
            return $result;
        }

        $pos = 0;
        $len = strlen($whereClause);
        while ($pos < $len) {
            if (preg_match('/\b(NOT\s+)?EXISTS\s*\(\s*/i', substr($whereClause, $pos), $m)) {
                $negate = isset($m[1]) && trim($m[1]) === 'NOT';
                $pos += strlen($m[0]);
                $open = $pos - 1;
                $depth = 1;
                $start = $pos;
                while ($pos < $len && $depth > 0) {
                    $c = $whereClause[$pos];
                    if ($c === "'" || $c === '"') {
                        $quote = $c;
                        $pos++;
                        while ($pos < $len) {
                            if ($whereClause[$pos] === '\\') {
                                $pos += 2;
                                continue;
                            }
                            if ($whereClause[$pos] === $quote) {
                                $pos++;
                                break;
                            }
                            $pos++;
                        }
                        continue;
                    }
                    if ($c === '(') {
                        $depth++;
                    } elseif ($c === ')') {
                        $depth--;
                        if ($depth === 0) {
                            $subquery = trim(substr($whereClause, $start, $pos - $start));
                            if ($subquery !== '') {
                                $result[] = ['negate' => $negate, 'subquery' => $subquery];
                            }
                            $pos++;
                            break;
                        }
                    }
                    $pos++;
                }
                continue;
            }
            $pos++;
        }

        return $result;
    }

    /**
     * From a string starting with '(', return the content up to the matching ')' (balanced).
     *
     * @param string $s String starting with '('
     * @return string|null Inner content (without outer parens) or null
     */
    public static function extractBalancedParentheses(string $s): ?string
    {
        $s = ltrim($s);
        if ($s === '' || $s[0] !== '(') {
            return null;
        }
        $depth = 0;
        $len = strlen($s);
        $inSingle = false;
        $inDouble = false;
        for ($i = 0; $i < $len; $i++) {
            $c = $s[$i];
            if ($inSingle) {
                if ($c === "'" && ($i === 0 || $s[$i - 1] !== '\\')) {
                    $inSingle = false;
                }
                continue;
            }
            if ($inDouble) {
                if ($c === '"' && ($i === 0 || $s[$i - 1] !== '\\')) {
                    $inDouble = false;
                }
                continue;
            }
            if ($c === "'") {
                $inSingle = true;
                continue;
            }
            if ($c === '"') {
                $inDouble = true;
                continue;
            }
            if ($c === '(') {
                $depth++;
                if ($depth === 1) {
                    $start = $i + 1;
                }
                continue;
            }
            if ($c === ')') {
                $depth--;
                if ($depth === 0) {
                    return substr($s, $start, $i - $start);
                }
            }
        }
        return null;
    }

    /**
     * Remove EXISTS (subquery) and NOT EXISTS (subquery) from WHERE clause and replace with 1=1 so rest can be parsed.
     *
     * @param string $whereClause Full WHERE clause
     * @return string WHERE with EXISTS parts replaced by "1=1"
     */
    public static function stripExistsFromWhere(string $whereClause): string
    {
        $whereClause = trim($whereClause);
        if ($whereClause === '') {
            return $whereClause;
        }
        $len = strlen($whereClause);
        $i = 0;
        $result = '';
        while ($i < $len) {
            if (preg_match('/\b(NOT\s+)?EXISTS\s*\(\s*/i', substr($whereClause, $i), $m)) {
                $result .= '1=1';
                $i += strlen($m[0]);
                $depth = 1;
                while ($i < $len && $depth > 0) {
                    $c = $whereClause[$i];
                    if ($c === "'" || $c === '"') {
                        $quote = $c;
                        $i++;
                        while ($i < $len) {
                            if ($whereClause[$i] === '\\') {
                                $i += 2;
                                continue;
                            }
                            if ($whereClause[$i] === $quote) {
                                $i++;
                                break;
                            }
                            $i++;
                        }
                        continue;
                    }
                    if ($c === '(') {
                        $depth++;
                    } elseif ($c === ')') {
                        $depth--;
                    }
                    $i++;
                }
                continue;
            }
            $result .= $whereClause[$i];
            $i++;
        }
        $result = preg_replace('/\s*AND\s*1=1\s*|\s*1=1\s*AND\s*/i', ' ', $result);
        $result = preg_replace('/\s*OR\s*1=1\s*|\s*1=1\s*OR\s*/i', ' ', $result);
        return trim(preg_replace('/\s+/', ' ', $result));
    }
}
