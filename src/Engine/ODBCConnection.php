<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use ReflectionException;
use SensitiveParameter;
use AllowDynamicProperties;
use Exception;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\ODBC\Connection\ODBC;
use GenericDatabase\Engine\ODBC\Connection\Arguments;
use GenericDatabase\Engine\ODBC\Connection\Options;
use GenericDatabase\Engine\ODBC\Connection\Attributes;
use GenericDatabase\Engine\ODBC\Connection\DSN;
use GenericDatabase\Engine\ODBC\Connection\Dump;
use GenericDatabase\Engine\ODBC\Connection\Transaction;
use GenericDatabase\Engine\ODBC\Connection\Statements;
use GenericDatabase\Helpers\CustomException;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Translate;
use GenericDatabase\Helpers\Validations;
use GenericDatabase\Shared\Setter;
use GenericDatabase\Shared\Getter;
use GenericDatabase\Shared\Cleaner;
use GenericDatabase\Shared\Singleton;
use stdClass;

/**
 * Dynamic and Static container class for ODBCConnection connections.
 *
 * @method static ODBCConnection|void setDriver(mixed $value): void
 * @method static ODBCConnection|string getDriver($value = null): string
 * @method static ODBCConnection|void setHost(mixed $value): void
 * @method static ODBCConnection|string getHost($value = null): string
 * @method static ODBCConnection|void setPort(mixed $value): void
 * @method static ODBCConnection|int getPort($value = null): int
 * @method static ODBCConnection|void setUser(mixed $value): void
 * @method static ODBCConnection|string getUser($value = null): string
 * @method static ODBCConnection|void setPassword(mixed $value): void
 * @method static ODBCConnection|string getPassword($value = null): string
 * @method static ODBCConnection|void setDatabase(mixed $value): void
 * @method static ODBCConnection|string getDatabase($value = null): string
 * @method static ODBCConnection|void setOptions(mixed $value): void
 * @method static ODBCConnection|array|null getOptions($value = null): array|null
 * @method static ODBCConnection|static setConnected(mixed $value): void
 * @method static ODBCConnection|mixed getConnected($value = null): mixed
 * @method static ODBCConnection|void setDsn(mixed $value): void
 * @method static ODBCConnection|mixed getDsn($value = null): mixed
 * @method static ODBCConnection|void setAttributes(mixed $value): void
 * @method static ODBCConnection|mixed getAttributes($value = null): mixed
 * @method static ODBCConnection|void setCharset(mixed $value): void
 * @method static ODBCConnection|string getCharset($value = null): string
 * @method static ODBCConnection|void setException(mixed $value): void
 * @method static ODBCConnection|mixed getException($value = null): mixed
 */
#[AllowDynamicProperties]
class ODBCConnection implements IConnection
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
     * @var mixed $statementResult = null
     */
    private static mixed $statementResult = null;

    /**
     * Instance of the Statement of the database
     * @var mixed $statementCount = null
     */
    private static mixed $statementCount = null;

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
     * @return ODBCConnection|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): ODBCConnection|string|int|bool|array|null
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
     * @return ODBCConnection
     * @throws ReflectionException
     */
    public static function __callStatic(string $name, array $arguments): ODBCConnection
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return ODBCConnection
     * @throws ReflectionException
     */
    private function preConnect(): ODBCConnection
    {
        Options::setOptions((array) static::getOptions());
        $options = Options::getOptions();
        static::setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return ODBCConnection
     * @throws CustomException
     */
    private function postConnect(): ODBCConnection
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the ODBC connection.
     *
     * @param string $dsn The Data source name of the connection
     * @param ?string $user = null The user of the database
     * @param ?string $password = null The password of the database
     * @param int|null $options
     * @return ODBCConnection
     * @throws ReflectionException
     */
    private function realConnect(
        string $dsn,
        string $user = null,
        #[SensitiveParameter] string $password = null,
        int $options = null
    ): ODBCConnection {
        $this->setConnection((!Options::getOptions(ODBC::ATTR_PERSISTENT) || $this->getDriver() === 'mysql') ?
            odbc_connect($dsn, (string) $user, (string) $password, $options) :
            odbc_pconnect($dsn, (string) $user, (string) $password, $options));
        if (!Options::getOptions(ODBC::ATTR_PERSISTENT) || $this->getDriver() === 'mysql') {
            $nonPersistent = [];
            foreach (Options::getOptions() as $key => $value) {
                if ($key !== ODBC::ATTR_PERSISTENT) {
                    $nonPersistent[$key] = $value;
                }
            }
            $nonPersistent[ODBC::ATTR_PERSISTENT] = false;
            Options::setOptions($nonPersistent);
        }
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return ODBCConnection
     * @throws Exception
     */
    public function connect(): ODBCConnection
    {
        if (!extension_loaded('odbc')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'odbc',
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
                    0
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
     * @throws ReflectionException
     */
    public function disconnect(): void
    {
        if ($this->getConnection() !== null && $this->ping()) {
            static::setConnected(false);
            if (!$this->getAttribute(ODBC::ATTR_PERSISTENT)) {
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

    /**
     * This function returns the last ID generated by an auto-increment column,
     *  either the last one inserted during the current transaction, or by passing in the optional name parameter.
     *
     * @param ?string $name = null Resource name, table or view
     * @return string|int|false
     */
    public function lastInsertId(?string $name = null): string|int|false
    {
        return $this->getConnection()->lastInsertId($name);
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
        $this->queryRows = (function () use ($params) {
            $this->bindParam(...self::$statementCount);
            return $params();
        });
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
        $this->affectedRows = $this->getAffectedRows() + $params;
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
     * @param mixed $data The prepared statement to bind variables to.
     * @return mixed The prepared statement with bound variables.
     */
    private function internalBindVariable(mixed $data): mixed
    {
        return Validations::detectTypes($data);
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
            $referenceParams = array_values($param);
            (!$params['rowCount'])
                ? $this->setStatement($this->exec($params['sqlStatement'], $referenceParams))
                : $this->setStatementResult($this->exec($params['sqlStatement'], $referenceParams));
            $this->setAffectedRows(odbc_num_rows($this->getStatement()));
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
        $referenceParams = array_values($params['sqlArgs']);
        $this->exec($params['sqlStatement'], $referenceParams);
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
        $driver = static::getDriver();
        $dialectQuote = match ($driver) {
            'mysql' => Translate::SQL_DIALECT_BACKTICK,
            'pgsql', 'sqlsrv', 'oci', 'firebird' => Translate::SQL_DIALECT_DOUBLE_QUOTE,
            'sqlite' => Translate::SQL_DIALECT_SINGLE_QUOTE,
            default => Translate::SQL_DIALECT_NONE,
        };
        $this->setQueryString(Translate::binding(Translate::escape(reset($params), $dialectQuote)));
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
            $this->setStatement(odbc_exec($this->getConnection(), $this->parse(...$params)));
            $rowCount = $params;
            $this->setStatementResult(odbc_prepare($this->getConnection(), $this->parse(...$params)));
            array_unshift($rowCount, $this->getStatementResult());
            array_unshift($params, $this->getStatement());
            self::$statementCount = array_merge($this->makeArgs('', ...$rowCount), ['rowCount' => true]);
            $this->setQueryRows(fn() => count(Statements::internalFetchAllAssoc(
                self::$statementCount['sqlStatement']
            )));
            $this->setQueryColumns(odbc_num_fields($this->getStatement()));
            $this->setAffectedRows(odbc_num_rows($this->getStatement()));
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
        $this->setAllMetadata();
        if (!empty($params)) {
            $this->setStatement(odbc_prepare($this->getConnection(), $this->parse(...$params)));
            $rowCount = $params;
            array_unshift($rowCount, odbc_prepare($this->getConnection(), $this->parse(...$params)));
            array_unshift($params, $this->getStatement());
            $bindParams = array_merge($this->makeArgs('', ...$params), ['rowCount' => false]);
            self::$statementCount = array_merge($this->makeArgs('', ...$rowCount), ['rowCount' => true]);
            $this->bindParam(...$bindParams);
            $this->setQueryRows(fn() => count(Statements::internalFetchAllAssoc(
                self::$statementCount['sqlStatement']
            )));
            $this->setQueryColumns(odbc_num_fields($this->getStatement()));
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
        $statement = reset($params);
        $data = $params[1] ?? false;
        $data = $this->internalBindVariable($data);
        return odbc_execute($statement, $data);
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
        $fetch = is_null($fetchStyle) ? Options::getOptions(ODBC::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            ODBC::FETCH_OBJ,
            ODBC::FETCH_INTO,
            ODBC::FETCH_CLASS => Statements::internalFetchClass($this->getStatement(), $fetchArgument, $optArgs),
            ODBC::FETCH_COLUMN => Statements::internalFetchColumn($this->getStatement(), $fetchArgument),
            ODBC::FETCH_ASSOC => Statements::internalFetchAssoc($this->getStatement()),
            ODBC::FETCH_NUM => Statements::internalFetchNum($this->getStatement()),
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
        $fetch = is_null($fetchStyle) ? Options::getOptions(ODBC::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            ODBC::FETCH_OBJ,
            ODBC::FETCH_CLASS => Statements::internalFetchAllClass($this->getStatement(), $fetchArgument, $optArgs),
            ODBC::FETCH_COLUMN => Statements::internalFetchAllColumn($this->getStatement(), $fetchArgument),
            ODBC::FETCH_ASSOC => Statements::internalFetchAllAssoc($this->getStatement()),
            ODBC::FETCH_NUM => Statements::internalFetchAllNum($this->getStatement()),
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
        return ODBC::getAttribute($name);
    }

    /**
     * This function sets an attribute from the database.
     *
     * @param mixed $name The attribute name
     * @param mixed $value The attribute value
     * @return void
     * @throws ReflectionException
     */
    public function setAttribute(mixed $name, mixed $value): void
    {
        ODBC::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return string
     */
    public function errorCode(mixed $inst = null): string
    {
        return odbc_error($this->getConnection()) ?: $inst;
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return array|bool
     * @noinspection PhpUnused
     */
    public function errorInfo(mixed $inst = null): array|bool
    {
        $errorCode = $this->errorCode() || $inst;
        $result = false;
        if ($errorCode) {
            $result = [
                'code' => $this->errorCode(),
                'message' => odbc_errormsg($this->getConnection()),
            ];
        }
        return $result;
    }
}
