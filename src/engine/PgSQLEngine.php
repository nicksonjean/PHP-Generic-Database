<?php

namespace GenericDatabase\Engine;

use
  GenericDatabase\Traits\Errors,
  GenericDatabase\Traits\Caller,
  GenericDatabase\Traits\Cleaner,
  GenericDatabase\Traits\Singleton,
  GenericDatabase\Engine\PgSQL\Arguments,
  GenericDatabase\Engine\PgSQL\Options,
  GenericDatabase\Engine\PgSQL\Attributes,
  GenericDatabase\Engine\PgSQL\DSN,
  GenericDatabase\Engine\PgSQL\PgSQL,
  GenericDatabase\Engine\PgSQL\Dump;

class PgSQLEngine
{
  use Errors, Caller, Cleaner, Singleton;

  /**
   * This method is responsible for call the static instance to Arguments class with a Magic Method __call and __callStatic.
   * 
   * @return mixed
   */
  public static function call($method, $arguments): mixed
  {
    return Arguments::call($method, $arguments);
  }

  /**
   * This method is responsible for prepare the connection options before connect.
   * 
   * @return PgSQLEngine
   */
  private function preConnect(): PgSQLEngine
  {
    Options::setOptions($this->getOptions());
    $options = [];
    $options = Options::getOptions();
    $this->setOptions($options);
    return $this;
  }

  /**
   * This method is responsible for update in date late binding the connection.
   * 
   * @return PgSQLEngine
   */
  private function postConnect(): PgSQLEngine
  {
    Options::define();
    Attributes::define();
    return $this;
  }

  private function realConnect($dsn): PgSQLEngine
  {
    $this->setConnection((string) !Options::getOptions(PgSQL::ATTR_PERSISTENT) ? pg_connect($dsn, Attributes::getFlags()) : pg_pconnect($dsn, Attributes::getFlags()));
    return $this;
  }

  /**
   * This method is used to establish a database connection and set the connection instance
   * 
   * @return PgSQLEngine
   */
  public function connect(): PgSQLEngine
  {
    try {
      $this
        ->preConnect()
        ->setInstance($this)
        ->realConnect($this->parseDns())
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
   * @return \PgSql\Connection
   */
  public function getConnection(): \PgSql\Connection
  {
    return $GLOBALS['connection'];
  }

  /**
   * This method is used to assign the database connection instance
   * 
   * @param \PgSql\Connection $connection
   * @return \PgSql\Connection
   */
  public function setConnection(\PgSql\Connection $connection): \PgSql\Connection
  {
    return $GLOBALS['connection'] = $connection;
  }
}
