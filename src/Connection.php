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
use GenericDatabase\Traits\Arrays;
use GenericDatabase\Traits\Errors;
use GenericDatabase\Traits\Singleton;
use GenericDatabase\Traits\Reflections;
use GenericDatabase\Traits\JSON;
use GenericDatabase\Traits\INI;
use GenericDatabase\Traits\YAML;
use GenericDatabase\Traits\XML;

#[AllowDynamicProperties]
class Connection
{
    use Arrays;
    use Errors;
    use Singleton;
    use Reflections;

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
    private InterfaceConnection $strategy;

    /**
     * Defines the strategy instance
     *
     * @param InterfaceConnection $strategy
     * @return Connection
     */
    public function setStrategy(InterfaceConnection $strategy): Connection
    {
        $this->strategy = $strategy;
        return $this;
    }

    /**
     * Get the strategy instance
     *
     * @return InterfaceConnection
     */
    public function getStrategy(): InterfaceConnection
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
     */
    public static function __callStatic(string $name, array $arguments): Connection
    {
        switch ($name) {
            case 'new':
            case 'create':
            case 'config':
                if (JSON::isValidJSON(...$arguments)) {
                    self::callArgumentsByFormat('json', $arguments);
                } elseif (YAML::isValidYAML(...$arguments)) {
                    self::callArgumentsByFormat('yaml', $arguments);
                } elseif (INI::isValidINI(...$arguments)) {
                    self::callArgumentsByFormat('ini', $arguments);
                } elseif (XML::isValidXML(...$arguments)) {
                    self::callArgumentsByFormat('xml', $arguments);
                } else {
                    self::callWithByStatic($arguments);
                }
                break;
            default:
                self::call(self::getInstance(), $name, $arguments);
                break;
        }
        return self::getInstance();
    }

    /**
     * This method is used to establish a database connection
     *
     * @return Connection
     */
    public function connect(): Connection
    {
        $this->strategy->connect();
        return $this;
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
     * @return void
     */
    private static function call(mixed $instance, mixed $name, mixed $arguments): void
    {
        call_user_func_array([$instance, $name], $arguments);
    }

    /**
     * This method is used when all parameters are used
     *
     * @param array $arguments
     * @return void
     */
    private static function callWithByStatic(array $arguments): void
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
    }

    /**
     * Determines arguments type by calling to format type
     *
     * @param string $format Accept formats json, xml, ini and yaml
     * @param mixed $arguments
     * @return void
     */
    private static function callArgumentsByFormat(string $format, mixed $arguments): void
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
    }
}
