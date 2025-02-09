<?php

namespace GenericDatabase\Engine\SQLSrv\Connection;

use GenericDatabase\Engine\SQLSrvConnection;
use GenericDatabase\Core\Schema;
use GenericDatabase\Helpers\Translate;
use GenericDatabase\Helpers\Validations;
use stdClass;

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
        $string = reset($params);
        return match (true) {
            is_int($string) => $string,
            is_float($string) => "'" . str_replace(',', '.', strval($string)) . "'",
            is_bool($string) => $string ? '1' : '0',
            is_null($string) => 'NULL',
            default => "'" . str_replace("'", "''", (string) $string) . "'",
        };
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
        if (!$name) {
            $query = "SELECT CAST(@@IDENTITY AS BIGINT) AS LastInsertedID";
            $statement = sqlsrv_query(SQLSrvConnection::getInstance()->getConnection(), $query);
            if ($statement) {
                $row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC);
                sqlsrv_free_stmt($statement);
                return $row ? (int) $row['LastInsertedID'] : 0;
            }
            return 0;
        }
        $filter = "WHERE TABLE_NAME = ? AND TABLE_SCHEMA = SCHEMA_NAME() AND COLUMNPROPERTY(OBJECT_ID(TABLE_SCHEMA + '.' + TABLE_NAME), COLUMN_NAME, 'IsIdentity') = 1";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $statement = sqlsrv_query(SQLSrvConnection::getInstance()->getConnection(), $query, [$name]);
        if ($statement) {
            $row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($statement);
            if (isset($row['COLUMN_NAME'])) {
                $identityColumn = $row['COLUMN_NAME'];
                $query = sprintf("SELECT MAX(%s) AS LastInsertedID FROM %s", $identityColumn, $name);
                $statement = sqlsrv_query(SQLSrvConnection::getInstance()->getConnection(), $query);
                if ($statement) {
                    $result = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC);
                    sqlsrv_free_stmt($statement);
                    return $result ? (int) $result['LastInsertedID'] : 0;
                }
            }
        }
        return 0;
    }

    /**
     * Binds variables to a prepared statement with specified types.
     * This method binds variables to a prepared statement based on their types,
     * allowing for more precise parameter binding.
     *
     * @param mixed $preparedParams The prepared statement to bind variables to.
     * @return mixed The prepared statement with bound variables.
     */
    private static function internalBindVariable(mixed $preparedParams): mixed
    {
        return Validations::detectTypes($preparedParams);
    }

    /**
     * Binds an array multiple parameter to a variable in the SQL statement.
     *
     * @param object $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private static function internalBindParamArrayMulti(object $params): void
    {
        $affectedRows = 0;
        foreach ($params->sqlArgs as $param) {
            self::internalBindVariable($param);
            if (self::exec($params->sqlStatement, array_values($param))) {
                if (self::getQueryColumns() === 0) {
                    $affectedRows++;
                    self::setAffectedRows((int) $affectedRows);
                }
            }
        }
    }

    /**
     * Binds an array single parameter to a variable in the SQL statement.
     *
     * @param object $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private static function internalBindParamArraySingle(object $params): void
    {
        self::internalBindParamArgs($params);
    }

    /**
     * Binds an array parameter to a variable in the SQL statement.
     *
     * @param object $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private static function internalBindParamArray(object $params): void
    {
        if ($params->isMulti) {
            self::internalBindParamArrayMulti($params);
        } else {
            self::internalBindParamArraySingle($params);
        }
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param object $params The name of the parameter or an args of parameters and values.
     * @return void
     */
    private static function internalBindParamArgs(object $params): void
    {
        self::internalBindVariable($params->sqlArgs);
        if (self::exec($params->sqlStatement)) {
            if ((int) self::getQueryColumns() > 0) {
                self::setQueryRows(
                    (function (mixed $stmt): int {
                        $results = [];
                        $rows = 0;
                        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                            $results[] = $row;
                            $rows++;
                        }
                        self::setStatement(['results' => $results]);
                        return $rows;
                    })($params->sqlStatement) ?? 0
                );
            } else {
                self::setAffectedRows((int) sqlsrv_rows_affected($params->sqlStatement));
            }
        }
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    public static function bindParam(object $params): void
    {
        self::setQueryColumns((int) is_resource($params->sqlStatement) ? sqlsrv_num_fields($params->sqlStatement) : 0);
        if ($params->isArray) {
            self::internalBindParamArray($params);
        } else {
            self::internalBindParamArgs($params);
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
        self::setQueryString(Translate::binding(Translate::escape(reset($params), Translate::SQL_DIALECT_DOUBLE_QUOTE)));
        return self::getQueryString();
    }

    /**
     * This function binds the parameters to a prepared statement.
     *
     * @param mixed ...$params
     * @return mixed
     */
    private static function prepareStatement(mixed ...$params): mixed
    {
        self::setAllMetadata();
        if (!empty($params)) {
            $bindParams = Schema::makeArgs([null, ...$params]);
            self::setQueryParameters($bindParams->sqlArgs);
            if ($bindParams->isArray) {
                if ($bindParams->isMulti) {
                    foreach ($bindParams->sqlArgs as $bindParam) {
                        $statement = sqlsrv_query(SQLSrvConnection::getInstance()->getConnection(), self::parse(...$params), array_values($bindParam), ['Scrollable' => SQLSRV_CURSOR_FORWARD]);
                    }
                } else {
                    $statement = sqlsrv_query(SQLSrvConnection::getInstance()->getConnection(), self::parse(...$params), array_values($bindParams->sqlArgs), ['Scrollable' => SQLSRV_CURSOR_FORWARD]);
                }
            } else {
                $statement = sqlsrv_query(SQLSrvConnection::getInstance()->getConnection(), self::parse(...$params));
            }
            if ($statement) {
                self::setStatement($statement);
            }
            return $statement;
        }
        return false;
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return SQLSrvConnection|null
     */
    public static function query(mixed ...$params): ?SQLSrvConnection
    {
        if (!empty($params) && ($statement = self::prepareStatement(...$params)) && self::exec($statement)) {
            $colCount = is_resource($statement) ? sqlsrv_num_fields($statement) : 0;
            if ($colCount > 0) {
                self::setQueryColumns($colCount);
                self::setQueryRows(
                    (function (mixed $stmt): int {
                        $results = [];
                        $rows = 0;
                        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                            $results[] = $row;
                            $rows++;
                        }
                        self::setStatement(['results' => $results, 'statement' => $stmt]);
                        return $rows;
                    })($statement) ?? 0
                );
            } else {
                self::setAffectedRows(sqlsrv_rows_affected($statement));
                self::setStatement(['results' => []]);
            }
        }
        return SQLSrvConnection::getInstance();
    }
    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return SQLSrvConnection|null
     */
    public static function prepare(mixed ...$params): ?SQLSrvConnection
    {
        if (!empty($params) && ($statement = self::prepareStatement(...$params))) {
            $bindParams = Schema::makeArgs([self::getStatement(), ...$params]);
            self::bindParam($bindParams);
        }
        return SQLSrvConnection::getInstance();
    }

    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return mixed
     */
    public static function exec(mixed ...$params): mixed
    {
        $statement = reset($params) ?? self::getStatement();
        sqlsrv_execute($statement);
        self::setStatement($statement);
        return self::getStatement();
    }
}
