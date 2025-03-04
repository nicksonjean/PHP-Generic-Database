<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use Exception;
use SensitiveParameter;
use ReflectionException;
use AllowDynamicProperties;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Helpers\Compare;
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
use GenericDatabase\Engine\SQLSrv\Connection\SQLSrv;
use GenericDatabase\Engine\SQLSrv\Connection\DSN\DSNHandler;
use GenericDatabase\Engine\SQLSrv\Connection\Fetch\FetchHandler;
use GenericDatabase\Engine\SQLSrv\Connection\Options\OptionsHandler;
use GenericDatabase\Engine\SQLSrv\Connection\Attributes\AttributesHandler;
use GenericDatabase\Engine\SQLSrv\Connection\Fetch\Strategy\FetchStrategy;
use GenericDatabase\Engine\SQLSrv\Connection\Statements\StatementsHandler;
use GenericDatabase\Engine\SQLSrv\Connection\Arguments\ArgumentsHandler;
use GenericDatabase\Engine\SQLSrv\Connection\Arguments\Strategy\ArgumentsStrategy;
use GenericDatabase\Engine\SQLSrv\Connection\Transactions\TransactionsHandler;
use GenericDatabase\Engine\SQLSrv\Connection\Report\ReportHandler;

/**
 * Dynamic and Static container class for SQLSrvConnection connections.
 *
 * @method static SQLSrvConnection|void setDriver(mixed $value): void
 * @method static SQLSrvConnection|string getDriver($value = null): string
 * @method static SQLSrvConnection|void setHost(mixed $value): void
 * @method static SQLSrvConnection|string getHost($value = null): string
 * @method static SQLSrvConnection|void setPort(mixed $value): void
 * @method static SQLSrvConnection|int getPort($value = null): int
 * @method static SQLSrvConnection|void setUser(mixed $value): void
 * @method static SQLSrvConnection|string getUser($value = null): string
 * @method static SQLSrvConnection|void setPassword(mixed $value): void
 * @method static SQLSrvConnection|string getPassword($value = null): string
 * @method static SQLSrvConnection|void setDatabase(mixed $value): void
 * @method static SQLSrvConnection|string getDatabase($value = null): string
 * @method static SQLSrvConnection|void setOptions(mixed $value): void
 * @method static SQLSrvConnection|array|null getOptions($value = null): array|null
 * @method static SQLSrvConnection|static setConnected(mixed $value): void
 * @method static SQLSrvConnection|mixed getConnected($value = null): mixed
 * @method static SQLSrvConnection|void setDsn(mixed $value): void
 * @method static SQLSrvConnection|mixed getDsn($value = null): mixed
 * @method static SQLSrvConnection|void setAttributes(mixed $value): void
 * @method static SQLSrvConnection|mixed getAttributes($value = null): mixed
 * @method static SQLSrvConnection|void setCharset(mixed $value): void
 * @method static SQLSrvConnection|string getCharset($value = null): string
 * @method static SQLSrvConnection|void setException(mixed $value): void
 * @method static SQLSrvConnection|mixed getException($value = null): mixed
 */
#[AllowDynamicProperties]
class SQLSrvConnection implements IConnection, IFetch, IStatements, IDSN, IArguments, ITransactions
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
        self::$dsnHandler = new DSNHandler($this, self::$optionsHandler);
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
     * @return SQLSrvConnection
     */
    private function preConnect(): SQLSrvConnection
    {
        $this->getOptionsHandler()->setOptions(static::getOptions());
        static::setOptions($this->getOptionsHandler()->getOptions());
        if (static::getCharset()) {
            static::setCharset((static::getCharset() == 'utf8') ? 'UTF-8' : static::getCharset());
        }
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return SQLSrvConnection
     */
    private function postConnect(): SQLSrvConnection
    {
        $this->getOptionsHandler()->define();
        $this->getAttributesHandler()->define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the SQLSrvConnection connection.
     *
     * @param mixed $host The host of the database
     * @param mixed $user The user of the database
     * @param mixed $password The password of the database
     * @param mixed $database The name of the database
     * @param mixed $port The port of the database
     * @return SQLSrvConnection
     * @throws Exception
     */
    private function realConnect(
        mixed $host,
        mixed $user,
        #[SensitiveParameter] mixed $password,
        mixed $database,
        mixed $port
    ): SQLSrvConnection {
        $serverName = vsprintf('%s,%s', [$host, $port]);
        $connectionInfo = ["Database" => $database, "UID" => $user, "PWD" => $password];
        if (static::getCharset()) {
            $connectionInfo['CharacterSet'] = static::getCharset();
        }
        if ($this->getOptionsHandler()->getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)) {
            $connectionInfo['LoginTimeout'] = $this->getOptionsHandler()->getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT);
        }
        $this->setConnection(sqlsrv_connect($serverName, $connectionInfo));
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return SQLSrvConnection
     * @throws Exception
     */
    public function connect(): SQLSrvConnection
    {
        if (!extension_loaded('sqlsrv')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'sqlsrv',
                'PHP.ini'
            );
            throw new Exceptions($message);
        }

        try {
            $this->setInstance($this);
            $this
                ->preConnect()
                ->getInstance()
                ->setDsn($this->parseDsn())
                ->realConnect(
                    static::getHost(),
                    static::getUser(),
                    static::getPassword(),
                    static::getDatabase(),
                    static::getPort()
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
            if (!$this->getOptionsHandler()->getOptions(SQLSrv::ATTR_PERSISTENT)) {
                if (Compare::connection($this->getConnection()) === 'sqlsrv') {
                    sqlsrv_close($this->getConnection());
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
        return (Compare::connection($this->getConnection()) === 'sqlsrv') && $this->getInstance()->getConnected();
    }

    /**
     * This method is responsible for parsing the DSN from DSN class.
     *
     * @return string|Exceptions
     */
    private function parseDsn(): string|Exceptions
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
     * This function returns the last ID generated by an auto-increment column.
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
     * @return mixed
     */
    public function quote(mixed ...$params): mixed
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
        return SQLSrv::getAttribute($name);
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
        SQLSrv::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return array|null
     * @noinspection PhpUnused
     */
    public function errorCode(mixed $inst = null): ?array
    {
        return sqlsrv_errors($inst);
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return array|null
     * @noinspection PhpUnused
     */
    public function errorInfo(mixed $inst = null): ?array
    {
        return sqlsrv_errors($inst);
    }
}
