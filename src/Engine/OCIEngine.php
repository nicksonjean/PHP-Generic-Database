<?php

namespace GenericDatabase\Engine;

use
  GenericDatabase\Traits\Errors,
  GenericDatabase\Traits\Caller,
  GenericDatabase\Traits\Cleaner,
  GenericDatabase\Traits\Singleton,
  GenericDatabase\Engine\OCI\Arguments,
  GenericDatabase\Engine\OCI\Options,
  GenericDatabase\Engine\OCI\Attributes,
  GenericDatabase\Engine\OCI\DSN,
  GenericDatabase\Engine\OCI\OCI,
  GenericDatabase\Engine\OCI\Dump,
  GenericDatabase\Engine\OCI\Transaction;

class OCIEngine
{
  use Errors, Caller, Cleaner, Singleton;

  /**
   * This method is responsible for call the static instance to Arguments class with a Magic Method __call and __callStatic.
   * 
   * @param string $method
   * @param array $arguments
   * @return OCIEngine
   */
  private static function call(string $method, array $arguments): OCIEngine
  {
    return Arguments::call($method, $arguments);
  }

  /**
   * This method is responsible for prepare the connection options before connect.
   * 
   * @return OCIEngine
   */
  private function preConnect(): OCIEngine
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
   * @return OCIEngine
   */
  private function postConnect(): OCIEngine
  {
    Options::define();
    Attributes::define();
    return $this;
  }

  /**
   * This method is responsible for creating a new instance of the OCIEngine connection.
   * 
   * @param string $dsn
   * @return OCIEngine 
   */
  private function realConnect(string $host = null, string $user, string $password = null, string $database, int $port): OCIEngine
  {
    $dsn = vsprintf('%s:%s/%s', [$host, $port, $database]);
    $this->setConnection((string) !Options::getOptions(OCI::ATTR_PERSISTENT) ? oci_connect($user, $password, $dsn, $this->getCharset()) : oci_pconnect($user, $password, $dsn, $this->getCharset()));
    return $this;
  }

  /**
   * This method is used to establish a database connection and set the connection instance
   * 
   * @return OCIEngine
   */
  public function connect(): OCIEngine
  {
    try {
      $this
        ->preConnect()
        ->setInstance($this)
        ->setDsn($this->parseDsn())
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
  private function parseDsn(): string|\Exception
  {
    return DSN::parseDsn();
  }

  /**
   * This method is used to get the database connection instance
   * 
   * @return mixed
   */
  public function getConnection(): mixed
  {
    return $GLOBALS['connection'];
  }

  /**
   * This method is used to assign the database connection instance
   * 
   * @param mixed $connection
   * @return mixed
   */
  public function setConnection(mixed $connection): mixed
  {
    return $GLOBALS['connection'] = $connection;
  }

  /**
   * Import SQL dump from file - extremely fast.
   * 
   * @param string $file 
   * @param string $delimiter = ';'
   * @param array<callable(int, ?float): void> $onProgress = null
   * @return int
   */
  public function loadFromFile(string $file, string $delimiter = ';', ?callable $onProgress = null): int
  {
    return Dump::loadFromFile($file, $delimiter, $onProgress);
  }

  /**
   * This function creates a new transaction, in order to be able to commit or rollback changes made to the database.
   * 
   * @return bool
   */
  public function beginTransaction(): bool
  {
    return Transaction::beginTransaction();
  }

  /**
   * This function commits any changes made to the database during this transaction.
   * 
   * @return bool
   */
  public function commit(): bool
  {
    return Transaction::commit();
  }

  /**
   * This function rolls back any changes made to the database during this transaction and restores the data to its original state.
   * 
   * @return bool
   */
  public function rollback(): bool
  {
    return Transaction::rollback();
  }

  /**
   * This function returns the last ID generated by an auto-increment column, either the last one inserted during the current transaction, or by passing in the optional name parameter.
   * 
   * @return bool
   */
  public function inTransaction(): bool
  {
    return Transaction::inTransaction();
  }

  /**
   * This function returns the last ID generated by an auto-increment column, either the last one inserted during the current transaction, or by passing in the optional name parameter.
   * 
   * @param ?string $name = null
   * @return string|false
   */
  public function lastInsertId(?string $name = null): string | false
  {
    return 0;
  }

  /**
   * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
   * 
   * @param mixed $string
   * @param ?bool $quote = false
   * @return string|array|false
   */
  public function quote(mixed $string): string | array | false
  {
    $quoted = function ($string) {
      return str_replace("'", "''", $string);
    };

    if (is_int($string)) {
      return $string;
    } elseif (is_float($string)) {
      return "'" . $quoted(str_replace(',', '.', strval(floatval($string)))) . "'";
    } elseif (is_bool($string)) {
      return $string ? '1' : '0';
    } elseif (is_null($string)) {
      return 'NULL';
    } else {
      return "'" . $quoted($string) . "'";
    }
  }

  /**
   * This function prepares an SQL statement for execution and returns a statement object.
   * 
   * @param mixed $statement
   * @param mixed $param
   * @param mixed $value
   * @return mixed
   */
  public function prepare(mixed $statement, mixed $param, mixed $value): mixed
  {
    if (is_numeric($value)) {
      oci_bind_by_name($statement, $param, $value,  8, SQLT_INT);
    } else if (is_float($value)) {
      oci_bind_by_name($statement, $param, floatval($value),  8, SQLT_FLT);
    } else if (is_string($value)) {
      $value = (string) $value;
      oci_bind_by_name($statement, $param, $value, -1, SQLT_CHR);
    } else if (is_bool($value)) {
      $value = (bool) $value;
      oci_bind_by_name($statement, $param, $value, -1, SQLT_BOL);
    } else if (is_array($value)) {
      foreach ($param as $key => $val) {
        oci_bind_by_name($statement, $key, $param[$key]);
      }
    }
    return $this;
  }

  /**
   * This function executes an SQL statement and returns the result set as a statement object.
   * 
   * @param string $query
   * @return object|false
   */
  public function query(string $query): mixed
  {
    return oci_parse($this->getInstance()->getConnection(), $query);
  }

  /**
   * This function runs an SQL statement and returns the number of affected rows.
   * 
   * @param mixed $statement
   * @return mixed
   */
  public function exec(mixed $statement): mixed
  {
    return oci_execute($statement, OCI_DEFAULT);
  }

  /**
   * This function retrieves an attribute from the database.
   * 
   * @param int $attribute
   * @return mixed
   */
  public function getAttribute($name)
  {
    return OCI::getAttribute($name);
  }

  /**
   * This function returns an array containing error information about the last operation performed by the database.
   * 
   * @return string
   */
  public function setAttribute($name, $value)
  {
    return OCI::setAttribute($name, $value);
  }

  /**
   * This function returns an SQLSTATE code for the last operation executed by the database.
   * @param mixed $inst
   * @return string
   */
  public function errorCode($inst): string
  {
    $m = oci_error($inst);
    return $m['code'];
  }

  /**
   * This function returns an array containing error information about the last operation performed by the database.
   * @param mixed $inst
   * @return string
   */
  public function errorInfo($inst): string
  {
    $m = oci_error($inst);
    return $m['message'];
  }
}