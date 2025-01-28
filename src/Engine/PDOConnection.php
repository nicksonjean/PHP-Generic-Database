<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use ReflectionException;
use SensitiveParameter;
use AllowDynamicProperties;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\PDO\Connection\Arguments;
use GenericDatabase\Engine\PDO\Connection\Options;
use GenericDatabase\Engine\PDO\Connection\Attributes;
use GenericDatabase\Engine\PDO\Connection\DSN;
use GenericDatabase\Engine\PDO\Connection\Dump;
use GenericDatabase\Engine\PDO\Connection\Transaction;
use GenericDatabase\Engine\PDO\Connection\Statements;
use GenericDatabase\Helpers\CustomException;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Translate;
use GenericDatabase\Shared\Setter;
use GenericDatabase\Shared\Getter;
use GenericDatabase\Shared\Cleaner;
use GenericDatabase\Shared\Singleton;
use GenericDatabase\Helpers\Compare;
use PDO;
use PDOStatement;
use PDOException;
use Exception;
use stdClass;

/**
 * Dynamic and Static container class for PDOConnection connections.
 *
 * @method static PDOConnection|void setDriver(mixed $value): void
 * @method static PDOConnection|string getDriver($value = null): string
 * @method static PDOConnection|void setHost(mixed $value): void
 * @method static PDOConnection|string getHost($value = null): string
 * @method static PDOConnection|void setPort(mixed $value): void
 * @method static PDOConnection|int getPort($value = null): int
 * @method static PDOConnection|void setUser(mixed $value): void
 * @method static PDOConnection|string getUser($value = null): string
 * @method static PDOConnection|void setPassword(mixed $value): void
 * @method static PDOConnection|string getPassword($value = null): string
 * @method static PDOConnection|void setDatabase(mixed $value): void
 * @method static PDOConnection|string getDatabase($value = null): string
 * @method static PDOConnection|void setOptions(mixed $value): void
 * @method static PDOConnection|array|null getOptions($value = null): array|null
 * @method static PDOConnection|static setConnected(mixed $value): void
 * @method static PDOConnection|mixed getConnected($value = null): mixed
 * @method static PDOConnection|void setDsn(mixed $value): void
 * @method static PDOConnection|mixed getDsn($value = null): mixed
 * @method static PDOConnection|void setAttributes(mixed $value): void
 * @method static PDOConnection|mixed getAttributes($value = null): mixed
 * @method static PDOConnection|void setCharset(mixed $value): void
 * @method static PDOConnection|string getCharset($value = null): string
 * @method static PDOConnection|void setException(mixed $value): void
 * @method static PDOConnection|mixed getException($value = null): mixed
 */
#[AllowDynamicProperties]
class PDOConnection implements IConnection
{
    use Setter;
    use Getter;
    use Cleaner;
    use Singleton;

    /**
     * Instance of the connection with database
     * @var mixed $connection
     */
    private static mixed $connection;

    /**
     * Instance of the Statement of the database
     * @var mixed $statement = null
     */
    private static mixed $statement = null;

    /**
     * Count rows in query statement
     * @var ?int $queryRows = 0
     */
    private ?int $queryRows = 0;

    /**
     * Count columns in query statement
     * @var ?int $queryColumns = 0
     */
    private ?int $queryColumns = 0;

    /**
     * Affected row in query statement
     * @var ?int $affectedRows = 0
     */
    private ?int $affectedRows = 0;

    /**
     * Lasts params query executed
     * @var ?array $queryParameters = []
     */
    private ?array $queryParameters = [];

    /**
     * Last string query executed
     * @var string $queryString = ''
     */
    private string $queryString = '';

    /**
     * Empty constructor since initialization is handled by traits and interface methods
     */
    public function __construct()
    {
    }

    /**
     * Triggered when invoking inaccessible methods in an object context
     *
     * @param string $name Name of the method
     * @param array $arguments Array of arguments
     * @return PDOConnection|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): PDOConnection|string|int|bool|array|null
    {
        $method = substr($name, 0, 3);
        $field = mb_strtolower(substr($name, 3));
        if ($method == 'set') {
            $this->__set($field, ...$arguments);
        } elseif ($method == 'get') {
            return $this->__get($field);
        }
        return $this;
    }

    /**
     * Triggered when invoking inaccessible methods in a static context
     *
     * @param string $name Name of the static method
     * @param array $arguments Array of arguments
     * @return PDOConnection
     */
    public static function __callStatic(string $name, array $arguments): PDOConnection
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return PDOConnection
     * @throws CustomException
     */
    private function preConnect(): PDOConnection
    {
        Options::setOptions((array) static::getOptions());
        $options = Options::getOptions();
        static::setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return PDOConnection
     * @throws CustomException
     */
    private function postConnect(): PDOConnection
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the PDO connection.
     *
     * @param string $dsn The Data source name of the connection
     * @param ?string $user = null The user of the database
     * @param ?string $password = null The password of the database
     * @param ?array $options = null The options of the database
     * @return PDOConnection
     * @throws Exception
     */
    private function realConnect(
        string $dsn,
        mixed $user = null,
        #[SensitiveParameter] mixed $password = null,
        mixed $options = null
    ): PDOConnection {
        $this->setConnection(new PDO($dsn, $user, $password, $options));
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return PDOConnection
     * @throws PDOException|Exception
     */
    public function connect(): PDOConnection
    {
        if (!extension_loaded('pdo')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'pdo',
                'PHP.ini'
            );
            throw new CustomException($message);
        }

        try {
            $this->setInstance($this);
            $this
                ->preConnect()
                ->getInstance()
                ->realConnect(
                    $this->parseDsn(),
                    static::getUser(),
                    static::getPassword(),
                    static::getOptions()
                )
                ->postConnect()
                ->setConnected(true);
            return $this;
        } catch (PDOException | Exception $error) {
            $this->disconnect();
            die(Errors::throw($error));
        }
    }

    /**
     * Pings a server connection, or tries to reconnect if the connection has gone down
     *
     * @return bool
     */
    public function ping(): bool
    {
        $query = 'SELECT 1';
        if (static::getDriver() == 'oci') {
            $query .= ' FROM DUAL';
        } elseif (static::getDriver() == 'ibase' || static::getDriver() == 'firebird') {
            $query .= ' FROM RDB$DATABASE';
        }
        return $this->query($query) !== false;
    }

    /**
     * Disconnects from a database.
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->getConnection() !== null && $this->ping()) {
            static::setConnected(false);
            if (!$this->getAttribute(PDO::ATTR_PERSISTENT)) {
                $this->setConnection(null);
            }
        }
    }

    /**
     * Returns true when connection was established.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return ($this->getConnection() !== null) && $this->getInstance()->getConnected();
    }

    /**
     * This method is responsible for parsing the DSN from DSN class.
     *
     * @return string|Exception
     * @throws Exception
     */
    private function parseDsn(): string|Exception
    {
        return DSN::parse();
    }

    /**
     * This method is used to get the database connection instance
     *
     * @return mixed
     */
    public function getConnection(): mixed
    {
        return self::$connection;
    }

    /**
     * This method is used to assign the database connection instance
     *
     * @param mixed $connection Sets an instance of the connection with the database
     * @return mixed
     */
    public function setConnection(mixed $connection): mixed
    {
        self::$connection = $connection;
        return self::$connection;
    }

    /**
     * Import SQL dump from file - extremely fast.
     *
     * @param string $file The file dumped to be imported
     * @param string $delimiter = ';' The delimiter of the dump
     * @param ?callable $onProgress = null
     * @return int
     * @throws Exception
     */
    public function loadFromFile(string $file, string $delimiter = ';', ?callable $onProgress = null): int
    {
        return Dump::loadFromFile($file, $delimiter, $onProgress);
    }

    /**
     * This function creates a new transaction, in order to be able to commit or rollback changes made to the database.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return Transaction::beginTransaction();
    }

    /**
     * This function commits any changes made to the database during this transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        return Transaction::commit();
    }

    /**
     * This function rolls back any changes made to the database during
     *  this transaction and restores the data to its original state.
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return Transaction::rollback();
    }

    /**
     * This function returns the last ID generated by an auto-increment column,
     *  either the last one inserted during the current transaction, or by passing in the optional name parameter.
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return Transaction::inTransaction();
    }

    private function lastInsertIdMySQL(?string $name = null): string|int|false
    {
        if (!$name) {
            return (int) $this->getConnection()->lastInsertId();
        }
        $filter = "WHERE TABLE_NAME = :tableName AND COLUMN_KEY = :columnKey AND EXTRA = :extra";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = $this->getConnection()->prepare($query);
        $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
        $stmt->bindValue(':columnKey', 'PRI', PDO::PARAM_STR);
        $stmt->bindValue(':extra', 'auto_increment', PDO::PARAM_STR);
        $stmt->execute();
        $autoKey = $stmt->fetch(PDO::FETCH_ASSOC);
        if (isset($autoKey['COLUMN_NAME'])) {
            $query = sprintf("SELECT MAX(%s) AS value FROM %s", $autoKey['COLUMN_NAME'], $name);
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $maxIndex = $stmt->fetch(PDO::FETCH_ASSOC)['value'];
            if ($maxIndex !== null) {
                return (int) $maxIndex;
            }
        }
        return ($autoKey['COLUMN_NAME'] ? (int) $autoKey['COLUMN_NAME'] : 0) ?? false;
    }

    private function lastInsertIdPgSQL(?string $name = null): string|int|false
    {
        if (!$name) {
            return (int) $this->getConnection()->lastInsertId();
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
        $stmt = $this->getConnection()->prepare($query);
        $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
        $stmt->bindValue(':databaseName', static::getDatabase(), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['last_value']) && is_null($row['last_value']) && isset($row['name'])) {
            $seqName = $row['name'];
            $seqQuery = "SELECT currval(:seqName)";
            $seqStmt = $this->getConnection()->prepare($seqQuery);
            $seqStmt->bindValue(':seqName', $seqName);
            $seqStmt->execute();
            $seqResult = $seqStmt->fetch(PDO::FETCH_NUM);
            return $seqResult ? (int) $seqResult[0] : false;
        } elseif ($row && isset($row['last_value']) && !is_null($row['last_value'])) {
            return (int) $row['last_value'];
        }
        return false;
    }

    private function lastInsertIdSQLSrv(?string $name = null): string|int|false
    {
        if (!$name) {
            $query = "SELECT CAST(@@IDENTITY AS BIGINT) AS LastInsertedID";
            $stmt = $this->getConnection()->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int) $result['LastInsertedID'] : 0;
        }
        $filter = "WHERE TABLE_NAME = :tableName AND TABLE_SCHEMA = SCHEMA_NAME() AND COLUMNPROPERTY(OBJECT_ID(TABLE_SCHEMA + '.' + TABLE_NAME), COLUMN_NAME, 'IsIdentity') = 1";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = $this->getConnection()->prepare($query);
        $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (isset($row['COLUMN_NAME'])) {
            $identityColumn = $row['COLUMN_NAME'];
            $query = sprintf("SELECT MAX(%s) AS LastInsertedID FROM %s", $identityColumn, $name);
            $stmt = $this->getConnection()->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int) $result['LastInsertedID'] : 0;
        }
        return false;
    }

    private function lastInsertIdOCI(?string $name = null): string|int|false
    {
        if ($name !== null) {
            $filter = "WHERE OWNER = USER AND identity_column = 'YES' AND TABLE_NAME = :tableName";
            $seqQuery = sprintf("SELECT data_default AS sequence_val, table_name, column_name FROM all_tab_columns %s", $filter);
            $stmt = $this->getConnection()->prepare($seqQuery);
            $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
            if ($stmt->execute()) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['SEQUENCE_VAL'])) {
                    $sequenceVal = $row['SEQUENCE_VAL'];
                    $sequenceVal = str_replace('.nextval', '.currval', $sequenceVal);
                    $sequenceVal = str_replace('"', '', $sequenceVal);
                    $query = "SELECT $sequenceVal FROM DUAL";
                    $statement = $this->getConnection()->prepare($query);
                    if ($statement->execute()) {
                        $row = $statement->fetch(PDO::FETCH_NUM);
                        return $row ? (int) $row[0] : false;
                    }
                }
            }
        }
        return false;
    }

    private function lastInsertIdFirebird(?string $name = null): string|int|false
    {
        if (!$name) {
            return 0;
        }
        $filter = 'WHERE RDB$RELATION_NAME=:tableName AND RDB$IDENTITY_TYPE=1';
        $query = sprintf('SELECT RDB$FIELD_NAME, RDB$GENERATOR_NAME FROM RDB$RELATION_FIELDS %s', $filter);
        $stmt = $this->getConnection()->prepare($query);
        $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (isset($row['RDB$GENERATOR_NAME'])) {
            $identityColumn = $row['RDB$GENERATOR_NAME'];
            $query = sprintf('SELECT GEN_ID(%s,0) AS LASTINSERTEDID FROM RDB$DATABASE', $identityColumn);
            $stmt = $this->getConnection()->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int) $result['LASTINSERTEDID'] : 0;
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
    public function lastInsertId(?string $name = null): string|int|false
    {
        $driver = static::getDriver();
        return match ($driver) {
            'mysql' => $this->lastInsertIdMySQL($name),
            'pgsql' => $this->lastInsertIdPgSQL($name),
            'sqlsrv' => $this->lastInsertIdSQLSrv($name),
            'oci' => $this->lastInsertIdOCI($name),
            'firebird' => $this->lastInsertIdFirebird($name),
            'sqlite' => (int) $this->getConnection()->lastInsertId(),
            default => (int) $this->getConnection()->lastInsertId(),
        };
    }

    /**
     * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
     *
     * @param mixed $params Content to be quoted
     * @return mixed
     */
    public function quote(mixed ...$params): mixed
    {
        $string = $params[0];
        $type = (empty($params) || !isset($params[1])) ? PDO::PARAM_STR : $params[1];
        return $this->getConnection()->quote($string, $type);
    }

    /**
     * Reset query metadata
     *
     * @return void
     */
    private function setAllMetadata(): void
    {
        $this->queryString = '';
        $this->queryParameters = [];
        $this->queryRows = 0;
        $this->queryColumns = 0;
        $this->affectedRows = 0;
    }

    /**
     * Returns an object containing the number of queried rows and the number of affected rows.
     *
     * @return object An associative object with keys 'queryRows' and 'affectedRows'.
     */
    public function getAllMetadata(): object
    {
        $metadata = new stdClass();
        $metadata->queryString = $this->getQueryString();
        $metadata->queryParameters = $this->getQueryParameters();
        $metadata->queryRows = $this->getQueryRows();
        $metadata->queryColumns = $this->getQueryColumns();
        $metadata->affectedRows = $this->getAffectedRows();
        return $metadata;
    }

    /**
     * Returns the query string.
     *
     * @return string The query string associated with this instance.
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }

    /**
     * Sets the query string for the Connection instance.
     *
     * @param string $params The query string to set.
     */
    public function setQueryString(string $params): void
    {
        $this->queryString = $params;
    }

    /**
     * Returns the parameters associated with this instance.
     *
     * @return array|null The parameters associated with this instance.
     */
    public function getQueryParameters(): ?array
    {
        return $this->queryParameters;
    }

    /**
     * Sets the query parameters for the Connection instance.
     *
     * @param array|null $params The query parameters to set.
     */
    public function setQueryParameters(?array $params): void
    {
        $this->queryParameters = $params;
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public function getQueryRows(): int|false
    {
        return $this->queryRows;
    }

    /**
     * Sets the number of query rows for the Connection instance.
     *
     * @param callable|int|false $params The number of query rows to set.
     * @return void
     */
    public function setQueryRows(callable|int|false $params): void
    {
        $this->queryRows = $params;
    }

    /**
     * Returns the number of columns in a statement result.
     *
     * @return int|false The number of columns in the result or false in case of an error.
     */
    public function getQueryColumns(): int|false
    {
        return $this->queryColumns;
    }

    /**
     * Sets the number of columns in the query result.
     *
     * @param int|false $params The number of columns or false if there are no columns.
     * @return void
     */
    public function setQueryColumns(int|false $params): void
    {
        $this->queryColumns = $params;
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public function getAffectedRows(): int|false
    {
        return $this->affectedRows;
    }

    /**
     * Sets the number of affected rows for the Connection instance.
     *
     * @param int|false $params The number of affected rows to set.
     * @return void
     */
    public function setAffectedRows(int|false $params): void
    {
        $this->affectedRows = $params;
    }

    /**
     * A description of the entire PHP function.
     *
     * @return mixed
     */
    public function getStatement(): PDOStatement
    {
        return self::$statement;
    }

    /**
     * Set the statement for the function.
     *
     * @param mixed $statement The statement to be set.
     */
    public function setStatement(mixed $statement): void
    {
        self::$statement = $statement;
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
    private function internalBindVariable(array &$preparedParams, PDOStatement $stmt): PDOStatement
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
    private function internalBindParamArrayMulti(mixed ...$params): void
    {
        $affectedRows = 0;
        foreach ($params['sqlArgs'] as $param) {
            $statement = $this->internalBindVariable($param, $params['sqlStatement']);
            $this->exec($statement);
            $affectedRows++;
            if ($this->getQueryColumns() === 0) {
                $this->setAffectedRows((int) $affectedRows);
            }
        }
    }

    /**
     * Binds an array single parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArraySingle(mixed ...$params): void
    {
        $this->internalBindParamArgs(...$params);
    }

    /**
     * Binds an array parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArray(mixed ...$params): void
    {
        $this->setQueryParameters($params['sqlArgs']);
        if ($params['isMulti']) {
            $this->internalBindParamArrayMulti(...$params);
        } else {
            $this->internalBindParamArraySingle(...$params);
        }
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an args of parameters and values.
     * @return void
     */
    private function internalBindParamArgs(mixed ...$params): void
    {
        $statement = $this->internalBindVariable($params['sqlArgs'], $params['sqlStatement']);
        $this->exec($statement);
        if ($this->getQueryColumns() > 0) {
            $this->setQueryRows((int) count($this->getStatement()->fetchAll(PDO::FETCH_ASSOC)));
        }
    }

    /**
     * This function makes an arguments list
     *
     * @param mixed $params Arguments list
     * @param mixed $driver Driver name
     * @return array
     */
    private function makeArgs(mixed $driver, mixed ...$params): array
    {
        return Arrays::makeArgs($driver, ...$params);
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    public function bindParam(mixed ...$params): void
    {
        if ($params['isArray']) {
            $this->internalBindParamArray(...$params);
        } else {
            $this->internalBindParamArgs(...$params);
        }
        $this->setQueryColumns((int) $this->getStatement()->columnCount());
        if ($this->getQueryColumns() > 0) {
            $this->setQueryRows((int) count($this->getStatement()->fetchAll(PDO::FETCH_ASSOC)));
        } else {
            if (!$params['isMulti']) {
                $this->setAffectedRows((int) $this->getStatement()->rowCount());
            }
        }
    }

    /**
     * Parses an SQL statement and returns an statement.
     *
     * @param mixed ...$params The parameters for the query function.
     * @return string The statement resulting from the SQL statement.
     */
    private function parse(mixed ...$params): string
    {
        $driver = static::getDriver();
        $dialectQuote = match ($driver) {
            'mysql' => Translate::SQL_DIALECT_BACKTICK,
            'pgsql', 'sqlsrv', 'oci', 'firebird' => Translate::SQL_DIALECT_DOUBLE_QUOTE,
            'sqlite' => Translate::SQL_DIALECT_NONE,
            default => Translate::SQL_DIALECT_NONE,
        };
        $this->setQueryString(Translate::escape(reset($params), $dialectQuote));
        return $this->getQueryString();
    }

    /**
     * This function binds the parameters to a prepared statement.
     *
     * @param mixed ...$params
     * @return PDOStatement|false
     */
    private function prepareStatement(mixed ...$params): PDOStatement|false
    {
        $this->setAllMetadata();
        if (!empty($params)) {

            $cursor = match (static::getDriver()) {
                'oci', 'mysql', 'pgsql' => [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY],
                'firebird', 'sqlsrv' => [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL],
                'sqlite' => [],
                default => [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY],
            };

            $statement = $this->getConnection()->prepare($this->parse(...$params), $cursor);
            if ($statement) {
                $this->setStatement($statement);
            }

        }
        return $statement;
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return static|null
     */
    public function query(mixed ...$params): static|null
    {
        $statement = $this->prepareStatement(...$params);
        if ($statement && $this->exec($statement)) {
            $this->setQueryColumns((int) $this->getStatement()->columnCount());
            if ($this->getQueryColumns() > 0) {
                $this->setQueryRows((int) count($this->getStatement()->fetchAll(PDO::FETCH_ASSOC)));
            } else {
                $this->setAffectedRows($this->getStatement()->rowCount());
            }
        }
        return $this;
    }

    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return static|null
     */
    public function prepare(mixed ...$params): static|null
    {
        $statement = $this->prepareStatement(...$params);
        $driver = static::getDriver();
        if ($statement) {
            if ($driver === 'sqlsrv') {
                $bindParams = $this->makeArgs($driver, ...$params);
                $bindParams['sqlStatement'] = $this->getStatement();
            } else {
                array_unshift($params, $this->getStatement());
                $bindParams = $this->makeArgs($driver, ...$params);
            }
            $this->bindParam(...$bindParams);
            $this->setQueryParameters($bindParams['sqlArgs']);
        }
        return $this;
    }

    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return bool
     */
    public function exec(mixed ...$params): bool
    {
        $stmt = reset($params);
        return $stmt->execute();
    }

    /**
     * Fetches the next row from the statement and returns it as an array.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed $fetchArgument From the Fetch Into or Fetch Class.
     * @param mixed $optArgs From the Fetch Into or Fetch Class.
     * @return mixed The next row from the statement as an array, or false if there are no more rows.
     * @throws ReflectionException
     */
    public function fetch(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        return match ($fetchStyle) {
            PDO::FETCH_OBJ,
            PDO::FETCH_INTO,
            PDO::FETCH_CLASS => Statements::internalFetchClass($this->getStatement(), $fetchArgument, $optArgs),
            PDO::FETCH_COLUMN => Statements::internalFetchColumn($this->getStatement(), $fetchArgument),
            PDO::FETCH_ASSOC => Statements::internalFetchAssoc($this->getStatement()),
            PDO::FETCH_NUM => Statements::internalFetchNum($this->getStatement()),
            default => Statements::internalFetchBoth($this->getStatement()),
        };
    }

    /**
     * Fetches all rows from the statement and returns them as an array.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed $fetchArgument From the Fetch Into or Fetch Class.
     * @param mixed $optArgs From the Fetch Into or Fetch Class.
     * @return array|bool The next row from the statement as an array, or false if there are no more rows.
     * @throws ReflectionException
     */
    public function fetchAll(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        return match ($fetchStyle) {
            PDO::FETCH_OBJ,
            PDO::FETCH_INTO,
            PDO::FETCH_CLASS => Statements::internalFetchAllClass($this->getStatement(), $fetchArgument, $optArgs),
            PDO::FETCH_COLUMN => Statements::internalFetchAllColumn($this->getStatement(), $fetchArgument),
            PDO::FETCH_ASSOC => Statements::internalFetchAllAssoc($this->getStatement()),
            PDO::FETCH_NUM => Statements::internalFetchAllNum($this->getStatement()),
            default => Statements::internalFetchAllBoth($this->getStatement()),
        };
    }

    /**
     * This function retrieves an attribute from the database.
     *
     * @param mixed $name The attribute name
     * @return mixed
     */
    public function getAttribute(mixed $name): mixed
    {
        return $this->getConnection()->getAttribute($name);
    }

    /**
     * This function sets an attribute on the database.
     *
     * @param mixed $name The attribute name
     * @param mixed $value The attribute value
     * @return void
     */
    public function setAttribute(mixed $name, mixed $value): void
    {
        $this->getConnection()->setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return int|bool
     * @noinspection PhpUnused
     */
    public function errorCode(mixed $inst = null): int|bool
    {
        return $this->getConnection()->errorCode() || $inst;
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return string|bool
     * @noinspection PhpUnused
     */
    public function errorInfo(mixed $inst = null): string|bool
    {
        return $this->getConnection()->errorInfo() || $inst;
    }
}
