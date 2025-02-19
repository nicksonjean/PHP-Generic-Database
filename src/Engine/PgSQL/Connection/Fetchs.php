<?php

namespace GenericDatabase\Engine\PgSQL\Connection;

use GenericDatabase\Helpers\Reflections;

class Fetchs
{
    private static array $positions = [];
    private static array $cachedResults = [];
    private static array $lastFetchAll = [];

    /**
     * Gets a unique identifier for the statement resource
     *
     * @param mixed $statement The statement to get ID for
     * @return string Unique identifier for the statement
     */
    private static function getResourceId(mixed $statement): string
    {
        return match (true) {
            is_resource($statement) => (string)$statement,
            is_object($statement) => spl_object_hash($statement),
            is_array($statement) => md5(serialize($statement)),
            default => 'null',
        };
    }

    /**
     * Caches the results from a statement for future use
     *
     * @param mixed $statement The statement to cache results from
     * @return string The statement identifier
     */
    private static function cacheResults(mixed $statement): string
    {
        $statementId = self::getResourceId($statement);
        if (!isset(self::$cachedResults[$statementId])) {
            self::$cachedResults[$statementId] = [];
            if ($statement instanceof \PgSql\Result) {
                while ($row = pg_fetch_array($statement, null, PGSQL_ASSOC)) {
                    $normalizedRow = [];
                    foreach ($row as $key => $value) {
                        if (is_string($key)) {
                            $normalizedRow[$key] = $value;
                        }
                    }
                    self::$cachedResults[$statementId][] = $normalizedRow;
                }
                pg_result_seek($statement, 0);
            } elseif (is_array($statement)) {
                self::$cachedResults[$statementId] = $statement['results'] ?? [];
            }
            self::$positions[$statementId] = 0;
        }
        return $statementId;
    }

    /**
     * Handles resetting the fetch position and caching results if not already cached
     *
     * @param mixed $statement The statement resource
     * @return void
     */
    private static function handleFetchReset(mixed $statement): void
    {
        if (!$statement) {
            return;
        }
        $statementId = self::getResourceId($statement);
        if (!isset(self::$cachedResults[$statementId])) {
            self::$cachedResults[$statementId] = [];
            if ($statement instanceof \PgSql\Result) {
                while ($row = pg_fetch_array($statement, null, PGSQL_ASSOC)) {
                    self::$cachedResults[$statementId][] = $row;
                }
                pg_result_seek($statement, 0);
            } elseif (is_array($statement)) {
                self::$cachedResults[$statementId] = $statement['results'] ?? [];
            }
            self::$positions[$statementId] = 0;
        }
        if (isset(self::$lastFetchAll[$statementId])) {
            self::$positions[$statementId] = 0;
            unset(self::$lastFetchAll[$statementId]);
        }
    }

    public static function internalFetchClass(
        $statement = null,
        ?array $constructorArguments = null,
        $aClassOrObject = '\stdClass'
    ) {
        $statementId = self::cacheResults($statement);
        $results = self::$cachedResults[$statementId] ?? [];

        if (isset($results[self::$positions[$statementId]])) {
            $row = $results[self::$positions[$statementId]++];
            return Reflections::createObjectAndSetPropertiesCaseInsensitive(
                $aClassOrObject,
                $constructorArguments ?? [],
                $row
            );
        }

        return false;
    }

    public static function internalFetchBoth($statement = null): bool|array
    {
        $statementId = self::cacheResults($statement);
        $results = self::$cachedResults[$statementId] ?? [];

        if (isset($results[self::$positions[$statementId]])) {
            $row = $results[self::$positions[$statementId]++];
            $result = [];
            $index = 0;
            foreach ($row as $key => $value) {
                $result[$index] = (string) $value;
                $result[$key] = (string) $value;
                $index++;
            }
            return $result;
        }

        return false;
    }

    public static function internalFetchAssoc(mixed $statement): array|null|false
    {
        $statementId = self::cacheResults($statement);
        $results = self::$cachedResults[$statementId] ?? [];

        if (isset($results[self::$positions[$statementId]])) {
            return $results[self::$positions[$statementId]++];
        }

        return false;
    }

    public static function internalFetchNum($statement = null): bool|array|null
    {
        $statementId = self::cacheResults($statement);
        $results = self::$cachedResults[$statementId] ?? [];

        if (isset($results[self::$positions[$statementId]])) {
            $row = array_values($results[self::$positions[$statementId]++]);
            return array_map('strval', $row);
        }

        return false;
    }

    public static function internalFetchColumn($statement = null, $columnIndex = 0)
    {
        $columnIndex = $columnIndex ?? 0;
        self::handleFetchReset($statement);
        $statementId = self::cacheResults($statement);
        $results = self::$cachedResults[$statementId];

        if (isset($results[self::$positions[$statementId]])) {
            $row = $results[self::$positions[$statementId]++];
            $values = array_values($row);
            return isset($values[$columnIndex]) ? (string) $values[$columnIndex] : false;
        }

        return false;
    }

    public static function internalFetchAllAssoc($statement = null): array
    {
        $statementId = self::cacheResults($statement);
        self::$lastFetchAll[$statementId] = true;
        return self::$cachedResults[$statementId];
    }

    public static function internalFetchAllNum($statement = null): array
    {
        $statementId = self::cacheResults($statement);
        self::$lastFetchAll[$statementId] = true;

        $result = [];
        foreach (self::$cachedResults[$statementId] as $row) {
            $result[] = array_map('strval', array_values($row));
        }
        return $result;
    }

    public static function internalFetchAllBoth($statement = null): array
    {
        $statementId = self::cacheResults($statement);
        self::$lastFetchAll[$statementId] = true;

        $result = [];
        foreach (self::$cachedResults[$statementId] as $row) {
            $combined = [];
            $index = 0;
            foreach ($row as $key => $value) {
                $combined[$index] = (string) $value;
                $combined[$key] = (string) $value;
                $index++;
            }
            $result[] = $combined;
        }
        return $result;
    }

    public static function internalFetchAllColumn($statement = null, $columnIndex = 0): array
    {
        $columnIndex = $columnIndex ?? 0;
        $statementId = self::cacheResults($statement);
        self::$lastFetchAll[$statementId] = true;
        $result = [];
        foreach (self::$cachedResults[$statementId] as $row) {
            $values = array_values($row);
            if (isset($values[$columnIndex])) {
                $result[] = (string) $values[$columnIndex];
            }
        }
        return $result;
    }

    public static function internalFetchAllClass(
        $statement = null,
        $constructorArguments = [],
        $aClassOrObject = '\stdClass',
    ): array {
        $statementId = self::cacheResults($statement);
        self::$lastFetchAll[$statementId] = true;

        $result = [];
        foreach (self::$cachedResults[$statementId] as $row) {
            $result[] = Reflections::createObjectAndSetPropertiesCaseInsensitive(
                $aClassOrObject,
                $constructorArguments ?? [],
                $row
            );
        }
        return $result;
    }
}
