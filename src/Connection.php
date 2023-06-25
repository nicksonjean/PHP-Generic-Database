<?php

declare(strict_types=1);

namespace GenericDatabase;

use
  GenericDatabase\iConnection,
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
  use Arrays, Errors, Singleton, Reflections;

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
   * @param iConnection $strategy
   * @return iConnection
   */
  private function setStrategy(iConnection $strategy): Connection
  {
    $this->strategy = $strategy;
    return $this;
  }

  /**
   * Get the strategy instance
   * 
   * @return iConnection
   */
  private function getStrategy(): iConnection
  {
    return $this->strategy;
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
      call_user_func_array([$this, 'initFactory'], [...$arguments]);
    }
    if ($method == 'set') {
      call_user_func_array([$this->getStrategy(), 'set' . ucfirst($field)], [...$arguments]);
      return $this;
    } elseif ($method == 'get') {
      return call_user_func_array([$this->getStrategy(), 'get' . ucfirst($field)], []);
    }
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
        } else if (YAML::isValidYAML(...$arguments)) {
          self::callArgumentsByFormat('yaml', $arguments);
        } else if (INI::isValidINI(...$arguments)) {
          self::callArgumentsByFormat('ini', $arguments);
        } else if (XML::isValidXML(...$arguments)) {
          self::callArgumentsByFormat('xml', $arguments);
        } else {
          self::callWithFullArguments($arguments);
        }
        return self::getInstance();
        break;
      default:
        self::callArgumentsByDefault($name, $arguments);
        break;
    }
    return self::getInstance();
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
   * This method is used when all parameters are used
   * 
   * @param array $arguments
   * @return void
   */
  private static function callWithFullArguments($arguments): void
  {
    $argumentList = [];
    $argumentClass = Reflections::getClassPropertyName(sprintf("GenericDatabase\Engine\%s\Arguments", Arrays::arrayByMatchValues(self::$engineList, $arguments)), 'argumentList');
    $argumentList = array_merge(['Engine'], $argumentClass);
    if ($arguments[0] === 'pdo' && $arguments[1] === 'sqlite') {
      $clonedArgumentList = Arrays::exceptByValues($argumentList, ['Host', 'Port', 'User', 'Password']);
      foreach ($arguments as $key => $value) {
        call_user_func_array([self::getInstance(), 'set' . $clonedArgumentList[$key]], [$value]);
      }
    } else {
      foreach ($arguments as $key => $value) {
        call_user_func_array([self::getInstance(), 'set' . $argumentList[$key]], [$value]);
      }
    }
  }

  /**
   * Determines arguments type by calling to default type
   *
   * @param mixed $arguments
   * @return void
   */
  private static function callArgumentsByDefault($name, $arguments): void
  {
    call_user_func_array([self::getInstance(), $name], $arguments);
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
    $args = [];
    $params = [];

    if ($format === 'json') {
      $data = JSON::parseJSON(...$arguments);
    } elseif ($format === 'ini') {
      $data = INI::parseINI(...$arguments);
    } elseif ($format === 'xml') {
      $data = XML::parseXML(...$arguments);
    } elseif ($format === 'yaml') {
      $data = YAML::parseYAML(...$arguments);
    }

    foreach ($data as $key => $value) {
      $args[$key] = $value;
      $params[] = $value;
    }

    call_user_func_array([self::getInstance(), 'initFactory'], [...$params]);

    $reflex = new \ReflectionClass(sprintf("GenericDatabase\Engine\%s\Arguments", Arrays::arrayByMatchValues(self::$engineList, $params)));

    foreach ($args as $key => $value) {
      if (strtolower($key) === 'options') {
        call_user_func_array([self::getInstance()->getStrategy(), 'set' . ucfirst($key)], [$reflex->getMethod('setConstant')->invoke(self::getInstance()->getStrategy(), ($format === 'json' || $format === 'yaml') ? $value : [$value])]);
      } else {
        call_user_func_array([self::getInstance()->getStrategy(), 'set' . ucfirst($key)], [$reflex->getMethod('setType')->invoke(self::getInstance()->getStrategy(), $value)]);
      }
    }
  }
}
