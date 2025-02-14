<?php

namespace GenericDatabase\Engine\PgSQL\Connection;

use \PgSql\Result;
use GenericDatabase\Core\Query;
use GenericDatabase\Helpers\Hash;
use GenericDatabase\Helpers\Schemas;
use GenericDatabase\Helpers\Parsers\SQL;
use GenericDatabase\Helpers\Validations;
use GenericDatabase\Engine\PgSQLConnection;
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
     * Instance of the Statement name of the database
     * @var string $stmtName = ''
     */
    private static mixed $stmtName = '';

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
     * Returns the statement for the function.
     *
     * @return string
     */
    public static function getStmtName(): string
    {
        return self::$stmtName;
    }

    /**
     * Sets the statement for the function.
     *
     * @param string $statement The statement to be set.
     */
    public static function setStmtName(string $stmtName): void
    {
        self::$stmtName = $stmtName;
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
        if (!$name) {
            $result = pg_query(PgSQLConnection::getInstance()->getConnection(), "SELECT lastval()");
            if ($result) {
                $row = pg_fetch_row($result);
                return $row ? (int) $row[0] : false;
            }
            return false;
        }
        $query = "SELECT
            current_database() as database_name,
            seq.schemaname AS schema_name,
            seq.sequencename AS name,
            table_identities.*,
            seq.last_value
        FROM pg_sequences seq
            INNER JOIN pg_namespace nspc ON nspc.nspname = seq.schemaname
            INNER JOIN pg_class s ON s.relname = seq.sequencename AND s.relnamespace = nspc.oid
            LEFT OUTER JOIN (
                SELECT
                    t.relname AS table_name,
                    a.attname AS column_name,
                    d.objid AS objid
                FROM pg_namespace tns
                    JOIN pg_class t ON tns.oid = t.relnamespace AND t.relkind IN ('p', 'r')
                    JOIN pg_index i ON t.oid = i.indrelid AND i.indisprimary
                    JOIN pg_attribute a ON i.indrelid = a.attrelid AND a.attnum = ANY (i.indkey)
                    JOIN pg_depend d ON t.oid = d.refobjid AND d.refobjsubid = a.attnum
            ) table_identities ON table_identities.objid = s.oid
        WHERE table_identities.table_name = '$name'
        AND (SELECT current_database()) = current_database()";
        $result = pg_query(PgSQLConnection::getInstance()->getConnection(), $query);
        if ($result) {
            $row = pg_fetch_assoc($result);
            if ($row && isset($row['last_value']) && is_null($row['last_value']) && isset($row['name'])) {
                $seqName = $row['name'];
                $seqResult = pg_query_params(PgSQLConnection::getInstance()->getConnection(), "SELECT currval($1)", [$seqName]);
                if ($seqResult) {
                    $seqRow = pg_fetch_row($seqResult);
                    return $seqRow ? (int) $seqRow[0] : false;
                }
            } elseif ($row && isset($row['last_value']) && !is_null($row['last_value'])) {
                return (int) $row['last_value'];
            }
        }
        return false;
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
        foreach ($params->query->arguments as $argument) {
            self::internalBindVariable($argument);
            if (self::exec(self::getStmtName(), array_values($argument))) {
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
     * @param mixed $params The name of the parameter or an args of parameters and values.
     * @return void
     */
    private static function internalBindParamArgs(object $params): void
    {
        self::internalBindVariable($params->query->arguments);
        if ($result = self::exec(self::getStmtName(), array_values($params->query->arguments))) {
            $colCount = pg_num_fields($result);
            if ($colCount > 0) {
                self::setQueryColumns($colCount);
                self::setQueryRows(
                    (function (mixed $result): int {
                        $results = [];
                        $rows = 0;
                        while ($row = pg_fetch_array($result, null, PGSQL_ASSOC)) {
                            $results[] = $row;
                            $rows++;
                        }
                        self::setStatement(['results' => $results]);
                        return $rows;
                    })($result) ?? 0
                );
            } else {
                self::setStatement(['results' => []]);
                self::setAffectedRows(pg_affected_rows($result));
            }
            pg_free_result($result);
        }
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
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
        self::setQueryString(SQL::binding(SQL::escape(reset($params), SQL::SQL_DIALECT_DOUBLE_QUOTE), SQL::BIND_DOLLAR_SIGN));
        return self::getQueryString();
    }

    /**
     * This function binds the parameters to a prepared statement.
     *
     * @param mixed ...$params
     * @return Result|bool
     */
    private static function prepareStatement(mixed ...$params): mixed
    {
        self::setAllMetadata();
        if (!empty($params)) {
            self::setStmtName(Hash::hash());
            if (reset($params)[1] === Query::RAW) {
                $statement = pg_query(PgSQLConnection::getInstance()->getConnection(), self::parse(reset($params)[0]));
            } else {
                $statement = pg_prepare(PgSQLConnection::getInstance()->getConnection(), self::getStmtName(), self::parse(reset($params)[0]));
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
     * @return PgSQLConnection|null
     */
    public static function query(mixed ...$params): ?PgSQLConnection
    {
        if (!empty($params) && ($statement = self::prepareStatement([...$params, Query::RAW]))) {
            $colCount = pg_num_fields($statement);
            if ($colCount > 0) {
                $cloneStmt = function () use ($statement, $params): mixed {
                    if ($statement instanceof Result) {
                        return false;
                    }
                    return self::prepareStatement([...$params, Query::RAW]);
                };
                $countResult = $cloneStmt();
                if ($countResult) {
                    $rowCount = 0;
                    while (pg_fetch_array($countResult, null, PGSQL_ASSOC)) {
                        $rowCount++;
                    }
                    self::setQueryRows($rowCount);
                }
                self::setQueryColumns($colCount);
                self::setStatement($statement);
            } else {
                self::setStatement(['results' => []]);
                self::setAffectedRows(pg_affected_rows($statement));
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
        if (!empty($params) && (self::prepareStatement([...$params, Query::PREPARED]))) {
            $bindParams = Schemas::makeArgs([self::getStatement(), ...$params, self::getStmtName()]);
            self::bindParam($bindParams);
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
        $statement = reset($params);
        $data = $params[1] ?? false;
        if (!is_array($data)) {
            $data = [];
        }
        $processedData = array_values($data);
        return call_user_func_array('pg_execute', [PgSQLConnection::getInstance()->getConnection(), $statement, $processedData]);
    }
}
