<?php

namespace GenericDatabase\Engine\PgSQL\Connection;

use GenericDatabase\Engine\PgSQLConnection;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Translate;
use stdClass;
use \PgSql\Result;

class Statements
{
    /**
     * Instance of the Statement of the database
     * @var mixed $statement = null
     */
    private static mixed $statement = null;

    /**
     * Count rows in query statement
     * @var ?int $queryRows = 0
     */
    private static ?int $queryRows = 0;

    /**
     * Count columns in query statement
     * @var ?int $queryColumns = 0
     */
    private static ?int $queryColumns = 0;

    /**
     * Affected row in query statement
     * @var ?int $affectedRows = 0
     */
    private static ?int $affectedRows = 0;

    /**
     * Lasts params query executed
     * @var ?array $queryParameters = []
     */
    private static ?array $queryParameters = [];

    /**
     * Last string query executed
     * @var string $queryString = ''
     */
    private static string $queryString = '';

    /**
     * Reset query metadata
     *
     * @return void
     */
    public static function setAllMetadata(): void
    {
        self::$queryString = '';
        self::$queryParameters = [];
        self::$queryRows = 0;
        self::$queryColumns = 0;
        self::$affectedRows = 0;
    }

    /**
     * Returns an object containing the number of queried rows and the number of affected rows.
     *
     * @return object An associative object with keys 'queryRows' and 'affectedRows'.
     */
    public static function getAllMetadata(): object
    {
        $metadata = new stdClass();
        $metadata->queryString = self::getQueryString();
        $metadata->queryParameters = self::getQueryParameters();
        $metadata->queryRows = self::getQueryRows();
        $metadata->queryColumns = self::getQueryColumns();
        $metadata->affectedRows = self::getAffectedRows();
        return $metadata;
    }

    /**
     * Returns the query string.
     *
     * @return string The query string associated with this instance.
     */
    public static function getQueryString(): string
    {
        return self::$queryString;
    }

    /**
     * Sets the query string for the Connection instance.
     *
     * @param string $params The query string to set.
     */
    public static function setQueryString(string $params): void
    {
        self::$queryString = $params;
    }

    /**
     * Returns the parameters associated with this instance.
     *
     * @return array|null The parameters associated with this instance.
     */
    public static function getQueryParameters(): ?array
    {
        return self::$queryParameters;
    }

    /**
     * Sets the query parameters for the Connection instance.
     *
     * @param array|null $params The query parameters to set.
     */
    public static function setQueryParameters(?array $params): void
    {
        self::$queryParameters = $params;
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public static function getQueryRows(): int|false
    {
        return self::$queryRows;
    }

    /**
     * Sets the number of query rows for the Connection instance.
     *
     * @param callable|int|false $params The number of query rows to set.
     * @return void
     */
    public static function setQueryRows(callable|int|false $params): void
    {
        self::$queryRows = $params;
    }

    /**
     * Returns the number of columns in a statement result.
     *
     * @return int|false The number of columns in the result or false in case of an error.
     */
    public static function getQueryColumns(): int|false
    {
        return self::$queryColumns;
    }

    /**
     * Sets the number of columns in the query result.
     *
     * @param int|false $params The number of columns or false if there are no columns.
     * @return void
     */
    public static function setQueryColumns(int|false $params): void
    {
        self::$queryColumns = $params;
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public static function getAffectedRows(): int|false
    {
        return self::$affectedRows;
    }

    /**
     * Sets the number of affected rows for the Connection instance.
     *
     * @param int|false $params The number of affected rows to set.
     * @return void
     */
    public static function setAffectedRows(int|false $params): void
    {
        self::$affectedRows = (int) $params;
    }

    /**
     * Returns the statement for the function.
     *
     * @return mixed
     */
    public static function getStatement(): mixed
    {
        return self::$statement;
    }

    /**
     * Sets the statement for the function.
     *
     * @param mixed $statement The statement to be set.
     */
    public static function setStatement(mixed $statement): void
    {
        self::$statement = $statement;
    }

    /**
     * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
     *
     * @param mixed $params Content to be quoted
     * @return mixed
     */
    public static function quote(mixed ...$params): mixed
    {
        $string = $params[0];
        $quote = $params[1] ?? false;
        if (is_array($string)) {
            return array_map(fn($str) => self::quote($str, $quote), $string);
        } elseif ($string && preg_match("/^(?:\d+\.\d+|[1-9]\d*)$/S", (string) $string)) {
            return $string;
        }
        $quoted = fn($str) => pg_escape_string(PgSQLConnection::getInstance()->getConnection(), (string) $str);
        return $quote ? "'" . $quoted($string) . "'" : $quoted($string);
    }

    /**
     * This function returns the last ID generated by an auto-increment column,
     * either the last one inserted during the current transaction, or by passing in the optional name parameter.
     *
     * @param ?string $name = null Resource name, table or view
     * @return string|int|false
     */
    public static function lastInsertId(?string $name = null): string|int|false
    {
        $statement = self::getStatement();
        if ($statement instanceof Result) {
            return pg_last_oid($statement);
        }
        if ($name !== null) {
            $filter = "WHERE table_name = $1 AND column_default LIKE 'nextval%'";
            $query = sprintf("SELECT column_name, column_default FROM information_schema.columns %s", $filter);
            $result = pg_query_params(PgSQLConnection::getInstance()->getConnection(), $query, [$name]);
            if ($result && ($row = pg_fetch_assoc($result))) {
                $seqName = preg_replace("/nextval\('(.+)'::regclass\)/", "$1", $row['column_default']);
                $result = pg_query(PgSQLConnection::getInstance()->getConnection(), "SELECT currval('$seqName')");
                if ($result) {
                    $row = pg_fetch_row($result);
                    return $row ? (int) $row[0] : false;
                }
            }
        }
        $result = pg_query(PgSQLConnection::getInstance()->getConnection(), "SELECT lastval()");
        if ($result) {
            $row = pg_fetch_row($result);
            return $row ? (int) $row[0] : false;
        }
        return false;
    }

    /**
     * Binds an array multiple parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private static function internalBindParamArrayMulti(mixed ...$params): void
    {
        foreach (Arrays::arrayValuesRecursive($params['sqlArgs']) as $param) {
            self::exec($params['sqlStatement'], $param);
            self::setAffectedRows(pg_affected_rows(self::getStatement()));
        }
    }

    /**
     * Binds an array single parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private static function internalBindParamArraySingle(mixed ...$params): void
    {
        self::exec($params['sqlStatement'], array_values($params['sqlArgs']));
        self::setAffectedRows(pg_affected_rows(self::getStatement()));
    }

    /**
     * Binds an array parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private static function internalBindParamArray(mixed ...$params): void
    {
        if ($params['isMulti']) {
            self::internalBindParamArrayMulti(...$params);
        } else {
            self::internalBindParamArraySingle(...$params);
        }
    }
    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an args of parameters and values.
     * @return void
     */
    private static function internalBindParamArgs(mixed ...$params): void
    {
        self::exec($params['sqlStatement'], $params['sqlArgs']);
        self::setAffectedRows(pg_affected_rows(self::getStatement()));
    }
    /**
     * This function makes an arguments list
     *
     * @param mixed $params Arguments list
     * @param mixed $driver Driver name
     * @return array
     */
    private static function makeArgs(mixed $driver, mixed ...$params): array
    {
        return Arrays::makeArgs($driver, ...$params);
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    public static function bindParam(mixed ...$params): void
    {
        self::setQueryParameters($params['sqlArgs']);
        if ($params['isArray']) {
            self::internalBindParamArray(...$params);
        } else {
            self::internalBindParamArgs(...$params);
        }
    }

    /**
     * Parses an SQL statement and returns an statement.
     *
     * @param mixed ...$params The parameters for the query function.
     * @return string The statement resulting from the SQL statement.
     */
    public static function parse(mixed ...$params): string
    {
        $queryString = Translate::binding(
            Translate::escape(reset($params), Translate::SQL_DIALECT_DOUBLE_QUOTE),
            Translate::BIND_DOLLAR_SIGN
        );
        self::setQueryString($queryString);
        return self::getQueryString();
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return PgSQLConnection|null
     */
    public static function query(mixed ...$params): ?PgSQLConnection
    {
        self::setAllMetadata();
        if (!empty($params)) {
            $query = self::parse(...$params);
            $result = pg_query(PgSQLConnection::getInstance()->getConnection(), $query);
            if ($result) {
                $numFields = pg_num_fields($result);
                if ($numFields > 0) {
                    $results = [];
                    while ($row = pg_fetch_array($result, null, PGSQL_BOTH)) {
                        $results[] = $row;
                    }
                    self::setStatement(['results' => $results]);
                    self::setQueryRows(pg_num_rows($result));
                    self::setQueryColumns($numFields);
                    self::setAffectedRows(0);
                } else {
                    $affectedRows = pg_affected_rows($result);
                    self::setStatement(['results' => []]);
                    self::setAffectedRows($affectedRows);
                    self::setQueryRows(0);
                    self::setQueryColumns(0);
                }
                pg_free_result($result);
            }
        }
        return PgSQLConnection::getInstance();
    }
    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return PgSQLConnection|null
     */
    public static function prepare(mixed ...$params): ?PgSQLConnection
    {
        $driver = PgSQLConnection::getInstance()->getDriver();
        self::setAllMetadata();
        if (!empty($params)) {
            $query = self::parse(...$params);
            if (isset($params[1])) {
                $results = [];
                $affectedRows = 0;
                $numFields = 0;
                $paramSets = is_array($params[1][0] ?? null) ? $params[1] : [$params[1]];
                $bindParams = self::makeArgs($driver, ...$params);
                self::setQueryParameters($bindParams['sqlQuery']);
                foreach ($paramSets as $bindParams) {
                    $orderedParams = array_values($bindParams);
                    $result = pg_query_params(PgSQLConnection::getInstance()->getConnection(), $query, $orderedParams);
                    if ($result) {
                        $numFields = pg_num_fields($result);
                        if ($numFields > 0) {
                            while ($row = pg_fetch_array($result, null, PGSQL_ASSOC)) {
                                $results[] = $row;
                            }
                        } else {
                            $affectedRows += pg_affected_rows($result);
                        }
                        pg_free_result($result);
                    }
                }
                if ($numFields > 0) {
                    self::setStatement(['results' => $results]);
                    self::setQueryRows(count($results));
                    self::setQueryColumns($numFields);
                    self::setAffectedRows(0);
                } else {
                    self::setStatement(['results' => []]);
                    self::setAffectedRows($affectedRows);
                    self::setQueryRows(0);
                    self::setQueryColumns(0);
                }
            }
        }
        return PgSQLConnection::getInstance();
    }
    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return Result|bool
     */
    public static function exec(mixed ...$params): Result|bool
    {
        if (!empty($params)) {
            $stmtName = 'stmt_' . md5(serialize($params));
            $query = self::parse(...$params);
            $stmt = pg_prepare(PgSQLConnection::getInstance()->getConnection(), $stmtName, $query);
            if ($stmt && isset($params[1])) {
                $orderedParams = array_values($params[1]);
                $result = pg_execute(PgSQLConnection::getInstance()->getConnection(), $stmtName, $orderedParams);
                if ($result) {
                    $results = [];
                    if (pg_num_fields($result) > 0) {
                        while ($row = pg_fetch_array($result, null, PGSQL_BOTH)) {
                            $results[] = $row;
                        }
                    }
                    self::setStatement(['results' => $results]);
                    self::setAffectedRows(pg_affected_rows($result));
                    return $result;
                }
            }
        }
        return false;
    }
}
