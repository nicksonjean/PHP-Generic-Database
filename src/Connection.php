<?php

declare(strict_types=1);

namespace GenericDatabase;

use ReflectionException;
use AllowDynamicProperties;
use GenericDatabase\Core\Entity;
use GenericDatabase\Helpers\INI;
use GenericDatabase\Helpers\XML;
use GenericDatabase\Helpers\JSON;
use GenericDatabase\Helpers\YAML;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Engine\OCIEngine;
use GenericDatabase\Engine\PDOEngine;
use GenericDatabase\Shared\Singleton;
use GenericDatabase\Engine\ODBCEngine;
use GenericDatabase\Engine\PgSQLEngine;
use GenericDatabase\Engine\MySQLiEngine;
use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Engine\SQLSrvEngine;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Engine\FirebirdEngine;

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
class Connection
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
     * Property of the type object who define the strategy
     */
    private IConnection $strategy;

    /**
     * Defines the strategy instance
     *
     * @param IConnection $strategy
     * @return Connection
     */
    private function setStrategy(IConnection $strategy): Connection
    {
        $this->strategy = $strategy;
        return $this;
    }

    /**
     * Get the strategy instance
     *
     * @return IConnection
     */
    private function getStrategy(): IConnection
    {
        return $this->strategy;
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
                    : self::callWithByStaticArgs($arguments)
            },
            default => self::call(self::getInstance(), $name, $arguments)
        };
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
     * @param mixed $params The fetch style (optional)
     * @return mixed The next row from the statement as an array, or false if there are no more rows.
     */
    public function fetch(mixed ...$params): mixed
    {
        return $this->getStrategy()->fetch(...$params);
    }

    /**
     * Fetches all rows from the statement and returns them as an array.
     *
     * @param mixed $params The fetch style (optional)
     * @return mixed An array containing all rows from the statement.
     */
    public function fetchAll(mixed ...$params): mixed
    {
        return $this->getStrategy()->fetchAll(...$params);
    }

    /**
     * Factory that replaces the __constructor and defines the Strategy through the engine parameter
     *
     * @param mixed $params
     * @return void
     */
    private function initFactory(mixed $params): void
    {
        $this->strategy = match ($params) {
            'pdo' => new PDOEngine(),
            'mysqli' => new MySQLiEngine(),
            'pgsql' => new PgSQLEngine(),
            'sqlsrv' => new SQLSrvEngine(),
            'oci' => new OCIEngine(),
            'firebird' => new FirebirdEngine(),
            'sqlite' => new SQLiteEngine(),
            'odbc' => new ODBCEngine(),
            default => null,
        };
        $this->setStrategy($this->strategy);
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
}
