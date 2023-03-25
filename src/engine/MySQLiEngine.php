<?php

namespace GenericDatabase\Engine;

use
  GenericDatabase\Traits\Errors,
  GenericDatabase\Traits\Caller,
  GenericDatabase\Traits\Cleaner,
  GenericDatabase\Traits\Singleton,
  GenericDatabase\Engine\MySQLi\Arguments,
  GenericDatabase\Engine\MySQLi\Options,
  GenericDatabase\Engine\MySQLi\Attributes,
  GenericDatabase\Engine\MySQLi\DSN,
  GenericDatabase\Engine\MySQli\MySQL,
  GenericDatabase\Engine\MySQLi\Dump;

class MySQLiEngine
{
  use Errors, Caller, Cleaner, Singleton;

  /**
   * This method is responsible for call the static instance to Arguments class with a Magic Method __call and __callStatic.
   * 
   * @param string $method
   * @param array $arguments
   * @return MySQLiEngine
   */
  private static function call(string $method, array $arguments): MySQLiEngine
  {
    return Arguments::call($method, $arguments);
  }

  /**
   * This method is responsible for prepare the connection options before connect.
   * 
   * @return MySQLiEngine
   */
  private function preConnect(): MySQLiEngine
  {
    $this->setConnection(mysqli_init());
    Options::setOptions($this->getOptions());
    $options = [];
    $options = Options::getOptions();
    $this->setOptions($options);
    return $this;
  }

  /**
   * This method is responsible for update in date late binding the connection.
   * 
   * @return MySQLiEngine
   */
  private function postConnect(): MySQLiEngine
  {
    Options::define();
    Attributes::define();
    return $this;
  }

  /**
   * This method is responsible for creating a new instance of the MySQLi connection.
   * 
   * @param string|null $host
   * @param string $user
   * @param string|null $password
   * @param string $database
   * @param int $port
   * @return PDOEngine 
   */
  private function realConnect(string $host = null, string $user, string $password = null, string $database, int $port): MySQLiEngine
  {
    $host = (string) !Options::getOptions(MySQL::ATTR_PERSISTENT) ? $host : 'p:' . $host;
    $this->setHost($host);
    $this->parseDns();
    $this->getConnection()->real_connect($host, $user, $password, $database, $port);
    return $this;
  }

  /**
   * This method is used to establish a database connection and set the connection instance
   * 
   * @return MySQLiEngine
   */
  public function connect(): MySQLiEngine
  {
    try {
      $this
        ->preConnect()
        ->setInstance($this)
        ->realConnect($this->getHost(), $this->getUser(), $this->getPassword(), $this->getDatabase(), $this->getPort())
        ->postConnect()
        ->setConnected(true);
      return $this;
    } catch (\Exception $error) {
      $this->setConnected(false);
      Errors::throw($error);
    }
  }

  /**
   * This method is responsible for parsing the DSN from DSN class.
   * 
   * @return string|\Exception
   */
  private function parseDns(): string|\Exception
  {
    return DSN::parseDns();
  }

  /**
   * This method is used to get the database connection instance
   * 
   * @return \MySQLi
   */
  public function getConnection(): \MySQLi
  {
    return $GLOBALS['connection'];
  }

  /**
   * This method is used to assign the database connection instance
   * 
   * @param \MySQLi $connection
   * @return \MySQLi
   */
  public function setConnection(\MySQLi $connection): \MySQLi
  {
    return $GLOBALS['connection'] = $connection;
  }

  public function getAttribute($name)
  {
    return MySQL::getAttribute($name);
  }

  public function setAttribute($name, $value)
  {
    return MySQL::setAttribute($name, $value);
  }
}
