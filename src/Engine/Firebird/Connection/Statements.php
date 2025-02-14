<?php

namespace GenericDatabase\Engine\Firebird\Connection;

use GenericDatabase\Core\Query;
use GenericDatabase\Helpers\Schemas;
use GenericDatabase\Helpers\Parsers\SQL;
use GenericDatabase\Helpers\Validations;
use GenericDatabase\Engine\FirebirdConnection;
use GenericDatabase\Shared\Objectable;
use AllowDynamicProperties;

#[AllowDynamicProperties]
class Statements
{
    use Objectable;
    /**
     * Instance of the Statement of the database
     * @var mixed $statement = null
     */
    private static mixed $statement = null;

    /**
     * Instance of the Statement of the database
     * @var mixed $statementResult = null
     */
    private static mixed $statementResult = null;

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
        $metadata = new self();
        $metadata->query->string = self::getQueryString();
        $metadata->query->arguments = self::getQueryParameters();
        $metadata->query->columns = self::getQueryColumns();
        $metadata->query->rows->fetched = self::getQueryRows();
        $metadata->query->rows->affected = self::getAffectedRows();
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
     * Returns the statement result for the function.
     *
     * @return mixed
     */
    private static function getStatementResult(): mixed
    {
        return self::$statementResult;
    }

    /**
     * Set the statement for the function.
     *
     * @param mixed $statement The statement to be set.
     */
    private static function setStatementResult(mixed $statement): void
    {
        self::$statementResult = $statement;
    }

    /**
     * Binds variables to a prepared statement with specified types.
     * This method binds variables to a prepared statement based on their types,
     * allowing for more precise parameter binding.
     *
     * @param mixed $data The prepared statement to bind variables to.
     * @return mixed The prepared statement with bound variables.
     */
    private static function internalBindVariable(mixed $data): mixed
    {
        return Validations::detectTypes($data);
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
            return 0;
        }
        $filter = 'WHERE RDB$RELATION_NAME = ? AND RDB$IDENTITY_TYPE = 1';
        $query = sprintf('SELECT RDB$FIELD_NAME, RDB$GENERATOR_NAME FROM RDB$RELATION_FIELDS %s', $filter);
        $stmt = ibase_prepare(FirebirdConnection::getInstance()->getConnection(), $query);
        $result = ibase_execute($stmt, $name);
        if (!$result) {
            return false;
        }
        $row = ibase_fetch_assoc($result);
        if (isset($row['RDB$GENERATOR_NAME'])) {
            $identityColumn = $row['RDB$GENERATOR_NAME'];
            $query = sprintf('SELECT GEN_ID(%s, 0) AS LASTINSERTEDID FROM RDB$DATABASE', $identityColumn);
            $stmt = ibase_query(FirebirdConnection::getInstance()->getConnection(), $query);
            if (!$stmt) {
                return false;
            }
            $result = ibase_fetch_assoc($stmt);
            if ($result) {
                return (int) $result['LASTINSERTEDID'];
            }
        }
        return false;
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
        foreach ($params->query->arguments as $argument) {
            self::internalBindVariable($argument);
            if (self::exec($params->statement->object, array_values($argument))) {
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
     * @param object $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private static function internalBindParamArray(object $params): void
    {
        if ($params->is->array->multi) {
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
        self::internalBindVariable($params->query->arguments);
        if ($stmt = self::exec($params->statement->object, array_values($params->query->arguments))) {
            self::setStatement($stmt);
            $colCount = is_resource($params->statement->object) ? ibase_num_fields($params->statement->object) : 0;
            if ($colCount > 0) {
                self::setQueryColumns((int) $colCount);
                $rowCount = 0;
                $rows = [];
                while ($line = ibase_fetch_assoc($stmt)) {
                    $rowCount++;
                    $rows[] = $line;
                }
                self::setQueryRows($rowCount);
                self::setStatement(['results' => $rows]);
            } else {
                self::setStatement(['results' => []]);
                self::setAffectedRows((int) ibase_affected_rows(FirebirdConnection::getInstance()->getConnection()));
            }
        }
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param object $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    public static function bindParam(object $params): void
    {
        self::setQueryParameters($params->query->arguments);
        if ($params->by->array) {
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
        self::setQueryString(SQL::binding(SQL::escape(reset($params), SQL::SQL_DIALECT_DOUBLE_QUOTE)));
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
            $statement = call_user_func_array((reset($params)[1] === Query::RAW) ? 'ibase_query' : 'ibase_prepare', [FirebirdConnection::getInstance()->getConnection(), self::parse(reset($params)[0])]);
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
     * @return FirebirdConnection|null
     */
    public static function query(mixed ...$params): ?FirebirdConnection
    {
        if (!empty($params) && ($statement = self::prepareStatement([...$params, Query::RAW]))) {
            $colCount = is_resource($statement) ? ibase_num_fields($statement) : 0;
            if ($colCount > 0) {
                $cloneStmt = function () use ($statement, $params): mixed {
                    if (!is_resource($statement)) {
                        return false;
                    }
                    return self::prepareStatement([...$params, Query::RAW]);
                };
                $countStmt = $cloneStmt();
                if ($countStmt) {
                    $rowCount = 0;
                    while (ibase_fetch_row($countStmt)) {
                        $rowCount++;
                    }
                    self::setQueryRows($rowCount);
                }
                self::setQueryColumns($colCount);
                self::setStatement($statement);
            } else {
                self::setStatement(['results' => []]);
                self::setAffectedRows(ibase_affected_rows(FirebirdConnection::getInstance()->getConnection()));
            }
        }
        return FirebirdConnection::getInstance();
    }

    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return FirebirdConnection|null
     */
    public static function prepare(mixed ...$params): ?FirebirdConnection
    {
        if (!empty($params) && (self::prepareStatement([...$params, Query::PREPARED]))) {
            $bindParams = Schemas::makeArgs([self::getStatement(), ...$params]);
            self::bindParam($bindParams);
        }
        return FirebirdConnection::getInstance();
    }

    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return mixed
     */
    public static function exec(mixed ...$params): mixed
    {
        $statement = reset($params);
        $data = $params[1] ?? false;
        if (!is_array($data)) {
            $data = [];
        }
        $data = self::internalBindVariable($data);
        array_unshift($data, $statement);
        return call_user_func_array('ibase_execute', $data);
    }
}
