<?php

namespace GenericDatabase\Engine\PDO\Connection;

use GenericDatabase\Engine\PDOConnection;
use GenericDatabase\Helpers\Schema;
use GenericDatabase\Helpers\Translate;
use PDOStatement;
use PDO;
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
        $type = (empty($params) || !isset($params[1])) ? PDO::PARAM_STR : $params[1];
        return PDOConnection::getInstance()->getConnection()->quote($string, $type);
    }

    private static function lastInsertIdMySQL(?string $name = null): string|int|false
    {
        if (!$name) {
            return (int) PDOConnection::getInstance()->getConnection()->lastInsertId();
        }
        $filter = "WHERE TABLE_NAME = :tableName AND COLUMN_KEY = :columnKey AND EXTRA = :extra";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = PDOConnection::getInstance()->getConnection()->prepare($query);
        $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
        $stmt->bindValue(':columnKey', 'PRI', PDO::PARAM_STR);
        $stmt->bindValue(':extra', 'auto_increment', PDO::PARAM_STR);
        $stmt->execute();
        $autoKey = $stmt->fetch(PDO::FETCH_ASSOC);
        if (isset($autoKey['COLUMN_NAME'])) {
            $query = sprintf("SELECT MAX(%s) AS value FROM %s", $autoKey['COLUMN_NAME'], $name);
            $stmt = PDOConnection::getInstance()->getConnection()->prepare($query);
            $stmt->execute();
            $maxIndex = $stmt->fetch(PDO::FETCH_ASSOC)['value'];
            if ($maxIndex !== null) {
                return (int) $maxIndex;
            }
        }
        return ($autoKey['COLUMN_NAME'] ? (int) $autoKey['COLUMN_NAME'] : 0) ?? false;
    }

    private static function lastInsertIdPgSQL(?string $name = null): string|int|false
    {
        if (!$name) {
            return (int) PDOConnection::getInstance()->getConnection()->lastInsertId();
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
        WHERE table_identities.TABLE_NAME = :tableName
        AND (SELECT current_database()) = :databaseName";
        $stmt = PDOConnection::getInstance()->getConnection()->prepare($query);
        $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
        $stmt->bindValue(':databaseName', PDOConnection::getInstance()->getDatabase(), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['last_value']) && is_null($row['last_value']) && isset($row['name'])) {
            $seqName = $row['name'];
            $seqQuery = "SELECT currval(:seqName)";
            $seqStmt = PDOConnection::getInstance()->getConnection()->prepare($seqQuery);
            $seqStmt->bindValue(':seqName', $seqName);
            $seqStmt->execute();
            $seqResult = $seqStmt->fetch(PDO::FETCH_NUM);
            return $seqResult ? (int) $seqResult[0] : false;
        } elseif ($row && isset($row['last_value']) && !is_null($row['last_value'])) {
            return (int) $row['last_value'];
        }
        return false;
    }

    private static function lastInsertIdSQLSrv(?string $name = null): string|int|false
    {
        if (!$name) {
            $query = "SELECT CAST(@@IDENTITY AS BIGINT) AS LastInsertedID";
            $stmt = PDOConnection::getInstance()->getConnection()->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int) $result['LastInsertedID'] : 0;
        }
        $filter = "WHERE TABLE_NAME = :tableName AND TABLE_SCHEMA = SCHEMA_NAME() AND COLUMNPROPERTY(OBJECT_ID(TABLE_SCHEMA + '.' + TABLE_NAME), COLUMN_NAME, 'IsIdentity') = 1";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = PDOConnection::getInstance()->getConnection()->prepare($query);
        $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (isset($row['COLUMN_NAME'])) {
            $identityColumn = $row['COLUMN_NAME'];
            $query = sprintf("SELECT MAX(%s) AS LastInsertedID FROM %s", $identityColumn, $name);
            $stmt = PDOConnection::getInstance()->getConnection()->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int) $result['LastInsertedID'] : 0;
        }
        return false;
    }

    private static function lastInsertIdOCI(?string $name = null): string|int|false
    {
        if ($name !== null) {
            $filter = "WHERE OWNER = USER AND identity_column = 'YES' AND TABLE_NAME = :tableName";
            $seqQuery = sprintf("SELECT data_default AS sequence_val, table_name, column_name FROM all_tab_columns %s", $filter);
            $stmt = PDOConnection::getInstance()->getConnection()->prepare($seqQuery);
            $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
            if ($stmt->execute()) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['SEQUENCE_VAL'])) {
                    $sequenceVal = $row['SEQUENCE_VAL'];
                    $sequenceVal = str_replace('.nextval', '.currval', $sequenceVal);
                    $sequenceVal = str_replace('"', '', $sequenceVal);
                    $query = "SELECT $sequenceVal FROM DUAL";
                    $statement = PDOConnection::getInstance()->getConnection()->prepare($query);
                    if ($statement->execute()) {
                        $row = $statement->fetch(PDO::FETCH_NUM);
                        return $row ? (int) $row[0] : false;
                    }
                }
            }
        }
        return false;
    }

    private static function lastInsertIdFirebird(?string $name = null): string|int|false
    {
        if (!$name) {
            return 0;
        }
        $filter = 'WHERE RDB$RELATION_NAME=:tableName AND RDB$IDENTITY_TYPE=1';
        $query = sprintf('SELECT RDB$FIELD_NAME, RDB$GENERATOR_NAME FROM RDB$RELATION_FIELDS %s', $filter);
        $stmt = PDOConnection::getInstance()->getConnection()->prepare($query);
        $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (isset($row['RDB$GENERATOR_NAME'])) {
            $identityColumn = $row['RDB$GENERATOR_NAME'];
            $query = sprintf('SELECT GEN_ID(%s,0) AS LASTINSERTEDID FROM RDB$DATABASE', $identityColumn);
            $stmt = PDOConnection::getInstance()->getConnection()->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int) $result['LASTINSERTEDID'] : 0;
        }
        return false;
    }

    private static function lastInsertIdSQLite(string $name): int|false
    {
        if (!$name) {
            return PDOConnection::getInstance()->getConnection()->lastInsertId();
        }
        $query = "SELECT seq FROM sqlite_sequence WHERE name = :name";
        $stmt = PDOConnection::getInstance()->getConnection()->prepare($query);
        if (!$stmt) {
            return false;
        }
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return false;
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int) $result['seq'] : false;
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
        $driver = PDOConnection::getInstance()->getDriver();
        return match ($driver) {
            'mysql' => self::lastInsertIdMySQL($name),
            'pgsql' => self::lastInsertIdPgSQL($name),
            'sqlsrv' => self::lastInsertIdSQLSrv($name),
            'oci' => self::lastInsertIdOCI($name),
            'firebird' => self::lastInsertIdFirebird($name),
            'sqlite' => self::lastInsertIdSQLite($name),
            default => (int) PDOConnection::getInstance()->getConnection()->lastInsertId(),
        };
    }

    /**
     * Binds variables to a prepared statement with specified types.
     * This method binds variables to a prepared statement based on their types,
     * allowing for more precise parameter binding.
     *
     * @param array &$preparedParams An array containing the parameters to bind.
     * @param PDOStatement $statement The prepared statement to bind variables to.
     * @return PDOStatement The prepared statement with bound variables.
     */
    private static function internalBindVariable(array &$preparedParams, PDOStatement $statement): PDOStatement
    {
        $index = 0;
        foreach ($preparedParams as &$arg) {
            if (is_bool($arg)) {
                $types = PDO::PARAM_BOOL;
            } elseif (is_integer($arg)) {
                $types = PDO::PARAM_INT;
            } elseif (is_float($arg)) {
                $types = PDO::PARAM_STR;
            } elseif (is_string($arg)) {
                $types = PDO::PARAM_STR;
            } elseif (is_null($arg)) {
                $types = PDO::PARAM_NULL;
            } else {
                $types = PDO::PARAM_LOB;
            }
            call_user_func_array([$statement, 'bindParam'], [array_keys($preparedParams)[$index], &$arg, $types]);
            $index++;
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
            if (self::getStatement()->columnCount() > 0) {
                self::setQueryRows((int) count(self::getStatement()->fetchAll(PDO::FETCH_ASSOC)));
            } else {
                self::setAffectedRows((int) self::getStatement()->rowCount());
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
        self::setQueryColumns((int) self::getStatement()->columnCount());
    }

    /**
     * Parses an SQL statement and returns an statement.
     *
     * @param mixed ...$params The parameters for the query function.
     * @return string The statement resulting from the SQL statement.
     */
    public static function parse(mixed ...$params): string
    {
        $driver = PDOConnection::getInstance()->getDriver();
        $dialectQuote = match ($driver) {
            'mysql' => Translate::SQL_DIALECT_BACKTICK,
            'pgsql', 'sqlsrv', 'oci', 'firebird' => Translate::SQL_DIALECT_DOUBLE_QUOTE,
            default => Translate::SQL_DIALECT_NONE,
        };
        self::setQueryString(Translate::escape(reset($params), $dialectQuote));
        return self::getQueryString();
    }

    /**
     * This function binds the parameters to a prepared statement.
     *
     * @param mixed ...$params
     * @return PDOStatement|false
     */
    private static function prepareStatement(mixed ...$params): PDOStatement|false
    {
        self::setAllMetadata();
        if (!empty($params)) {
            $driver = PDOConnection::getInstance()->getDriver();
            $cursor = match ($driver) {
                'oci', 'mysql', 'pgsql' => [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY],
                'firebird', 'sqlsrv' => [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL],
                'sqlite' => [],
                default => [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY],
            };
            $statement = PDOConnection::getInstance()->getConnection()->prepare(self::parse(...$params), $cursor);
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
     * @return PDOConnection|null
     */
    public static function query(mixed ...$params): ?PDOConnection
    {
        if (!empty($params) && ($statement = self::prepareStatement(...$params)) && self::exec($statement)) {
            $driver = PDOConnection::getInstance()->getDriver();
            $colCount = $statement->columnCount();
            if ($colCount > 0) {
                self::setQueryColumns($colCount);
                self::setQueryRows(in_array($driver, ['oci', 'firebird', 'sqlite'])
                    ? (function (PDOStatement $stmt): int {
                        $rows = 0;
                        while ($stmt->fetch(PDO::FETCH_ASSOC)) {
                            $rows++;
                        }
                        self::exec($stmt);
                        return $rows;
                    })($statement)
                    : $statement->rowCount());
            } else {
                self::setAffectedRows($statement->rowCount());
            }
        }
        return PDOConnection::getInstance();
    }

    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return PDOConnection|null
     */
    public static function prepare(mixed ...$params): PDOConnection|null
    {
        if (!empty($params) && (self::prepareStatement(...$params))) {
            $bindParams = Schema::makeArgs([self::getStatement(), ...$params]);
            self::bindParam($bindParams);
        }
        return PDOConnection::getInstance();
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
