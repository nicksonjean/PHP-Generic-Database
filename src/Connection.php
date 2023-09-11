<?php

declare(strict_types=1);

namespace GenericDatabase;

use AllowDynamicProperties;
use GenericDatabase\Engine\FBirdEngine;
use GenericDatabase\Engine\MySQLiEngine;
use GenericDatabase\Engine\OCIEngine;
use GenericDatabase\Engine\PgSQLEngine;
use GenericDatabase\Engine\SQLSrvEngine;
use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Engine\PDOEngine;
use GenericDatabase\Traits\Singleton;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\JSON;
use GenericDatabase\Helpers\INI;
use GenericDatabase\Helpers\YAML;
use GenericDatabase\Helpers\XML;
use ReflectionException;

/**
 * The `Connection` class is responsible for establishing and managing database connections.
 * It uses a strategy pattern to support different database engines.
 * The class provides methods for connecting to a database, executing queries, and managing the connection state.
 *
 * Example Usage:
 * <code>
 * //Create a new connection instance
 * $connection = Connection::getInstance();
 *
 * //Set the database engine
 * $connection->engine('pdo');
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
     * Array property for use in magic setter and getter in order
     */
    private static array $engineList = [
        'PDO',
        'MySQLi',
        'PgSQL',
        'SQLSrv',
        'OCI',
        'FBird',
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
     * @return Connection
     */
    public function __call(string $name, array $arguments): Connection
    {
        $method = substr($name, 0, 3);
        $field = strtolower(substr($name, 3));
        if ($field === 'engine' && !empty($arguments)) {
            $this->initFactory(...$arguments);
        }
        if ($method == 'set') {
            self::call($this->getStrategy(), 'set' . ucfirst($field), [...$arguments]);
            return $this;
        } elseif ($method == 'get') {
            self::call($this->getStrategy(), 'get' . ucfirst($field), [...$arguments]);
        }
        return self::getInstance();
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
        return match ($name) {
            'new' => match (true) {
                JSON::isValidJSON(...$arguments) => self::callArgumentsByFormat('json', $arguments),
                YAML::isValidYAML(...$arguments) => self::callArgumentsByFormat('yaml', $arguments),
                INI::isValidINI(...$arguments) => self::callArgumentsByFormat('ini', $arguments),
                XML::isValidXML(...$arguments) => self::callArgumentsByFormat('xml', $arguments),
                default => Arrays::isAssoc(...$arguments)
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
            'fbird' => new FBirdEngine(),
            'sqlite' => new SQLiteEngine(),
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
     * @throws ReflectionException
     */
    private static function callWithByStaticArgs(array $arguments): Connection
    {
        $argumentClass = Reflections::getClassPropertyName(
            sprintf(
                "GenericDatabase\Engine\%s\Arguments",
                Arrays::matchValues(self::$engineList, $arguments)
            ),
            'argumentList'
        );
        $argumentList = array_merge(['Engine'], $argumentClass);
        if ($arguments[0] === 'pdo' && $arguments[1] === 'sqlite') {
            $clonedArgumentList = Arrays::exceptByValues($argumentList, ['Host', 'Port', 'User', 'Password']);
            foreach ($arguments as $key => $value) {
                self::call(self::getInstance(), 'set' . $clonedArgumentList[$key], [$value]);
            }
        } else {
            foreach ($arguments as $key => $value) {
                self::call(self::getInstance(), 'set' . $argumentList[$key], [$value]);
            }
        }
        return self::getInstance();
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
                "GenericDatabase\Engine\%s\Arguments",
                Arrays::matchValues(self::$engineList, Arrays::assocToIndex(Arrays::recombine($data)))
            )
        );
        foreach (Arrays::recombine($data) as $key => $value) {
            if (strtolower($key) === 'options') {
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
