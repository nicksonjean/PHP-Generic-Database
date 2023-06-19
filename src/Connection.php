<?php

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
  GenericDatabase\Traits\Caller,
  GenericDatabase\Traits\Cleaner,
  GenericDatabase\Traits\Singleton,
  GenericDatabase\Traits\Reflections;

class Connection
{
  use Arrays, Errors, Caller, Cleaner, Singleton, Reflections;

  /**
   * array property for use in magic setter and getter in order
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

  private $strategy;

  private function setStrategy(iConnection $strategy): Connection
  {
    $this->strategy = $strategy;
    return $this;
  }

  private function getStrategy(): iConnection
  {
    return $this->strategy;
  }

  /**
   * Replace alternatively the __constructor and set the Strategy
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
   * Overload and intercept not founded methods and properties
   * 
   * @param string $method
   * @param array $arguments
   * @return mixed
   */
  public function __call(string $method, array $arguments): mixed
  {
    $methodName = substr($method, 0, 3);
    $field = strtolower(substr($method, 3));
    if ($field === 'engine' && count($arguments) > 0) {
      call_user_func_array([$this, 'initFactory'], [...$arguments]);
    }
    if ($methodName == 'set') {
      call_user_func_array([$this->getStrategy(), 'set' . ucfirst($field)], [...$arguments]);
      return $this;
    } elseif ($methodName == 'get') {
      return call_user_func_array([$this->getStrategy(), 'get' . ucfirst($field)], []);
    }
  }

  /**
   * This method is responsible for call the static instance to Arguments class with a Magic Method __call and __callStatic.
   * 
   * @param string $method
   * @param array $arguments
   * @return mixed
   */
  public static function __callStatic(string $method, array $arguments): mixed
  {
    switch ($method) {
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
        return call_user_func_array([self::getInstance(), $method], $arguments);
        break;
    }
  }

  /**
   * This method is used to establish a database connection and set the connection instance
   * 
   * @return Connection
   */
  public function connect(): Connection
  {
    $this->strategy->connect();
    return $this;
  }
}
