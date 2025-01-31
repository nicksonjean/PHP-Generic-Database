<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use ReflectionException;
use SensitiveParameter;
use AllowDynamicProperties;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\SQLite\Connection\SQLite;
use GenericDatabase\Engine\SQLite\Connection\Arguments;
use GenericDatabase\Engine\SQLite\Connection\Options;
use GenericDatabase\Engine\SQLite\Connection\Attributes;
use GenericDatabase\Engine\SQLite\Connection\DSN;
use GenericDatabase\Engine\SQLite\Connection\Dump;
use GenericDatabase\Engine\SQLite\Connection\Transaction;
use GenericDatabase\Engine\SQLite\Connection\Statements;
use GenericDatabase\Helpers\CustomException;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Translate;
use GenericDatabase\Shared\Setter;
use GenericDatabase\Shared\Getter;
use GenericDatabase\Shared\Cleaner;
use GenericDatabase\Shared\Singleton;
use SQLite3;
use Exception;
use stdClass;

/**
 * Dynamic and Static container class for SQLiteConnection connections.
 *
 * @method static SQLiteConnection|void setDriver(mixed $value): void
 * @method static SQLiteConnection|string getDriver($value = null): string
 * @method static SQLiteConnection|void setHost(mixed $value): void
 * @method static SQLiteConnection|string getHost($value = null): string
 * @method static SQLiteConnection|void setPort(mixed $value): void
 * @method static SQLiteConnection|int getPort($value = null): int
 * @method static SQLiteConnection|void setUser(mixed $value): void
 * @method static SQLiteConnection|string getUser($value = null): string
 * @method static SQLiteConnection|void setPassword(mixed $value): void
 * @method static SQLiteConnection|string getPassword($value = null): string
 * @method static SQLiteConnection|void setDatabase(mixed $value): void
 * @method static SQLiteConnection|string getDatabase($value = null): string
 * @method static SQLiteConnection|void setOptions(mixed $value): void
 * @method static SQLiteConnection|array|null getOptions($value = null): array|null
 * @method static SQLiteConnection|static setConnected(mixed $value): void
 * @method static SQLiteConnection|mixed getConnected($value = null): mixed
 * @method static SQLiteConnection|void setDsn(mixed $value): void
 * @method static SQLiteConnection|mixed getDsn($value = null): mixed
 * @method static SQLiteConnection|void setAttributes(mixed $value): void
 * @method static SQLiteConnection|mixed getAttributes($value = null): mixed
 * @method static SQLiteConnection|void setCharset(mixed $value): void
 * @method static SQLiteConnection|string getCharset($value = null): string
 * @method static SQLiteConnection|void setException(mixed $value): void
 * @method static SQLiteConnection|mixed getException($value = null): mixed
 */
#[AllowDynamicProperties]
class SQLiteConnection implements IConnection
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
     * Instance of the Statement of the database
     * @var mixed $statementCount = null
     */
    private static mixed $statementCount = null;

    /**
     * Instance of the Statement of the database
     * @var mixed $statementResult = null
     */
    private static mixed $statementResult = null;

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
     * @return SQLiteConnection|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): SQLiteConnection|string|int|bool|array|null
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
     * @return SQLiteConnection
     * @throws ReflectionException
     */
    public static function __callStatic(string $name, array $arguments): SQLiteConnection
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return SQLiteConnection
     * @throws ReflectionException
     */
    private function preConnect(): SQLiteConnection
    {
        Options::setOptions((array) static::getOptions());
        $options = Options::getOptions();
        static::setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return SQLiteConnection
     * @throws CustomException
     */
    private function postConnect(): SQLiteConnection
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the SQLiteConnection connection.
     *
     * @param mixed $database The path of the database file
     * @param int|null $flags = null Flags of the database behavior
     * @return SQLiteConnection
     * @throws Exception
     */
    private function realConnect(mixed $database, int $flags = null): SQLiteConnection
    {
        if (!$flags) {
            $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
        }
        $database = $database !== 'memory' ? $database : ':' . $database . ':';
        $this->setConnection(new SQLite3($database, $flags));
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return SQLiteConnection
     * @throws Exception
     */
    public function connect(): SQLiteConnection
    {
        if (!extension_loaded('sqlite3')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'sqlite3',
                'PHP.ini'
            );
            throw new CustomException($message);
        }

        try {
            $this->setInstance($this);
            $this
                ->preConnect()
                ->getInstance()
                ->setDsn($this->parseDsn())
                ->realConnect(
                    static::getDatabase(),
                    Options::flags()
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
            if (!Options::getOptions(SQLite::ATTR_PERSISTENT)) {
                if (
                    Compare::connection($this->getConnection()) === 'sqlite'
                    || Compare::connection($this->getConnection()) === 'sqlite3'
                ) {
                    $this->getConnection()->close();
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
        return (Compare::connection($this->getConnection()) === 'sqlite'
            || Compare::connection($this->getConnection()) === 'sqlite3') && $this->getInstance()->getConnected();
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

    /**
     * This function returns the last ID generated by an auto-increment column,
     *  either the last one inserted during the current transaction, or by passing in the optional name parameter.
     *
     * @param ?string $name = null Resource name, table or view
     * @return string|int|false
     */
    public function lastInsertId(?string $name = null): string|int|false
    {
        return (int) $this->getConnection()->lastInsertRowID() ?? 0;
    }

    /**
     * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
     *
     * @param mixed $params Content to be quoted
     * @return string|int
     */
    public function quote(mixed ...$params): string|int
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
     * Returns the statement for the function.
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
     * Returns the statement result for the function.
     *
     * @return mixed
     */
    public function getStatementResult(): mixed
    {
        return self::$statementResult;
    }

    /**
     * Set the statement for the function.
     *
     * @param mixed $statement The statement to be set.
     */
    public function setStatementResult(mixed $statement): void
    {
        self::$statementResult = $statement;
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
    private function internalBindVariable(array &$preparedParams, mixed $stmt): mixed
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
    private function internalBindParamArrayMulti(mixed ...$params): void
    {
        foreach ($params['sqlArgs'] as $param) {
            $statement = $this->internalBindVariable($param, $params['sqlStatement']);
            (!$params['rowCount'])
                ? $this->setStatement($this->exec($statement))
                : $this->setStatementResult($this->exec($statement));
            $this->setAffectedRows((int) $this->getConnection()->changes());
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
        $statement = $this->internalBindVariable($params['sqlArgs'], $params['sqlStatement']);
        (!$params['rowCount'])
            ? $this->setStatement($this->exec($statement))
            : $this->setStatementResult($this->exec($statement));
        $this->setAffectedRows((int) $this->getConnection()->changes());
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
     * @param mixed $params The name of the parameter or an array and args of parameters and values.
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
        $queryString = Translate::escape(reset($params), Translate::SQL_DIALECT_DOUBLE_QUOTE);
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
            $statement = $this->getConnection()->prepare($query);
            if ($statement) {
                $this->setStatement($statement);
                $queryParameters = $params[1] ?? [];
                $result = $statement->execute();
                if (is_object($result) && get_class($result) === 'SQLite3Result' && method_exists($result, 'numColumns')) {
                    $numColumns = $result->numColumns();
                    if ($numColumns > 0) {
                        $this->setQueryColumns($numColumns);
                        $results = [];
                        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                            $results[] = $row;
                        }
                        $this->setQueryRows(count($results));
                        $this->setStatement([
                            'results' => $results,
                            'queryString' => $query,
                            'queryParameters' => $queryParameters,
                        ]);
                        $result->reset();
                    } else {
                        $this->setAffectedRows($this->getConnection()->changes());
                        $this->setQueryRows(0);
                        $this->setQueryColumns(0);
                    }
                } else {
                    $this->setQueryRows(0);
                    $this->setQueryColumns(0);
                }
            }
        }

        return $this;
    }

    public function prepare(mixed ...$params): static|null
    {
        $driver = Compare::connection($this->getConnection());
        $this->setAllMetadata();
        if (!empty($params)) {
            $stmt = $this->getConnection()->prepare($this->parse(...$params));
            if ($stmt) {
                $this->setStatement($stmt);
                if (array_key_exists(1, $params) && is_array($params[1])) {
                    $bindParams = array_merge($this->makeArgs($driver, $stmt, ...$params), ['rowCount' => false]);
                    $this->setQueryParameters($bindParams['sqlArgs']);
                    if (isset($bindParams['sqlArgs']) && is_array($bindParams['sqlArgs'])) {
                        if (is_array($bindParams['sqlArgs']) && isset($bindParams['sqlArgs'][0]) && is_array($bindParams['sqlArgs'][0])) {
                            $affectedRows = 0;
                            foreach ($bindParams['sqlArgs'] as $args) {
                                $this->internalBindVariable($args, $stmt);
                                $result = $stmt->execute();
                                if ($result) {
                                    $affectedRows += $this->getConnection()->changes();
                                }
                            }
                            $this->setAffectedRows($affectedRows);
                        } else {
                            $this->internalBindVariable($bindParams['sqlArgs'], $stmt);
                            $result = $stmt->execute();
                            if ($result) {
                                $this->setAffectedRows($this->getConnection()->changes());
                                if (is_object($result) && get_class($result) === 'SQLite3Result' && method_exists($result, 'numColumns')) {
                                    $this->setQueryColumns($result->numColumns());
                                    $rowCount = 0;
                                    while ($result->fetchArray(SQLITE3_ASSOC)) {
                                        $rowCount++;
                                    }
                                    $this->setQueryRows($rowCount);
                                    $result->reset();
                                } else {
                                    $this->setQueryRows(0);
                                    $this->setQueryColumns(0);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return mixed
     */
    public function exec(mixed ...$params): mixed
    {
        return (reset($params) ?? $this->getStatement())->execute();
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
        $fetch = is_null($fetchStyle) ? Options::getOptions(SQLite::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            SQLite::FETCH_OBJ,
            SQLite::FETCH_INTO,
            SQLite::FETCH_CLASS => Statements::internalFetchClass($this->getStatement(), $fetchArgument, $optArgs),
            SQLite::FETCH_COLUMN => Statements::internalFetchColumn($this->getStatement(), $fetchArgument),
            SQLite::FETCH_ASSOC => Statements::internalFetchAssoc($this->getStatement()),
            SQLite::FETCH_NUM => Statements::internalFetchNum($this->getStatement()),
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
        $fetch = is_null($fetchStyle) ? Options::getOptions(SQLite::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            SQLite::FETCH_OBJ,
            SQLite::FETCH_INTO,
            SQLite::FETCH_CLASS =>
            Statements::internalFetchAllClass($this->getStatement(), $fetchArgument, $optArgs),
            SQLite::FETCH_COLUMN => Statements::internalFetchAllColumn($this->getStatement(), $fetchArgument),
            SQLite::FETCH_ASSOC => Statements::internalFetchAllAssoc($this->getStatement()),
            SQLite::FETCH_NUM => Statements::internalFetchAllNum($this->getStatement()),
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
        return SQLite::getAttribute($name);
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
        SQLite::setAttribute($name, $value);
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
        return $this->getConnection()->lastErrorCode() || $inst;
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
        return $this->getConnection()->lastErrorMsg() || $inst;
    }
}
