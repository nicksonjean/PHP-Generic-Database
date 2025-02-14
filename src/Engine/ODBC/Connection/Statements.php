<?php

namespace GenericDatabase\Engine\ODBC\Connection;

use GenericDatabase\Engine\ODBCConnection;
use GenericDatabase\Helpers\Schemas;
use GenericDatabase\Helpers\Parsers\SQL;
use GenericDatabase\Helpers\Validations;
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
        return match (true) {
            is_int($string) => $string,
            is_float($string) => "'" . str_replace(',', '.', strval($string)) . "'",
            is_bool($string) => $string ? '1' : '0',
            is_null($string) => 'NULL',
            default => "'" . str_replace("'", "''", (string) $string) . "'",
        };
    }

    private static function lastInsertIdMySQL(?string $name = null): string|int|false
    {
        if (!$name) {
            return false;
        }
        $filter = "WHERE TABLE_NAME = ? AND COLUMN_KEY = ? AND EXTRA = ?";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = odbc_prepare(ODBCConnection::getInstance()->getConnection(), $query);
        odbc_execute($stmt, [$name, 'PRI', 'auto_increment']);
        if ($stmt && odbc_fetch_row($stmt)) {
            $identityColumn = odbc_result($stmt, "COLUMN_NAME");
            $query = "SELECT MAX($identityColumn) AS LastInsertedID FROM $name";
            $stmt = odbc_exec(ODBCConnection::getInstance()->getConnection(), $query);
            if ($stmt && odbc_fetch_row($stmt)) {
                return (int) odbc_result($stmt, "LastInsertedID");
            }
        }
        return false;
    }

    private static function lastInsertIdPgSQL(?string $name = null): string|int|false
    {
        if (!$name) {
            return false;
        }
        $query = "SELECT
            current_database() as DATABASE_NAME,
            seq.schemaname AS SCHEMA_NAME,
            seq.sequencename AS NAME,
            table_identities.*,
            seq.last_value
        FROM pg_sequences seq
             INNER JOIN pg_namespace nspc ON nspc.nspname = seq.schemaname
             INNER JOIN pg_class s ON s.relname = seq.sequencename AND s.relnamespace = nspc.oid
             LEFT OUTER JOIN (
                 SELECT
                     t.relname AS TABLE_NAME,
                     a.attname AS COLUMN_NAME,
                     d.objid AS OBJID
                 FROM pg_namespace tns
                          JOIN pg_class t ON tns.oid = t.relnamespace AND t.relkind IN ('p', 'r')
                          JOIN pg_index i ON t.oid = i.indrelid AND i.indisprimary
                          JOIN pg_attribute a ON i.indrelid = a.attrelid AND a.attnum = ANY (i.indkey)
                          JOIN pg_depend d ON t.oid = d.refobjid AND d.refobjsubid = a.attnum
             ) table_identities ON table_identities.OBJID = s.oid
        WHERE table_identities.TABLE_NAME = ?
        AND (SELECT current_database()) = ?";
        $stmt = odbc_prepare(ODBCConnection::getInstance()->getConnection(), $query);
        odbc_execute($stmt, [$name, ODBCConnection::getInstance()->getDatabase()]);
        if ($stmt && odbc_fetch_row($stmt)) {
            $sequenceName = odbc_result($stmt, "NAME");
            $query = "SELECT currval('$sequenceName') AS last_value";
            $stmt = odbc_exec(ODBCConnection::getInstance()->getConnection(), $query);
            if ($stmt && odbc_fetch_row($stmt)) {
                return (int) odbc_result($stmt, "last_value");
            }
        }
        return false;
    }

    private static function lastInsertIdSQLSrv(?string $name = null): string|int|false
    {
        if (!$name) {
            $query = "SELECT @@IDENTITY AS LastInsertedID";
            $stmt = odbc_exec(ODBCConnection::getInstance()->getConnection(), $query);
            if ($stmt && odbc_fetch_row($stmt)) {
                return (int) odbc_result($stmt, "LastInsertedID");
            }
            return false;
        }
        $filter = "WHERE TABLE_NAME = ? AND TABLE_SCHEMA = SCHEMA_NAME() AND COLUMNPROPERTY(OBJECT_ID(TABLE_SCHEMA + '.' + TABLE_NAME), COLUMN_NAME, 'IsIdentity') = 1";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = odbc_prepare(ODBCConnection::getInstance()->getConnection(), $query);
        odbc_execute($stmt, [$name]);
        if ($stmt && odbc_fetch_row($stmt)) {
            $identityColumn = odbc_result($stmt, "COLUMN_NAME");
            $query = "SELECT MAX($identityColumn) AS LastInsertedID FROM $name";
            $stmt = odbc_exec(ODBCConnection::getInstance()->getConnection(), $query);
            if ($stmt && odbc_fetch_row($stmt)) {
                return (int) odbc_result($stmt, "LastInsertedID");
            }
        }
        return false;
    }

    private static function lastInsertIdOCI(?string $name = null): string|int|false
    {
        if ($name !== null) {
            $filter = "WHERE OWNER = USER AND identity_column = 'YES' AND TABLE_NAME = ?";
            $query = sprintf("SELECT data_default AS sequence_val, table_name, column_name FROM all_tab_columns %s", $filter);
            $stmt = odbc_prepare(ODBCConnection::getInstance()->getConnection(), $query);
            odbc_execute($stmt, [$name]);
            if ($stmt && odbc_fetch_row($stmt)) {
                $sequenceVal = odbc_result($stmt, "sequence_val");
                $sequenceVal = str_replace('.nextval', '.currval', $sequenceVal);
                $sequenceVal = str_replace('"', '', $sequenceVal);
                $query = "SELECT $sequenceVal FROM DUAL";
                $stmt = odbc_exec(ODBCConnection::getInstance()->getConnection(), $query);
                if ($stmt && odbc_fetch_row($stmt)) {
                    return (int) odbc_result($stmt, 1);
                }
            }
        }
        return false;
    }

    private static function lastInsertIdFirebird(?string $name = null): string|int|false
    {
        if (!$name) {
            return false;
        }
        $filter = 'WHERE RDB$RELATION_NAME=? AND RDB$IDENTITY_TYPE=1';
        $query = sprintf('SELECT RDB$FIELD_NAME, RDB$GENERATOR_NAME FROM RDB$RELATION_FIELDS %s', $filter);
        $stmt = odbc_prepare(ODBCConnection::getInstance()->getConnection(), $query);
        odbc_execute($stmt, [$name]);
        if ($stmt && odbc_fetch_row($stmt)) {
            $identityColumn = odbc_result($stmt, 'RDB$GENERATOR_NAME');
            $query = sprintf('SELECT GEN_ID(%s,0) AS LASTINSERTEDID FROM RDB$DATABASE', $identityColumn);
            $stmt = odbc_exec(ODBCConnection::getInstance()->getConnection(), $query);
            if ($stmt && odbc_fetch_row($stmt)) {
                return (int) odbc_result($stmt, "LastInsertedID");
            }
        }
        return false;
    }

    private static function lastInsertIdSQLite(?string $name = null): string|int|false
    {
        if (!$name) {
            return false;
        }
        $query = "SELECT seq FROM sqlite_sequence WHERE name = ?";
        $stmt = odbc_prepare(ODBCConnection::getInstance()->getConnection(), $query);
        if (!$stmt) {
            return false;
        }
        if (!odbc_execute($stmt, [$name])) {
            return false;
        }
        if (odbc_fetch_row($stmt)) {
            return (int) odbc_result($stmt, "seq");
        }
        return false;
    }

    /**
     * This function returns the last ID generated by an auto-increment column,
     *  either the last one inserted during the current transaction, or by passing in the optional name parameter.
     *
     * @param ?string $name = null Resource name, table or view
     * @return string|int|false
     */
    public static function lastInsertId(?string $name = null): string|int|false
    {
        $driver = ODBCConnection::getInstance()->getDriver();
        return match ($driver) {
            'mysql' => self::lastInsertIdMySQL($name),
            'pgsql' => self::lastInsertIdPgSQL($name),
            'sqlsrv' => self::lastInsertIdSQLSrv($name),
            'oci' => self::lastInsertIdOCI($name),
            'firebird' => self::lastInsertIdFirebird($name),
            'sqlite' => self::lastInsertIdSQLite($name),
            default => (int) 0,
        };
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
        self::internalBindVariable($params->query->arguments);
        if (self::exec($params->statement->object, array_values($params->query->arguments))) {
            if (odbc_num_fields($params->statement->object) > 0) {
                $results = [];
                while ($row = odbc_fetch_array($params->statement->object, 0)) {
                    $results[] = $row;
                }
                self::setStatement(['results' => $results]);
                self::setQueryRows(count($results));
            } else {
                self::setAffectedRows((int) odbc_num_rows($params->statement->object));
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
        self::setQueryColumns((int) odbc_num_fields($params->statement->object));
    }

    /**
     * Parses an SQL statement and returns an statement.
     *
     * @param mixed ...$params The parameters for the query function.
     * @return string The statement resulting from the SQL statement.
     */
    public static function parse(mixed ...$params): string
    {
        $driver = ODBCConnection::getInstance()->getDriver();
        $dialectQuote = match ($driver) {
            'mysql' => SQL::SQL_DIALECT_BACKTICK,
            'pgsql', 'sqlsrv', 'oci', 'firebird' => SQL::SQL_DIALECT_DOUBLE_QUOTE,
            default => SQL::SQL_DIALECT_NONE,
        };
        self::setQueryString(SQL::binding(SQL::escape(reset($params), $dialectQuote)));
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
            $statement = odbc_prepare(ODBCConnection::getInstance()->getConnection(), self::parse(...$params));
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
     * @return ODBCConnection|null
     */
    public static function query(mixed ...$params): ?ODBCConnection
    {
        if (!empty($params) && ($statement = self::prepareStatement(...$params)) && self::exec($statement)) {
            $colCount = is_resource($statement) ? odbc_num_fields($statement) : 0;
            if ($colCount > 0) {
                self::setQueryColumns($colCount);
                self::setQueryRows(
                    (function (mixed $stmt): int {
                        $results = [];
                        $rows = 0;
                        while ($row = odbc_fetch_array($stmt, 0)) {
                            $results[] = $row;
                            $rows++;
                        }
                        self::setStatement(['results' => $results, 'statement' => $stmt]);
                        return $rows;
                    })($statement) ?? 0
                );
            } else {
                if (is_resource($statement)) {
                    self::setAffectedRows(odbc_num_rows($statement));
                }
            }
        }
        return ODBCConnection::getInstance();
    }

    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return ODBCConnection|null
     */
    public static function prepare(mixed ...$params): ?ODBCConnection
    {
        if (!empty($params) && (self::prepareStatement(...$params))) {
            $bindParams = Schemas::makeArgs([self::getStatement(), ...$params]);
            self::bindParam($bindParams);
        }
        return ODBCConnection::getInstance();
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
        $processedData = array_values($data);
        return @call_user_func_array('odbc_execute', [$statement, $processedData]);
    }
}
