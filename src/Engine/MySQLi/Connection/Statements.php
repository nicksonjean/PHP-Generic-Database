<?php

namespace GenericDatabase\Engine\MySQLi\Connection;

use GenericDatabase\Engine\MySQLiConnection;
use GenericDatabase\Helpers\Schema;
use GenericDatabase\Helpers\Translate;
use mysqli_stmt;
use GenericDatabase\Helpers\Types\Compound\Objects;
use AllowDynamicProperties;

#[AllowDynamicProperties]
class Statements
{
    use Objects;
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
     * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
     *
     * @param mixed $params Content to be quoted
     * @return mixed
     */
    public static function quote(mixed ...$params): mixed
    {
        $string = reset($params);
        $quote = $params[1] ?? false;
        if (is_array($string)) {
            return array_map(fn($str) => self::quote($str, $quote), $string);
        } elseif ($string && preg_match("/^(?:\d+\.\d+|[1-9]\d*)$/S", (string) $string)) {
            return $string;
        }
        $quoted = fn($str) => MySQLiConnection::getInstance()->getConnection()->real_escape_string($str);
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
        if (!$name) {
            return (int) MySQLiConnection::getInstance()->getConnection()->insert_id;
        }
        $filter = "WHERE TABLE_NAME = ? AND COLUMN_KEY = ? AND EXTRA = ?";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = MySQLiConnection::getInstance()->getConnection()->prepare($query);
        $stmt = self::internalBindVariable([$name, 'PRI', 'auto_increment'], $stmt);
        $stmt->execute();
        $autoKeyResult = $stmt->get_result();
        $autoKey = $autoKeyResult->fetch_assoc();
        if (isset($autoKey['COLUMN_NAME'])) {
            $query = sprintf("SELECT MAX(%s) AS value FROM %s", $autoKey['COLUMN_NAME'], $name);
            $stmt = MySQLiConnection::getInstance()->getConnection()->prepare($query);
            $stmt->execute();
            $maxIndexResult = $stmt->get_result();
            $maxIndex = $maxIndexResult->fetch_assoc()['value'];
            if ($maxIndex !== null) {
                return (int) $maxIndex;
            }
        }
        return (int) $autoKey['COLUMN_NAME'] ?? 0;
    }

    /**
     * Binds variables to a prepared statement with specified types.
     * This method binds variables to a prepared statement based on their types,
     * allowing for more precise parameter binding.
     *
     * @param array &$preparedParams An array containing the parameters to bind.
     * @param mysqli_stmt $statement The prepared statement to bind variables to.
     * @return mysqli_stmt The prepared statement with bound variables.
     */
    private static function internalBindVariable(array $preparedParams, mysqli_stmt $statement): mysqli_stmt
    {
        $types = '';
        $values = [];
        foreach ($preparedParams as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } elseif (is_string($value)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
            $values[] = $value;
        }
        if (!empty($types)) {
            $valueArray = array_values($values);
            $statement->bind_param($types, ...$valueArray);
        }
        return $statement;
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
            self::setStatement(self::internalBindVariable($argument, $params->statement->object));
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
        self::setStatement(@self::internalBindVariable($params->query->arguments, $params->statement->object));
        if (self::exec(self::getStatement())) {
            if (self::getStatement()->field_count > 0) {
                $result = self::getStatement()->get_result();
                if ($result) {
                    self::setStatement($result);
                    self::setQueryRows((int) $result->num_rows);
                }
            } else {
                self::setAffectedRows(self::getStatement()->affected_rows);
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
        self::setQueryColumns((int) self::getStatement()->field_count);
    }

    /**
     * Parses an SQL statement and returns an statement.
     *
     * @param mixed ...$params The parameters for the query function.
     * @return string The statement resulting from the SQL statement.
     */
    public static function parse(mixed ...$params): string
    {
        self::setQueryString(Translate::binding(Translate::escape(reset($params), Translate::SQL_DIALECT_BACKTICK)));
        return self::getQueryString();
    }

    /**
     * This function binds the parameters to a prepared statement.
     *
     * @param mixed ...$params
     * @return mysqli_stmt|false
     */
    private static function prepareStatement(mixed ...$params): mysqli_stmt|false
    {
        self::setAllMetadata();
        if (!empty($params)) {
            $statement = MySQLiConnection::getInstance()->getConnection()->prepare(self::parse(...$params));
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
     * @return MySQLiConnection|null
     */
    public static function query(mixed ...$params): ?MySQLiConnection
    {
        if (!empty($params) && ($statement = self::prepareStatement(...$params)) && self::exec($statement)) {
            $colCount = $statement->field_count;
            if ($colCount > 0) {
                self::setQueryColumns($colCount);
                self::setQueryRows(
                    (function (mixed $stmt): int {
                        $result = $stmt->get_result();
                        if (!$result) {
                            return 0;
                        }
                        $results = $result->fetch_all(MYSQLI_ASSOC);
                        self::setStatement(['results' => $results]);
                        return $result->num_rows;
                    })($statement) ?? 0
                );
            } else {
                self::setStatement(['results' => []]);
                self::setAffectedRows((int) $statement->affected_rows);
            }
        }
        return MySQLiConnection::getInstance();
    }
    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return MySQLiConnection|null
     */
    public static function prepare(mixed ...$params): ?MySQLiConnection
    {
        if (!empty($params) && (self::prepareStatement(...$params))) {
            $bindParams = Schema::makeArgs([self::getStatement(), ...$params]);
            self::bindParam($bindParams);
        }
        return MySQLiConnection::getInstance();
    }
    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return bool
     */
    public static function exec(mixed ...$params): bool
    {
        $stmt = reset($params);
        return $stmt->execute();
    }
}
