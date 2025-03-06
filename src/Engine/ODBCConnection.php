<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use Exception;
use SensitiveParameter;
use ReflectionException;
use AllowDynamicProperties;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Shared\Singleton;
use GenericDatabase\Generic\Connection\Methods;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IDSN;
use GenericDatabase\Interfaces\Connection\IFetch;
use GenericDatabase\Interfaces\Connection\IStatements;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Interfaces\Connection\IArguments;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\Connection\ITransactions;
use GenericDatabase\Engine\ODBC\Connection\ODBC;
use GenericDatabase\Engine\ODBC\Connection\DSN\DSNHandler;
use GenericDatabase\Engine\ODBC\Connection\Fetch\FetchHandler;
use GenericDatabase\Engine\ODBC\Connection\Options\OptionsHandler;
use GenericDatabase\Engine\ODBC\Connection\Attributes\AttributesHandler;
use GenericDatabase\Engine\ODBC\Connection\Fetch\Strategy\FetchStrategy;
use GenericDatabase\Engine\ODBC\Connection\Statements\StatementsHandler;
use GenericDatabase\Engine\ODBC\Connection\Arguments\ArgumentsHandler;
use GenericDatabase\Engine\ODBC\Connection\Arguments\Strategy\ArgumentsStrategy;
use GenericDatabase\Engine\ODBC\Connection\Transactions\TransactionsHandler;
use GenericDatabase\Engine\ODBC\Connection\Report\ReportHandler;

/**
 * Dynamic and Static container class for ODBCConnection connections.
 *
 * @method static ODBCConnection|void setDriver(mixed $value): Sets a driver from the database.
 * @method static ODBCConnection|string getDriver($value = null): Retrieves a driver from the database.
 * @method static ODBCConnection|void setHost(mixed $value): Sets a host from the database.
 * @method static ODBCConnection|string getHost($value = null): Retrieves a host from the database.
 * @method static ODBCConnection|void setPort(mixed $value): Sets a port from the database.
 * @method static ODBCConnection|int getPort($value = null): Retrieves a port from the database.
 * @method static ODBCConnection|void setUser(mixed $value): Sets a user from the database.
 * @method static ODBCConnection|string getUser($value = null): Retrieves a user from the database.
 * @method static ODBCConnection|void setPassword(mixed $value): Sets a password from the database.
 * @method static ODBCConnection|string getPassword($value = null): Retrieves a password from the database.
 * @method static ODBCConnection|void setDatabase(mixed $value): Sets a database name from the database.
 * @method static ODBCConnection|string getDatabase($value = null): Retrieves a database name from the database.
 * @method static ODBCConnection|void setOptions(mixed $value): Sets a options from the database.
 * @method static ODBCConnection|array|null getOptions($value = null): Retrieves a options from the database.
 * @method static ODBCConnection|static setConnected(mixed $value): Sets a connected status from the database.
 * @method static ODBCConnection|mixed getConnected($value = null): Retrieves a connected status from the database.
 * @method static ODBCConnection|void setDsn(mixed $value): Sets a dsn string from the database.
 * @method static ODBCConnection|mixed getDsn($value = null): Retrieves a dsn string from the database.
 * @method static ODBCConnection|void setAttributes(mixed $value): Sets a attributes from the database.
 * @method static ODBCConnection|mixed getAttributes($value = null): Retrieves a attributes from the database.
 * @method static ODBCConnection|void setCharset(mixed $value): Sets a charset from the database.
 * @method static ODBCConnection|string getCharset($value = null): Retrieves a charset from the database.
 * @method static ODBCConnection|void setException(mixed $value): Sets a exception from the database.
 * @method static ODBCConnection|mixed getException($value = null): Retrieves a exception from the database.
 */
#[AllowDynamicProperties]
class ODBCConnection implements IConnection, IFetch, IStatements, IDSN, IArguments, ITransactions
{
    use Methods;
    use Singleton;

    /**
     * Instance of the connection with database
     * @var mixed $connection
     */
    private static mixed $connection;

    private static IFetch $fetchHandler;

    private static IStatements $statementsHandler;

    private static IDSN $dsnHandler;

    private static IAttributes $attributesHandler;

    private static IOptions $optionsHandler;

    private static IArguments $argumentsHandler;

    private static ITransactions $transactionsHandler;

    /**
     * Empty constructor since initialization is handled by traits and interface methods
     */
    public function __construct()
    {
        self::$fetchHandler = new FetchHandler($this, new FetchStrategy());
        self::$optionsHandler = new OptionsHandler($this);
        self::$dsnHandler = new DSNHandler($this);
        self::$statementsHandler = new StatementsHandler($this, self::$optionsHandler, new ReportHandler());
        self::$attributesHandler = new AttributesHandler($this, self::$optionsHandler);
        self::$argumentsHandler = new ArgumentsHandler($this, self::$optionsHandler, new ArgumentsStrategy());
        self::$transactionsHandler = new TransactionsHandler($this);
    }

    private function getFetchHandler(): IFetch
    {
        return self::$fetchHandler;
    }

    private function getStatementsHandler(): IStatements
    {
        return self::$statementsHandler;
    }

    private function getDsnHandler(): IDSN
    {
        return self::$dsnHandler;
    }

    private function getAttributesHandler(): IAttributes
    {
        return self::$attributesHandler;
    }

    private function getOptionsHandler(): IOptions
    {
        return self::$optionsHandler;
    }

    private function getArgumentsHandler(): IArguments
    {
        return self::$argumentsHandler;
    }

    private function getTransactionsHandler(): ITransactions
    {
        return self::$transactionsHandler;
    }

    /**
     * Triggered when invoking inaccessible methods in an object context
     *
     * @param string $name Name of the method
     * @param array $arguments Array of arguments
     * @return IConnection|string|int|bool|array|null
     * @throws ReflectionException
     */
    public function __call(string $name, array $arguments): IConnection|string|int|bool|array|null
    {
        return $this->getArgumentsHandler()->__call($name, $arguments);
    }

    /**
     * Triggered when invoking inaccessible methods in a static context
     *
     * @param string $name Name of the static method
     * @param array $arguments Array of arguments
     * @return IConnection|string|int|bool|array|null
     * @throws ReflectionException
     */
    public static function __callStatic(string $name, array $arguments): IConnection|string|int|bool|array|null
    {
        return self::getInstance()->getArgumentsHandler()->__callStatic($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return ODBCConnection
     */
    private function preConnect(): ODBCConnection
    {
        $this->getOptionsHandler()->setOptions(static::getOptions());
        static::setOptions($this->getOptionsHandler()->getOptions());
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return ODBCConnection
     */
    private function postConnect(): ODBCConnection
    {
        $this->getOptionsHandler()->define();
        $this->getAttributesHandler()->define();
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
     */
    private function realConnect(
        string $dsn,
        string $user = null,
        #[SensitiveParameter] string $password = null,
        int $options = null
    ): ODBCConnection {
        $this->setConnection((!$this->getOptionsHandler()->getOptions(ODBC::ATTR_PERSISTENT) || $this->getDriver() === 'mysql') ?
            odbc_connect($dsn, (string) $user, (string) $password, $options) :
            odbc_pconnect($dsn, (string) $user, (string) $password, $options));
        if (!$this->getOptionsHandler()->getOptions(ODBC::ATTR_PERSISTENT) || $this->getDriver() === 'mysql') {
            $nonPersistent = [];
            foreach ($this->getOptionsHandler()->getOptions() as $key => $value) {
                if ($key !== ODBC::ATTR_PERSISTENT) {
                    $nonPersistent[$key] = $value;
                }
            }
            $nonPersistent[ODBC::ATTR_PERSISTENT] = false;
            $this->getOptionsHandler()->setOptions($nonPersistent);
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
            throw new Exceptions("Invalid or not loaded 'odbc' extension in PHP.ini settings");
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
        return $this->getDsnHandler()->parse();
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
     * This function creates a new transaction, in order to be able to commit or rollback changes made to the database.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->getTransactionsHandler()->beginTransaction();
    }

    /**
     * This function commits any changes made to the database during this transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->getTransactionsHandler()->commit();
    }

    /**
     * This function rolls back any changes made to the database during
     * this transaction and restores the data to its original state.
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->getTransactionsHandler()->rollback();
    }

    /**
     * This function returns the last ID generated by an auto-increment column,
     * either the last one inserted during the current transaction, or by passing in the optional name parameter.
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getTransactionsHandler()->inTransaction();
    }

    /**
     * This function returns the last ID generated by an auto-increment column,
     * either the last one inserted during the current transaction, or by passing in the optional name parameter.
     *
     * @param ?string $name = null Resource name, table or view
     * @return string|int|false
     */
    public function lastInsertId(?string $name = null): string|int|false
    {
        return $this->getStatementsHandler()->lastInsertId($name);
    }

    /**
     * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
     *
     * @param mixed $params Content to be quoted
     * @return string|int
     */
    public function quote(mixed ...$params): string|int
    {
        return $this->getStatementsHandler()->quote(...$params);
    }

    /**
     * Reset query metadata
     *
     * @return void
     */
    public function setAllMetadata(): void
    {
        $this->getStatementsHandler()->setAllMetadata();
    }

    /**
     * Returns an object containing the number of queried rows and the number of affected rows.
     *
     * @return object An associative object with keys 'queryRows' and 'affectedRows'.
     */
    public function getAllMetadata(): object
    {
        return $this->getStatementsHandler()->getAllMetadata();
    }

    /**
     * Returns the query string.
     *
     * @return string The query string associated with this instance.
     */
    public function getQueryString(): string
    {
        return $this->getStatementsHandler()->getQueryString();
    }

    /**
     * Sets the query string for the Connection instance.
     *
     * @param string $params The query string to set.
     */
    public function setQueryString(string $params): void
    {
        $this->getStatementsHandler()->setQueryString($params);
    }

    /**
     * Returns the parameters associated with this instance.
     *
     * @return array|null The parameters associated with this instance.
     */
    public function getQueryParameters(): ?array
    {
        return $this->getStatementsHandler()->getQueryParameters();
    }

    /**
     * Sets the query parameters for the Connection instance.
     *
     * @param array|null $params The query parameters to set.
     */
    public function setQueryParameters(?array $params): void
    {
        $this->getStatementsHandler()->setQueryParameters($params);
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public function getQueryRows(): int|false
    {
        return $this->getStatementsHandler()->getQueryRows();
    }

    /**
     * Sets the number of query rows for the Connection instance.
     *
     * @param callable|int|false $params The number of query rows to set.
     * @return void
     */
    public function setQueryRows(callable|int|false $params): void
    {
        $this->getStatementsHandler()->setQueryRows($params);
    }

    /**
     * Returns the number of columns in a statement result.
     *
     * @return int|false The number of columns in the result or false in case of an error.
     */
    public function getQueryColumns(): int|false
    {
        return $this->getStatementsHandler()->getQueryColumns();
    }

    /**
     * Sets the number of columns in the query result.
     *
     * @param int|false $params The number of columns or false if there are no columns.
     * @return void
     */
    public function setQueryColumns(int|false $params): void
    {
        $this->getStatementsHandler()->setQueryColumns($params);
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public function getAffectedRows(): int|false
    {
        return $this->getStatementsHandler()->getAffectedRows();
    }

    /**
     * Sets the number of affected rows for the Connection instance.
     *
     * @param int|false $params The number of affected rows to set.
     * @return void
     */
    public function setAffectedRows(int|false $params): void
    {
        $this->getStatementsHandler()->setAffectedRows($params);
    }

    /**
     * Returns the statement for the function.
     *
     * @return mixed
     */
    public function getStatement(): mixed
    {
        return $this->getStatementsHandler()->getStatement();
    }

    /**
     * Set the statement for the function.
     *
     * @param mixed $statement The statement to be set.
     */
    public function setStatement(mixed $statement): void
    {
        $this->getStatementsHandler()->setStatement($statement);
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param object $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    public function bindParam(object $params): void
    {
        $this->getStatementsHandler()->bindParam($params);
    }

    /**
     * Parses an SQL statement and returns an statement.
     *
     * @param mixed ...$params The parameters for the query function.
     * @return string The statement resulting from the SQL statement.
     */
    public function parse(mixed ...$params): string
    {
        return $this->getStatementsHandler()->parse(...$params);
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return static|null
     */
    public function query(mixed ...$params): static|null
    {
        return $this->getStatementsHandler()->query(...$params);
    }

    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return static|null
     */
    public function prepare(mixed ...$params): static|null
    {
        return $this->getStatementsHandler()->prepare(...$params);
    }

    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return mixed
     */
    public function exec(mixed ...$params): mixed
    {
        return $this->getStatementsHandler()->exec(...$params);
    }

    /**
     * Fetches the next row from the statement and returns it as an array.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed $fetchArgument From the Fetch Into or Fetch Class.
     * @param mixed $optArgs From the Fetch Into or Fetch Class.
     * @return mixed The next row from the statement as an array, or false if there are no more rows.
     */
    public function fetch(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        return $this->getFetchHandler()->fetch($fetchStyle, $fetchArgument, $optArgs);
    }

    /**
     * Fetches all rows from the statement and returns them as an array.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed $fetchArgument From the Fetch Into or Fetch Class.
     * @param mixed $optArgs From the Fetch Into or Fetch Class.
     * @return array|bool The next row from the statement as an array, or false if there are no more rows.
     */
    public function fetchAll(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        return $this->getFetchHandler()->fetchAll($fetchStyle, $fetchArgument, $optArgs);
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
