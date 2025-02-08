<?php

namespace GenericDatabase\Engine\SQLite\Connection;

use GenericDatabase\Engine\SQLiteConnection;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Translate;
use SQLite3;
use SQLite3Result;
use stdClass;

class Statements
{
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
     * Returns the statement result for the function.
     *
     * @return mixed
     */
    public static function getStatementResult(): mixed
    {
        return self::$statementResult;
    }

    /**
     * Set the statement for the function.
     *
     * @param mixed $statement The statement to be set.
     */
    public static function setStatementResult(mixed $statement): void
    {
        self::$statementResult = $statement;
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
        return (int) SQLiteConnection::getInstance()->getConnection()->lastInsertRowID() ?? 0;
    }

    /**
     * Binds variables to a prepared statement with specified types.
     * This method binds variables to a prepared statement based on their types,
     * allowing for more precise parameter binding.
     *
     * @param array &$preparedParams An array containing the parameters to bind.
     * @param mixed $stmt The prepared statement to bind variables to.
     * @return mixed The prepared statement with bound variables.
     */
    private static function internalBindVariable(array &$preparedParams, mixed $stmt): mixed
    {
        $index = 0;
        foreach ($preparedParams as &$arg) {
            $types = match (true) {
                is_float($arg) => SQLITE3_FLOAT,
                is_integer($arg) => SQLITE3_INTEGER,
                is_string($arg) => SQLITE3_TEXT,
                is_null($arg) => SQLITE3_NULL,
                default => SQLITE3_BLOB,
            };
            call_user_func_array([$stmt, 'bindParam'], [array_keys($preparedParams)[$index], &$arg, $types]);
            $index++;
        }
        return $stmt;
    }

    /**
     * Binds an array multiple parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private static function internalBindParamArrayMulti(mixed ...$params): void
    {
        foreach ($params['sqlArgs'] as $param) {
            $statement = self::internalBindVariable($param, $params['sqlStatement']);
            (!$params['rowCount'])
                ? self::setStatement(self::exec($statement))
                : self::setStatementResult(self::exec($statement));
            self::setAffectedRows((int) SQLiteConnection::getInstance()->getConnection()->changes());
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
        self::internalBindParamArgs(...$params);
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
        $statement = self::internalBindVariable($params['sqlArgs'], $params['sqlStatement']);
        (!$params['rowCount'])
            ? self::setStatement(self::exec($statement))
            : self::setStatementResult(self::exec($statement));
        self::setAffectedRows((int) SQLiteConnection::getInstance()->getConnection()->changes());
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
        self::setQueryString(Translate::escape(reset($params), Translate::SQL_DIALECT_DOUBLE_QUOTE));
        return self::getQueryString();
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return SQLiteConnection|null
     */
    public static function query(mixed ...$params): ?SQLiteConnection
    {
        self::setAllMetadata();
        if (!empty($params)) {
            $query = self::parse(...$params);
            $statement = SQLiteConnection::getInstance()->getConnection()->prepare($query);
            if ($statement) {
                self::setStatement($statement);
                $queryParameters = $params[1] ?? [];
                $result = $statement->execute();
                if (is_object($result) && get_class($result) === 'SQLite3Result' && method_exists($result, 'numColumns')) {
                    $numColumns = $result->numColumns();
                    if ($numColumns > 0) {
                        self::setQueryColumns($numColumns);
                        $results = [];
                        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                            $results[] = $row;
                        }
                        self::setQueryRows(count($results));
                        self::setStatement([
                            'results' => $results,
                            'queryString' => $query,
                            'queryParameters' => $queryParameters,
                        ]);
                        $result->reset();
                    } else {
                        self::setAffectedRows(SQLiteConnection::getInstance()->getConnection()->changes());
                        self::setQueryRows(0);
                        self::setQueryColumns(0);
                    }
                } else {
                    self::setQueryRows(0);
                    self::setQueryColumns(0);
                }
            }
        }
        return SQLiteConnection::getInstance();
    }
    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return SQLiteConnection|null
     */
    public static function prepare(mixed ...$params): ?SQLiteConnection
    {
        $driver = SQLiteConnection::getInstance()->getDriver();
        self::setAllMetadata();
        if (!empty($params)) {
            $stmt = SQLiteConnection::getInstance()->getConnection()->prepare(self::parse(...$params));
            if ($stmt) {
                self::setStatement($stmt);
                if (array_key_exists(1, $params) && is_array($params[1])) {
                    $bindParams = array_merge(self::makeArgs($driver, $stmt, ...$params), ['rowCount' => false]);
                    self::setQueryParameters($bindParams['sqlArgs']);
                    if (isset($bindParams['sqlArgs']) && is_array($bindParams['sqlArgs'])) {
                        if (is_array($bindParams['sqlArgs']) && isset($bindParams['sqlArgs'][0]) && is_array($bindParams['sqlArgs'][0])) {
                            $affectedRows = 0;
                            foreach ($bindParams['sqlArgs'] as $args) {
                                self::internalBindVariable($args, $stmt);
                                $result = $stmt->execute();
                                if ($result) {
                                    $affectedRows += SQLiteConnection::getInstance()->getConnection()->changes();
                                }
                            }
                            self::setAffectedRows($affectedRows);
                        } else {
                            self::internalBindVariable($bindParams['sqlArgs'], $stmt);
                            $result = $stmt->execute();
                            if ($result) {
                                self::setAffectedRows(SQLiteConnection::getInstance()->getConnection()->changes());
                                if (is_object($result) && get_class($result) === 'SQLite3Result' && method_exists($result, 'numColumns')) {
                                    self::setQueryColumns($result->numColumns());
                                    $rowCount = 0;
                                    while ($result->fetchArray(SQLITE3_ASSOC)) {
                                        $rowCount++;
                                    }
                                    self::setQueryRows($rowCount);
                                    $result->reset();
                                } else {
                                    self::setQueryRows(0);
                                    self::setQueryColumns(0);
                                }
                            }
                        }
                    }
                }
            }
        }
        return SQLiteConnection::getInstance();
    }
    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return SQLite3Result|false
     */
    public static function exec(mixed ...$params): SQLite3Result|false
    {
        return (reset($params) ?? self::getStatement())->execute();
    }
}
