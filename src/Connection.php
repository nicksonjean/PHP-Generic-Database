<?php

declare(strict_types=1);

namespace GenericDatabase;

use
  GenericDatabase\InterfaceConnection,

  GenericDatabase\Engine\FBirdEngine,
  GenericDatabase\Engine\MySQLiEngine,
  GenericDatabase\Engine\OCIEngine,
  GenericDatabase\Engine\PgSQLEngine,
  GenericDatabase\Engine\SQLSrvEngine,
  GenericDatabase\Engine\SQLiteEngine,
  GenericDatabase\Engine\PDOEngine,
  GenericDatabase\Traits\Arrays,
  GenericDatabase\Traits\Errors,
  GenericDatabase\Traits\Singleton,
  GenericDatabase\Traits\Reflections,
  GenericDatabase\Traits\JSON,
  GenericDatabase\Traits\INI,
  GenericDatabase\Traits\YAML,
  GenericDatabase\Traits\XML;

class Connection
{
    use Arrays;
    use Errors;
    use Singleton;
    use Reflections;

  /**
   * Array property for use in magic setter and getter in order
   */
    private static $engineList = [
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
    private $strategy;

  /**
   * Defines the strategy instance
   *
   * @param InterfaceConnection $strategy
   * @return InterfaceConnection
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
   * @return mixed
   */
    public function __call(string $name, array $arguments): mixed
    {
        $method = substr($name, 0, 3);
        $field = strtolower(substr($name, 3));
        if ($field === 'engine' && count($arguments) > 0) {
            self::call($this, 'initFactory', [...$arguments]);
        }
        if ($method == 'set') {
            self::call($this->getStrategy(), 'set' . ucfirst($field), [...$arguments]);
            return $this;
        } elseif ($method == 'get') {
            self::call($this->getStrategy(), 'get' . ucfirst($field), [...$arguments]);
        }
        return null;
    }

  /**
   * Triggered when invoking inaccessible methods in a static context
   *
   * @param string $name Name of the method
   * @param array $arguments Array of arguments
   * @return mixed
   */
    public static function __callStatic(string $name, array $arguments): mixed
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
                return self::getInstance();
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
        switch ($params) {
            case 'pdo':
                $this->strategy = new PDOEngine();
                break;
            case 'mysqli':
                $this->strategy = new MySQLiEngine();
                break;
            case 'pgsql':
                $this->strategy = new PgSQLEngine();
                break;
            case 'sqlsrv':
                $this->strategy = new SQLSrvEngine();
                break;
            case 'oci':
                $this->strategy = new OCIEngine();
                break;
            case 'fbird':
                $this->strategy = new FBirdEngine();
                break;
            case 'sqlite':
                $this->strategy = new SQLiteEngine();
                break;
        }
        $this->setStrategy($this->strategy);
    }

  /**
   * Determines arguments type by calling to default type
   *
   * @param mixed $arguments
   * @return void
   */
    private static function call($instance, $name, $arguments): void
    {
        call_user_func_array([$instance, $name], $arguments);
    }

  /**
   * This method is used when all parameters are used
   *
   * @param array $arguments
   * @return void
   */
    private static function callWithByStatic($arguments): void
    {
        $argumentList = [];
        $argumentClass = Reflections::getClassPropertyName(sprintf("GenericDatabase\Engine\%s\Arguments", Arrays::matchValues(self::$engineList, $arguments)), 'argumentList');
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
    private static function callArgumentsByFormat($format, $arguments): void
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
        $instance = Reflections::getClassInstance(sprintf("GenericDatabase\Engine\%s\Arguments", Arrays::matchValues(self::$engineList, Arrays::assocToIndex(Arrays::recombine($data)))));
        foreach (Arrays::recombine($data) as $key => $value) {
            if (strtolower($key) === 'options') {
                self::call(self::getInstance()->getStrategy(), 'set' . ucfirst($key), [$instance->getMethod('setConstant')->invoke(self::getInstance()->getStrategy(), ($format === 'json' || $format === 'yaml') ? $value : [$value])]);
            } else {
                self::call(self::getInstance()->getStrategy(), 'set' . ucfirst($key), [$instance->getMethod('setType')->invoke(self::getInstance()->getStrategy(), $value)]);
            }
        }
    }
}
