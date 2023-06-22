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
  GenericDatabase\Engine\SQLite3Engine,
  GenericDatabase\Engine\PDOEngine,
  GenericDatabase\Traits\Arrays,
  GenericDatabase\Traits\Errors,
  GenericDatabase\Traits\Singleton,
  GenericDatabase\Traits\Reflections;

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
    'SQLite3'
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
      case 'sqlite3':
        $this->strategy = new SQLite3Engine();
        break;
    }
    $this->setStrategy($this->strategy);
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
        return self::getInstance();
        break;
      default:
        return call_user_func_array([self::getInstance(), $name], $arguments);
        break;
    }
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
}
