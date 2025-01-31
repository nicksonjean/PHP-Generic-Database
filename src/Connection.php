<?php

declare(strict_types=1);

namespace GenericDatabase;

use ReflectionException;
use AllowDynamicProperties;
use GenericDatabase\Core\Entity;
use GenericDatabase\Shared\Singleton;
use GenericDatabase\Helpers\INI;
use GenericDatabase\Helpers\XML;
use GenericDatabase\Helpers\JSON;
use GenericDatabase\Helpers\YAML;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\OCIConnection;
use GenericDatabase\Engine\PDOConnection;
use GenericDatabase\Engine\ODBCConnection;
use GenericDatabase\Engine\PgSQLConnection;
use GenericDatabase\Engine\MySQLiConnection;
use GenericDatabase\Engine\SQLiteConnection;
use GenericDatabase\Engine\SQLSrvConnection;
use GenericDatabase\Engine\FirebirdConnection;

/**
 * The `GenericDatabase\Connection` class is responsible for establishing and managing database connections.
 * It uses a strategy pattern to support different database engines. The class provides methods for connecting
 * to a database, executing queries, fetching results, and managing the connection state.
 *
 * Example Usage:
 * <code>
 * //Create a new connection instance
 * $connection = Connection::getInstance();
 *
 * //Set the database engine
 * $connection->setEngine('pdo');
 *
 * //Set connection parameters
 * $connection->setHost('localhost');
 * $connection->setPort(3306);
 * $connection->setUser('root');
 * $connection->setPassword('password');
 *
 * //Connect to the database
 * $connection->connect();
 *
 * //Execute a query
 * $result = $connection->query('SELECT * FROM users');
 *
 * //Fetch all rows from the result set
 * $rows = $connection->fetchAll($result);
 *
 * //Disconnect from the database
 * $connection->disconnect();
 * </code>
 *
 * Dynamic and Static container class for Connection connections.
 *
 * @method static Connection|static setEngine(mixed $value): void
 * @method static Connection|static getEngine($value = null): mixed
 * @method static Connection|static setDriver(mixed $value): void
 * @method static Connection|static getDriver($value = null): mixed
 * @method static Connection|static setHost(mixed $value): void
 * @method static Connection|static getHost($value = null): mixed
 * @method static Connection|static setPort(mixed $value): void
 * @method static Connection|static getPort($value = null): mixed
 * @method static Connection|static setUser(mixed $value): void
 * @method static Connection|static getUser($value = null): mixed
 * @method static Connection|static setPassword(mixed $value): void
 * @method static Connection|static getPassword($value = null): mixed
 * @method static Connection|static setDatabase(mixed $value): void
 * @method static Connection|static getDatabase($value = null): mixed
 * @method static Connection|static setOptions(mixed $value): void
 * @method static Connection|static getOptions($value = null): mixed
 * @method static Connection|static setConnected(mixed $value): void
 * @method static Connection|static getConnected($value = null): mixed
 * @method static Connection|static setDsn(mixed $value): void
 * @method static Connection|static getDsn($value = null): mixed
 * @method static Connection|static setAttributes(mixed $value): void
 * @method static Connection|static getAttributes($value = null): mixed
 * @method static Connection|static setCharset(mixed $value): void
 * @method static Connection|static getCharset($value = null): mixed
 * @method static Connection|static setException(mixed $value): void
 * @method static Connection|static getException($value = null): mixed
 */
#[AllowDynamicProperties]
class Connection implements IConnection, IConnectionStrategy
{
    use Singleton;

    /**
     * Fetch mode that starts fetching rows only when they are requested.
     */
    final public const FETCH_LAZY = 1;

    /**
     * Constant for the fetch mode representing fetching as an associative array
     */
    final public const FETCH_ASSOC = 2;

    /**
     * Constant for the fetch mode representing fetching as a numeric array
     */
    final public const FETCH_NUM = 3;

    /**
     * Constant for the fetch mode representing fetching as both a numeric and associative array
     */
    final public const FETCH_BOTH = 4;

    /**
     * Constant for the fetch mode representing fetching as an object
     */
    final public const FETCH_OBJ = 5;

    /**
     * Fetch mode that requires explicit binding of PHP variables to fetch values.
     */
    final public const FETCH_BOUND = 6;

    /**
     * Constant for the fetch mode representing fetching a single column
     */
    final public const FETCH_COLUMN = 7;

    /**
     * Constant for the fetch mode representing fetching into a new instance of a specified class
     */
    final public const FETCH_CLASS = 8;

    /**
     * Constant for the fetch mode representing fetching into an existing object
     */
    final public const FETCH_INTO = 9;

    /**
     * Array property for use in magic setter and getter in order
     */
    private static array $engineList = [
        'PDO',
        'MySQLi',
        'PgSQL',
        'SQLSrv',
        'OCI',
        'ODBC',
        'Firebird',
        'SQLite'
    ];

    /**
     * Empty constructor since initialization is handled through factory methods
     * and the Strategy pattern implementation
     */
    public function __construct()
    {
    }

    /**
     * Triggered when invoking inaccessible methods in an object context
     *
     * @param string $name Name of the method
     * @param array $arguments Array of arguments
     * @return Connection|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): Connection|string|int|bool|array|null
    {
        $method = substr($name, 0, 3);
        $field = mb_strtolower(substr($name, 3));
        if ($field === 'engine' && !empty($arguments)) {
            $this->initFactory(...$arguments);
        }
        if ($method == 'set') {
            call_user_func_array([$this->getStrategy(), 'set' . ucfirst($field)], [...$arguments]);
        } elseif ($method == 'get') {
            return call_user_func_array([$this->getStrategy(), 'get' . ucfirst($field)], [...$arguments]);
        }
        return $this;
    }

    /**
     * Triggered when invoking inaccessible methods in a static context
     *
     * @param string $name Name of the method
     * @param array $arguments Array of arguments
     * @return Connection
     * @throws ReflectionException
     */
    public static function __callStatic(string $name, array $arguments): Connection
    {
        $argumentsFile = Arrays::assocToIndex(Arrays::recombine($arguments));
        return match ($name) {
            'new' => match (true) {
                    JSON::isValidJSON(...$argumentsFile) => self::callArgumentsByFormat('json', $argumentsFile),
                    YAML::isValidYAML(...$argumentsFile) => self::callArgumentsByFormat('yaml', $argumentsFile),
                    INI::isValidINI(...$argumentsFile) => self::callArgumentsByFormat('ini', $argumentsFile),
                    XML::isValidXML(...$argumentsFile) => self::callArgumentsByFormat('xml', $argumentsFile),
                    default => Arrays::isAssoc(...$argumentsFile)
                    ? self::callWithByStaticArray(...$arguments)
                    : self::callWithByStaticArgs($arguments),
                },
            default => self::call(self::getInstance(), $name, $arguments),
        };
    }

    /**
     * Property of the type object who define the strategy
     */
    private IConnection $strategy;

    /**
     * Defines the strategy instance
     *
     * @param IConnection $strategy
     * @return void
     */
    public function setStrategy(IConnection $strategy): void
    {
        $this->strategy = $strategy;
    }

    /**
     * Get the strategy instance
     *
     * @return IConnection
     */
    public function getStrategy(): IConnection
    {
        return $this->strategy;
    }

    /**
     * Factory that replaces the __constructor and defines the Strategy through the engine parameter
     *
     * @param mixed $params
     * @return void
     */
    private function initFactory(mixed $params): void
    {
        $this->setStrategy(match ($params) {
            'pdo' => new PDOConnection(),
            'mysqli' => new MySQLiConnection(),
            'pgsql' => new PgSQLConnection(),
            'sqlsrv' => new SQLSrvConnection(),
            'oci' => new OCIConnection(),
            'firebird' => new FirebirdConnection(),
            'sqlite' => new SQLiteConnection(),
            'odbc' => new ODBCConnection(),
            default => null,
        });
    }

    /**
     * Determines arguments type by calling to default type
     *
     * @param mixed $instance
     * @param mixed $name
     * @param mixed $arguments
     * @return Connection
     */
    private static function call(mixed $instance, mixed $name, mixed $arguments): Connection
    {
        call_user_func_array([$instance, $name], $arguments);
        return self::getInstance();
    }

    /**
     * This method is used when all parameters are used in the static array format
     *
     * @param array $arguments
     * @return Connection
     */
    private static function callWithByStaticArray(array $arguments): Connection
    {
        foreach ($arguments as $key => $value) {
            self::call(self::getInstance(), 'set' . ucfirst($key), [$value]);
        }
        return self::getInstance();
    }

    /**
     * This method is used when all parameters are used in the static arguments format
     *
     * @param array $arguments
     * @return Connection
     * @noinspection PhpUnused
     */
    private static function callWithByStaticArgs(array $arguments): Connection
    {
        return self::callWithByStaticArray($arguments);
    }

    /**
     * Determines arguments type by calling to format type
     *
     * @param string $format Accept formats json, xml, ini and yaml
     * @param mixed $arguments
     * @return Connection
     * @throws ReflectionException
     * @noinspection PhpUnused
     */
    private static function callArgumentsByFormat(string $format, mixed $arguments): Connection
    {
        $data = [];
        if ($format === 'json') {
            $data = JSON::parseJSON(...$arguments);
        } elseif ($format === 'ini') {
            $data = INI::parseINI(...$arguments);
        } elseif ($format === 'xml') {
            $data = XML::parseXML(...$arguments);
        } elseif ($format === 'yaml') {
            $data = YAML::parseYAML(...$arguments);
        }
        self::call(self::getInstance(), 'initFactory', Arrays::assocToIndex(Arrays::recombine($data)));
        $instance = Reflections::getClassInstance(
            sprintf(
                Entity::CASE_ARGUMENT_CLASS->value,
                Arrays::matchValues(self::$engineList, Arrays::assocToIndex(Arrays::recombine($data)))
            )
        );
        foreach (Arrays::recombine($data) as $key => $value) {
            if (mb_strtolower($key) === 'options') {
                self::call(
                    self::getInstance()->getStrategy(),
                    'set' . ucfirst($key),
                    [
                        $instance->getMethod('setConstant')->invoke(
                            self::getInstance()->getStrategy(),
                            ($format === 'json' || $format === 'yaml') ? $value : [$value]
                        )
                    ]
                );
            } else {
                self::call(
                    self::getInstance()->getStrategy(),
                    'set' . ucfirst($key),
                    [$instance->getMethod('setType')->invoke(self::getInstance()->getStrategy(), $value)]
                );
            }
        }
        return self::getInstance();
    }

    /**
     * This method is used to assign the database connection instance
     *
     * @param mixed $connection Sets an instance of the connection with the database
     * @return mixed
     */
    public function setConnection(mixed $connection): mixed
    {
        $this->getStrategy()->setConnection($connection);
        return $this->getConnection();
    }

    /**
     * This method is used to get the database connection instance
     *
     * @return mixed
     */
    public function getConnection(): mixed
    {
        return $this->getStrategy()->getConnection();
    }

    /**
     * This method is used to establish a database connection
     *
     * @return Connection
     */
    public function connect(): Connection
    {
        $this->getStrategy()->connect();
        return $this;
    }

    /**
     * Pings a server connection, or tries to reconnect if the connection has gone down
     *
     * @return bool
     */
    public function ping(): bool
    {
        return $this->getStrategy()->ping();
    }

    /**
     * Disconnects from a database.
     *
     * @return void
     */
    public function disconnect(): void
    {
        $this->getStrategy()->disconnect();
    }

    /**
     * Returns true when connection was established.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->getStrategy()->isConnected();
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
        return $this->getStrategy()->loadFromFile($file, $delimiter, $onProgress);
    }

    /**
     * This function creates a new transaction, in order to be able to commit or rollback changes made to the database.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->getStrategy()->beginTransaction();
    }

    /**
     * This function commits any changes made to the database during this transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->getStrategy()->commit();
    }

    /**
     * This function rolls back any changes made to the database during
     * this transaction and restores the data to its original state.
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->getStrategy()->rollback();
    }

    /**
     * This function returns the last ID generated by an auto-increment column,
     * either the last one inserted during the current transaction, or by passing in the optional name parameter.
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getStrategy()->inTransaction();
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
        return $this->getStrategy()->lastInsertId($name);
    }

    /**
     * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
     *
     * @param mixed $params Content to be quoted
     * @return mixed
     */
    public function quote(mixed ...$params): mixed
    {
        return $this->getStrategy()->quote(...$params);
    }

    /**
     * Returns an object containing the number of queried rows and the number of affected rows.
     *
     * @return object An associative object with keys 'queryRows' and 'affectedRows'.
     */
    public function getAllMetadata(): object
    {
        return $this->getStrategy()->getAllMetadata();
    }

    /**
     * Returns the query string.
     *
     * @return string The query string associated with this instance.
     */
    public function getQueryString(): string
    {
        return $this->getStrategy()->getQueryString();
    }

    /**
     * Sets the query string for the Connection instance.
     *
     * @param string $params The query string to set.
     */
    public function setQueryString(string $params): void
    {
        $this->getStrategy()->setQueryString($params);
    }

    /**
     * Returns the parameters associated with this instance.
     *
     * @return array|null The parameters associated with this instance.
     */
    public function getQueryParameters(): ?array
    {
        return $this->getStrategy()->getQueryParameters();
    }

    /**
     * Sets the query parameters for the Connection instance.
     *
     * @param array|null $params The query parameters to set.
     */
    public function setQueryParameters(?array $params): void
    {
        $this->getStrategy()->setQueryParameters($params);
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public function getQueryRows(): int|false
    {
        return $this->getStrategy()->getQueryRows();
    }

    /**
     * Sets the number of query rows for the Connection instance.
     *
     * @param callable|int|false $params The number of query rows to set.
     * @return void
     */
    public function setQueryRows(callable|int|false $params): void
    {
        $this->getStrategy()->setQueryRows($params);
    }

    /**
     * Returns the number of columns in a statement result.
     *
     * @return int|false The number of columns in the result or false in case of an error.
     */
    public function getQueryColumns(): int|false
    {
        return $this->getStrategy()->getQueryColumns();
    }

    /**
     * Sets the number of columns in the query result.
     *
     * @param int|false $params The number of columns or false if there are no columns.
     * @return void
     */
    public function setQueryColumns(int|false $params): void
    {
        $this->getStrategy()->setQueryColumns($params);
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public function getAffectedRows(): int|false
    {
        return $this->getStrategy()->getAffectedRows();
    }

    /**
     * Sets the number of affected rows for the Connection instance.
     *
     * @param int|false $params The number of affected rows to set.
     * @return void
     */
    public function setAffectedRows(int|false $params): void
    {
        $this->getStrategy()->setAffectedRows($params);
    }

    /**
     * Returns the statement for the function.
     *
     * @return mixed
     */
    public function getStatement(): mixed
    {
        return $this->getStrategy()->getStatement();
    }

    /**
     * Set the statement for the function.
     *
     * @param mixed $statement The statement to be set.
     */
    public function setStatement(mixed $statement): void
    {
        $this->getStrategy()->setStatement($statement);
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return mixed
     */
    public function query(mixed ...$params): mixed
    {
        return $this->getStrategy()->query(...$params);
    }

    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return mixed
     */
    public function prepare(mixed ...$params): mixed
    {
        return $this->getStrategy()->prepare(...$params);
    }

    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return mixed
     */
    public function exec(mixed ...$params): mixed
    {
        return $this->getStrategy()->exec(...$params);
    }

    /**
     * Fetches the next row from the statement and returns it as an array.
     *
     * @param int|null $fetchStyle
     * @param mixed|null $fetchArgument
     * @param mixed|null $optArgs
     * @return mixed The next row from the statement as an array, or false if there are no more rows.
     */
    public function fetch(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        return $this->getStrategy()->fetch($fetchStyle, $fetchArgument, $optArgs);
    }

    /**
     * Fetches all rows from the statement and returns them as an array.
     *
     * @param int|null $fetchStyle
     * @param mixed|null $fetchArgument
     * @param mixed|null $optArgs
     * @return array|bool An array containing all rows from the statement.
     */
    public function fetchAll(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        return $this->getStrategy()->fetchAll($fetchStyle, $fetchArgument, $optArgs);
    }

    /**
     * This function retrieves an attribute from the database.
     *
     * @param mixed $name The attribute name
     * @return mixed
     */
    public function getAttribute(mixed $name): mixed
    {
        return $this->getStrategy()->getAttribute($name);
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
        $this->getStrategy()->setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return int|string|array|bool|null
     * @noinspection PhpUnused
     */
    public function errorCode(mixed $inst = null): int|string|array|bool|null
    {
        return $this->getStrategy()->getConnection()->errorCode($inst);
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return string|array|bool|null
     * @noinspection PhpUnused
     */
    public function errorInfo(mixed $inst = null): string|array|bool|null
    {
        return $this->getStrategy()->getConnection()->errorInfo($inst);
    }
}
