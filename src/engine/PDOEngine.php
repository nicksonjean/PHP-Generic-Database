<?php

namespace GenericDatabase;

use Errors, Caller, Cleaner, Singleton, Exception, PDO, PDOException, PDOStatement;

class PDOEngine
{
  use Errors, Caller, Cleaner, Singleton;

  /**
   * private database connection like PDO, MySQLi etc...
   */
  private static $connection;

  /**
   * This method is responsible for call the static instance to Arguments class with a Magic Method __call and __callStatic.
   * 
   * @return void
   */
  public static function call($method, $arguments): mixed
  {
    return PDOArguments::call($method, $arguments);
  }

  /**
   * This method is responsible for prepare the connection options before connect.
   * 
   * @return void
   */
  public function preConnect(): void
  {
    if (!in_array($this->getDriver(), (array) $this->getAvailableDrivers())) {
      $message = sprintf(
        "Driver '%s' is invalid, set the driver property with one of these options: '%s'",
        [$this->getDriver(), implode(', ', (array) $this->getAvailableDrivers())]
      );
      throw new Exception($message);
    }

    $result = [];
    $result += $this->getOptions();
    $result += [PDO::ATTR_ERRMODE => ($this->getException()) ? PDO::ERRMODE_WARNING : PDO::ERRMODE_SILENT];
    switch ($this->getDriver()) {
      case 'mysql':
        if ($this->getCharset()) {
          $result += [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '" . $this->getCharset() . "';"];
        }
        $result += [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true];
      case 'pgsql':
        $result += [PDO::ATTR_AUTOCOMMIT => true];
        break;
      case 'sqlsrv':
        $result += [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_SYSTEM];
        break;
      case 'sqlite':
        unset($this->user, $this->password);
        break;
      default:
        $result += [PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING];
    }
    $this->setOptions($result);
    $this->setInstance($this);
  }

  /**
   * This method is responsible for update in date late binding the connection.
   * 
   * @return void
   */
  public function postConnect(): void
  {
    switch ($this->getDriver()) {
      case 'mysql':
        if ($this->getCharset()) {
          $this->getConnection()->exec("SET NAMES '{$this->getCharset()}'");
        }
        break;
      case 'pgsql':
        if ($this->getCharset()) {
          $this->getConnection()->exec("SET CLIENT_ENCODING TO '{$this->getCharset()}'");
        }
        break;
      case 'sqlite':
        $this->getConnection()->query('PRAGMA foreign_keys = ON');
        break;
    }
    PDOAttributes::fetchAll();
    unset($this->argumentList);
    $this->setInstance($this);
  }

  /**
   * This method is responsible for parsing the DSN from PDODSN class.
   * 
   * @return string|object
   */
  public function parseDns(): string|Exception
  {
    return PDODSN::parseDns();
  }

  /**
   * This method is used to establish a database connection and set the connection instance
   * 
   * @return object
   */
  public function connect(): PDOEngine
  {
    try {
      $this->preConnect();
      $this->setConnection(new PDO($this->parseDns(), $this->getUser(), $this->getPassword(), $this->getOptions()));
      $this->setInstance($this);
      $this->postConnect();
      $this->setConnected(true);
      return $this;
    } catch (PDOException | Exception $error) {
      $this->setConnected(false);
      Errors::throw($error);
    }
  }

  /**
   * This method is used to get the database connection instance
   * 
   * @return object
   */
  public function getConnection(): PDO
  {
    return $this->connection;
  }

  /**
   * This method is used to assign the database connection instance
   * 
   * @param object $connection
   * @return object
   */
  public function setConnection(PDO $connection): PDO
  {
    $this->connection = $connection;
    return $this->connection;
  }

  /**
   * Import SQL dump from file - extremely fast.
   * 
   * @param string $file 
   * @param string $delimiter
   * @param array<callable(int, ?float): void> $onProgress
   * @return int Count of Commands
   */
  public function loadFromFile(string $file, string $delimiter = ';', ?callable $onProgress = null): int
  {
    return PDODump::loadFromFile($file, $delimiter, $onProgress);
  }

  /**
   * Result all PDO Supported Drivers
   * 
   * @return object attribut list array
   */
  public function getAvailableDrivers(): array
  {
    return PDO::getAvailableDrivers();
  }

  /**
   * This function creates a new transaction, in order to be able to commit or rollback changes made to the database.
   * 
   * @return bool
   */
  public function beginTransaction(): bool
  {
    return $this->getConnection()->beginTransaction();
  }

  /**
   * This function commits any changes made to the database during this transaction.
   * 
   * @return bool
   */
  public function commit(): bool
  {
    return $this->getConnection()->commit();
  }

  /**
   * This function rolls back any changes made to the database during this transaction and restores the data to its original state.
   * 
   * @return bool
   */
  public function rollback(): bool
  {
    return $this->getConnection()->rollback();
  }

  /**
   * This function returns the last ID generated by an auto-increment column, either the last one inserted during the current transaction, or by passing in the optional name parameter.
   * 
   * @return bool
   */
  public function inTransaction(): bool
  {
    return $this->getConnection()->inTransaction();
  }

  /**
   * This function returns the last ID generated by an auto-increment column, either the last one inserted during the current transaction, or by passing in the optional name parameter.
   * 
   * @param ?string $name
   * @return string|false
   */
  public function lastInsertId(?string $name = null): string | false
  {
    return $this->getConnection()->lastInsertId($name);
  }

  /**
   * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
   * 
   * @param string $string
   * @param ?int $type
   * @return string|false
   */
  public function quote(string $string, int $type = PDO::PARAM_STR): string | false
  {
    return $this->getConnection()->quote($string, $type);
  }

  /**
   * This function prepares an SQL statement for execution and returns a statement object.
   * 
   * @param string $query
   * @param ?array $options
   * @return object|false
   */
  public function prepare(string $query, ?array $options = []): PDOStatement | false
  {
    return $this->getConnection()->prepare($query, $options);
  }

  /**
   * This function executes an SQL statement and returns the result set as a statement object.
   * 
   * @param string $query
   * @param ?int $fetchMode
   * @return object|false
   */
  public function query(string $query, ?int $fetchMode = null): PDOStatement | false
  {
    return $this->getConnection()->query($query, $fetchMode);
  }

  /**
   * This function runs an SQL statement and returns the number of affected rows.
   * 
   * @param string $statement
   * @return int|false
   */
  public function exec(string $statement): int | false
  {
    return $this->getConnection()->exec($statement);
  }

  /**
   * This function retrieves an attribute from the database.
   * 
   * @param int $attribute
   * @return mixed
   */
  public function getAttribute(int $attribute): mixed
  {
    return $this->getConnection()->getAttribute($attribute);
  }

  /**
   * This function sets an attribute on the database.
   * 
   * @param int $attribute
   * @param string $value
   * @return object|false
   */
  public function setAttribute(int $attribute, string $value): PDOStatement | false
  {
    return $this->getConnection()->setAttribute($attribute, $value);
  }

  /**
   * This function returns an SQLSTATE code for the last operation executed by the database.
   * 
   * @return ?string
   */
  public function errorCode(): ?string
  {
    return $this->getConnection()->errorCode();
  }

  /**
   * This function returns an array containing error information about the last operation performed by the database.
   * 
   * @return array
   */
  public function errorInfo(): array
  {
    return $this->getConnection()->errorInfo();
  }
}
