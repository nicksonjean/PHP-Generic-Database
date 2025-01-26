<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use ReflectionException;
use SensitiveParameter;
use AllowDynamicProperties;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\PgSQL\Connection\PgSQL;
use GenericDatabase\Engine\PgSQL\Connection\Arguments;
use GenericDatabase\Engine\PgSQL\Connection\Options;
use GenericDatabase\Engine\PgSQL\Connection\Attributes;
use GenericDatabase\Engine\PgSQL\Connection\DSN;
use GenericDatabase\Engine\PgSQL\Connection\Dump;
use GenericDatabase\Engine\PgSQL\Connection\Transaction;
use GenericDatabase\Engine\PgSQL\Connection\Statements;
use GenericDatabase\Helpers\CustomException;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Translate;
use GenericDatabase\Shared\Setter;
use GenericDatabase\Shared\Getter;
use GenericDatabase\Shared\Cleaner;
use GenericDatabase\Shared\Singleton;
use PgSql\Result;
use Exception;
use stdClass;

/**
 * Dynamic and Static container class for PgSQLConnection connections.
 *
 * @method static PgSQLConnection|void setDriver(mixed $value): void
 * @method static PgSQLConnection|string getDriver($value = null): string
 * @method static PgSQLConnection|void setHost(mixed $value): void
 * @method static PgSQLConnection|string getHost($value = null): string
 * @method static PgSQLConnection|void setPort(mixed $value): void
 * @method static PgSQLConnection|int getPort($value = null): int
 * @method static PgSQLConnection|void setUser(mixed $value): void
 * @method static PgSQLConnection|string getUser($value = null): string
 * @method static PgSQLConnection|void setPassword(mixed $value): void
 * @method static PgSQLConnection|string getPassword($value = null): string
 * @method static PgSQLConnection|void setDatabase(mixed $value): void
 * @method static PgSQLConnection|string getDatabase($value = null): string
 * @method static PgSQLConnection|void setOptions(mixed $value): void
 * @method static PgSQLConnection|array|null getOptions($value = null): array|null
 * @method static PgSQLConnection|static setConnected(mixed $value): void
 * @method static PgSQLConnection|mixed getConnected($value = null): mixed
 * @method static PgSQLConnection|void setDsn(mixed $value): void
 * @method static PgSQLConnection|mixed getDsn($value = null): mixed
 * @method static PgSQLConnection|void setAttributes(mixed $value): void
 * @method static PgSQLConnection|mixed getAttributes($value = null): mixed
 * @method static PgSQLConnection|void setCharset(mixed $value): void
 * @method static PgSQLConnection|string getCharset($value = null): string
 * @method static PgSQLConnection|void setException(mixed $value): void
 * @method static PgSQLConnection|mixed getException($value = null): mixed
 */
#[AllowDynamicProperties]
class PgSQLConnection implements IConnection
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
     * @return PgSQLConnection|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): PgSQLConnection|string|int|bool|array|null
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
     * @return PgSQLConnection
     * @throws ReflectionException
     */
    public static function __callStatic(string $name, array $arguments): PgSQLConnection
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return PgSQLConnection
     * @throws ReflectionException
     */
    private function preConnect(): PgSQLConnection
    {
        Options::setOptions((array) static::getOptions());
        $options = Options::getOptions();
        static::setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return PgSQLConnection
     * @throws CustomException
     */
    private function postConnect(): PgSQLConnection
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the PgSQLConnection connection.
     *
     * @param string $dsn The Data source name of the connection
     * @return PgSQLConnection
     * @throws Exception
     */
    private function realConnect(string $dsn): PgSQLConnection
    {
        $this->setConnection(
            (string) !Options::getOptions(PgSQL::ATTR_PERSISTENT)
            ? pg_connect($dsn, Attributes::getFlags())
            : pg_pconnect($dsn, Attributes::getFlags())
        );
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return PgSQLConnection
     * @throws Exception
     */
    public function connect(): PgSQLConnection
    {
        if (!extension_loaded('pgsql')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'pgsql',
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
                    $this->parseDsn()
                )
                ->postConnect()
                ->setConnected(true);
            return $this;
        } catch (Exception $error) {
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
        return $this->query('SELECT 1') !== false;
    }

    /**
     * Disconnects from a database.
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->isConnected()) {
            static::setConnected(false);
            if (!Options::getOptions(PgSQL::ATTR_PERSISTENT)) {
                if (Compare::connection($this->getConnection()) === 'pgsql') {
                    pg_close($this->getConnection());
                }
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
        return (Compare::connection($this->getConnection()) === 'pgsql') && $this->getInstance()->getConnected();
    }

    /**
     * This method is responsible for parsing the DSN from DSN class.
     *
     * @return string|CustomException
     * @throws CustomException
     */
    private function parseDsn(): string|CustomException
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
     * this transaction and restores the data to its original state.
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

    /**
     * This function returns the last ID generated by an auto-increment column.
     *
     * @param ?string $name = null Resource name, table or view
     * @return string|int|false
     */
    public function lastInsertId(?string $name = null): string|int|false
    {
        $statement = $this->getStatement();
        if ($statement instanceof \PgSql\Result) {
            return pg_last_oid($statement);
        }
        if ($name !== null) {
            $query = "SELECT column_name, column_default FROM information_schema.columns WHERE table_name = $1 AND column_default LIKE 'nextval%'";
            $result = pg_query_params($this->getConnection(), $query, [$name]);
            if ($result && ($row = pg_fetch_assoc($result))) {
                $seqName = preg_replace("/nextval\('(.+)'::regclass\)/", "$1", $row['column_default']);
                $result = pg_query($this->getConnection(), "SELECT currval('$seqName')");
                if ($result) {
                    $row = pg_fetch_row($result);
                    return $row ? (int) $row[0] : false;
                }
            }
        }
        $result = pg_query($this->getConnection(), "SELECT lastval()");
        if ($result) {
            $row = pg_fetch_row($result);
            return $row ? (int) $row[0] : false;
        }
        return false;
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
        $quote = $params[1] ?? false;
        if (is_array($string)) {
            return array_map(fn($str) => $this->quote($str, $quote), $string);
        } elseif ($string && preg_match("/^(?:\d+\.\d+|[1-9]\d*)$/S", (string) $string)) {
            return $string;
        }
        $quoted = fn($str) => pg_escape_string($this->getConnection(), (string) $str);
        return $quote ? "'" . $quoted($string) . "'" : $quoted($string);
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
    public function getStatement(): mixed
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
     * Binds an array multiple parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArrayMulti(mixed ...$params): void
    {
        foreach (Arrays::arrayValuesRecursive($params['sqlArgs']) as $param) {
            $this->exec($params['sqlStatement'], $param);
            $this->setAffectedRows(pg_affected_rows($this->getStatement()));
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
        $this->exec($params['sqlStatement'], array_values($params['sqlArgs']));
        $this->setAffectedRows(pg_affected_rows($this->getStatement()));
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArray(mixed ...$params): void
    {
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
        $this->exec($params['sqlStatement'], $params['sqlArgs']);
        $this->setAffectedRows(pg_affected_rows($this->getStatement()));
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
        $this->setQueryParameters($params['sqlArgs']);
        if ($params['isArray']) {
            $this->internalBindParamArray(...$params);
        } else {
            $this->internalBindParamArgs(...$params);
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
        $queryString = Translate::binding(
            Translate::escape(reset($params), Translate::SQL_DIALECT_DOUBLE_QUOTE),
            Translate::BIND_DOLLAR_SIGN
        );
        $this->setQueryString($queryString);
        return $this->getQueryString();
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return static|null
     */
    public function query(mixed ...$params): static|null
    {
        $this->setAllMetadata();
        if (!empty($params)) {
            $query = $this->parse(...$params);
            $result = pg_query($this->getConnection(), $query);
            if ($result) {
                $numFields = pg_num_fields($result);
                if ($numFields > 0) {
                    $results = [];
                    while ($row = pg_fetch_array($result, null, PGSQL_ASSOC)) {
                        $results[] = $row;
                    }
                    $this->setStatement(['results' => $results]);
                    $this->setQueryRows(pg_num_rows($result));
                    $this->setQueryColumns($numFields);
                    $this->setAffectedRows(0);
                } else {
                    $affectedRows = pg_affected_rows($result);
                    $this->setStatement(['results' => []]);
                    $this->setAffectedRows($affectedRows);
                    $this->setQueryRows(0);
                    $this->setQueryColumns(0);
                }
                pg_free_result($result);
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
        $driver = Compare::connection($this->getConnection());
        $this->setAllMetadata();
        if (!empty($params)) {
            $query = $this->parse(...$params);
            if (isset($params[1])) {
                $results = [];
                $affectedRows = 0;
                $numFields = 0;
                $paramSets = is_array($params[1][0] ?? null) ? $params[1] : [$params[1]];
                $bindParams = $this->makeArgs($driver, ...$params);
                $this->setQueryParameters($bindParams['sqlQuery']);
                foreach ($paramSets as $bindParams) {
                    $orderedParams = array_values($bindParams);
                    $result = pg_query_params($this->getConnection(), $query, $orderedParams);
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
                    $this->setStatement(['results' => $results]);
                    $this->setQueryRows(count($results));
                    $this->setQueryColumns($numFields);
                    $this->setAffectedRows(0);
                } else {
                    $this->setStatement(['results' => []]);
                    $this->setAffectedRows($affectedRows);
                    $this->setQueryRows(0);
                    $this->setQueryColumns(0);
                }
            }
        }
        return $this;
    }

    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return bool|Result
     */
    public function exec(mixed ...$params): bool|Result
    {
        if (!empty($params)) {
            $stmtName = 'stmt_' . md5(serialize($params));
            $query = $this->parse(...$params);
            $stmt = pg_prepare($this->getConnection(), $stmtName, $query);
            if ($stmt && isset($params[1])) {
                $orderedParams = array_values($params[1]);
                $result = pg_execute($this->getConnection(), $stmtName, $orderedParams);
                if ($result) {
                    $results = [];
                    if (pg_num_fields($result) > 0) {
                        while ($row = pg_fetch_array($result, null, PGSQL_BOTH)) {
                            $results[] = $row;
                        }
                    }
                    $this->setStatement(['results' => $results]);
                    $this->setAffectedRows(pg_affected_rows($result));
                    return $result;
                }
            }
        }
        return false;
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
        $fetch = is_null($fetchStyle) ? Options::getOptions(PgSQL::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            PgSQL::FETCH_OBJ,
            PgSQL::FETCH_INTO,
            PgSQL::FETCH_CLASS => Statements::internalFetchClass($this->getStatement(), $fetchArgument, $optArgs),
            PgSQL::FETCH_COLUMN => Statements::internalFetchColumn($this->getStatement(), $fetchArgument),
            PgSQL::FETCH_ASSOC => Statements::internalFetchAssoc($this->getStatement()),
            PgSQL::FETCH_NUM => Statements::internalFetchNum($this->getStatement()),
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
        $fetch = is_null($fetchStyle) ? Options::getOptions(PgSQL::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            PgSQL::FETCH_OBJ,
            PgSQL::FETCH_INTO,
            PgSQL::FETCH_CLASS =>
            Statements::internalFetchAllClass($this->getStatement(), $fetchArgument, $optArgs),
            PgSQL::FETCH_COLUMN => Statements::internalFetchAllColumn($this->getStatement(), $fetchArgument),
            PgSQL::FETCH_ASSOC => Statements::internalFetchAllAssoc($this->getStatement()),
            PgSQL::FETCH_NUM => Statements::internalFetchAllNum($this->getStatement()),
            default => Statements::internalFetchAllBoth($this->getStatement()),
        };
    }

    /**
     * This function retrieves an attribute from the database.
     *
     * @param mixed $name The attribute name
     * @return mixed
     * @throws ReflectionException
     */
    public function getAttribute(mixed $name): mixed
    {
        return PgSQL::getAttribute($name);
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $name The attribute name
     * @param mixed $value The attribute value
     * @return void
     * @throws ReflectionException
     */
    public function setAttribute(mixed $name, mixed $value): void
    {
        PgSQL::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param ?mixed $inst = null
     * @return string|bool
     * @noinspection PhpUnused
     */
    public function errorCode(mixed $inst = null): string|bool
    {
        return pg_last_error($this->getConnection()) ?: $inst;
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param ?mixed $inst = null
     * @return string|bool
     * @noinspection PhpUnused
     */
    public function errorInfo(mixed $inst = null): string|bool
    {
        return pg_last_error($this->getConnection()) ?: $inst;
    }
}
