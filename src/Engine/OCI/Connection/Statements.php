<?php

namespace GenericDatabase\Engine\OCI\Connection;

use stdClass;
use GenericDatabase\Core\Schema;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Translate;
use GenericDatabase\Engine\OCIConnection;

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
        if ($name !== null) {
            $filter = "WHERE OWNER = USER AND identity_column = 'YES' AND TABLE_NAME = :tableName";
            $query = sprintf("SELECT data_default AS sequence_val, table_name, column_name FROM all_tab_columns %s", $filter);
            $stmt = oci_parse(OCIConnection::getInstance()->getConnection(), $query);
            oci_bind_by_name($stmt, ":tableName", $name);
            if (oci_execute($stmt)) {
                $row = oci_fetch_array($stmt, OCI_ASSOC);
                if ($row) {
                    $sequenceVal = $row['SEQUENCE_VAL'];
                    $sequenceVal = str_replace('.nextval', '.currval', $sequenceVal);
                    $sequenceVal = str_replace('"', '', $sequenceVal);
                    $query = "SELECT $sequenceVal FROM DUAL";
                    $statement = oci_parse(OCIConnection::getInstance()->getConnection(), $query);
                    if ($statement && oci_execute($statement)) {
                        $row = oci_fetch_array($statement, OCI_NUM);
                        return $row ? (int) $row[0] : false;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Binds variables to a prepared statement with specified types.
     * This method binds variables to a prepared statement based on their types,
     * allowing for more precise parameter binding.
     *
     * @param mixed $statement An array containing the parameters to bind.
     * @param mixed $param The prepared statement to bind variables to.
     * @param mixed $value The prepared statement to bind variables to.
     * @return mixed
     */
    private static function internalBindVariable(array $params, mixed $statement): mixed
    {
        foreach ($params as $key => $value) {
            oci_bind_by_name($statement, $key, $params[$key]);
        }
        return $statement;
    }

    /**
     * Binds an array multiple parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private static function internalBindParamArrayMulti(object $params): void
    {
        $affectedRows = 0;
        foreach ($params->sqlArgs as $param) {
            self::setStatement(self::internalBindVariable($param, $params->sqlStatement));
            if (self::exec(self::getStatement())) {
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
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private static function internalBindParamArraySingle(object $params): void
    {
        self::internalBindParamArgs($params);
    }

    /**
     * Binds an array parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
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
     * @param mixed $params The name of the parameter or an args of parameters and values.
     * @return void
     */
    private static function internalBindParamArgs(object $params): void
    {
        self::setStatement(self::internalBindVariable($params->sqlArgs, $params->sqlStatement));
        if (self::exec(self::getStatement())) {
            if ((int) oci_num_fields(self::getStatement()) > 0) {
                self::setQueryRows(
                    (function (mixed $stmt): int {
                        $results = [];
                        $rows = 0;
                        while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
                            $results[] = $row;
                            $rows++;
                        }
                        self::setStatement(['results' => $results]);
                        return $rows;
                    })(self::getStatement()) ?? 0
                );
            } else {
                self::setAffectedRows((int) oci_num_rows(self::getStatement()));
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
        self::setQueryParameters($params->sqlArgs);
        if ($params->isArray) {
            self::internalBindParamArray($params);
        } else {
            self::internalBindParamArgs($params);
        }
        self::setQueryColumns((int) oci_num_fields($params->sqlStatement));
    }

    /**
     * Parses an SQL statement and returns an statement.
     *
     * @param mixed ...$params The parameters for the query function.
     * @return string The statement resulting from the SQL statement.
     */
    public static function parse(mixed ...$params): string
    {
        self::setQueryString(Translate::escape(reset($params), Translate::SQL_DIALECT_DOUBLE_QUOTE));
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
            $statement = oci_parse(OCIConnection::getInstance()->getConnection(), self::parse(...$params));
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
     * @return OCIConnection|null
     */
    public static function query(mixed ...$params): ?OCIConnection
    {
        if (!empty($params) && ($statement = self::prepareStatement(...$params)) && self::exec($statement)) {
            $colCount = is_resource($statement) ? oci_num_fields($statement) : 0;
            if ($colCount > 0) {
                self::setQueryColumns($colCount);
                self::setQueryRows(
                    (function (mixed $stmt): int {
                        $results = [];
                        $rows = 0;
                        while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
                            $results[] = $row;
                            $rows++;
                        }
                        self::setStatement(['results' => $results, 'statement' => $stmt]);
                        return $rows;
                    })($statement) ?? 0
                );
            } else {
                self::setAffectedRows(oci_num_rows($statement));
                self::setStatement(['results' => []]);
            }
        }
        return OCIConnection::getInstance();
    }

    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return OCIConnection|null
     */
    public static function prepare(mixed ...$params): ?OCIConnection
    {
        if (!empty($params) && (self::prepareStatement(...$params))) {
            $bindParams = Schema::makeArgs([self::getStatement(), ...$params]);
            self::bindParam($bindParams);
        }
        return OCIConnection::getInstance();
    }

    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return bool
     */
    public static function exec(mixed ...$params): mixed
    {
        $statement = reset($params);
        $data = $params[1] ?? false;
        if (!is_array($data)) {
            $data = [];
        }
        return call_user_func_array('oci_execute', [$statement, OCI_COMMIT_ON_SUCCESS]);
    }
}
