<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use
  GenericDatabase\InterfaceConnection,

  GenericDatabase\Traits\Errors,
  GenericDatabase\Traits\Caller,
  GenericDatabase\Traits\Cleaner,
  GenericDatabase\Traits\Singleton,
  GenericDatabase\Engine\FBird\Arguments,
  GenericDatabase\Engine\FBird\Options,
  GenericDatabase\Engine\FBird\Attributes,
  GenericDatabase\Engine\FBird\DSN,
  GenericDatabase\Engine\FBird\FBird,
  GenericDatabase\Engine\FBird\Dump,
  GenericDatabase\Engine\FBird\Transaction;

class FBirdEngine implements InterfaceConnection
{
    use Errors;
    use Caller;
    use Cleaner;
    use Singleton;

  /**
   *  Instance of the connection with database
   */
    private $connection;

  /**
   * This method is responsible for call the static instance to Arguments class with a Magic Method __call and __callStatic.
   *
   * @param string $method The method name to be called
   * @param array $arguments The arguments of the method
   * @return FBirdEngine
   */
    private static function call(string $method, array $arguments): FBirdEngine
    {
        return Arguments::call($method, $arguments);
    }

  /**
   * This method is responsible for prepare the connection options before connect.
   *
   * @return FBirdEngine
   */
    private function preConnect(): FBirdEngine
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
   * @return FBirdEngine
   */
    private function postConnect(): FBirdEngine
    {
        Options::define();
        Attributes::define();
        return $this;
    }

  /**
   * This method is responsible for creating a new instance of the FBirdEngine connection.
   *
   * @param string $host The host of the database
   * @param string $user The user of the database
   * @param string $password The password of the database
   * @param string $database The name of the database
   * @param int $port The port of the database
   * @return FBirdEngine
   */
    private function realConnect(string $host, string $user, string $password, string $database, int $port): FBirdEngine
    {
        $dsn = vsprintf('%s/%s:%s', [$host, $port, $database]);
        $this->setConnection((string) !Options::getOptions(FBird::ATTR_PERSISTENT) ? fbird_connect($dsn, $user, $password) : fbird_pconnect($dsn, $user, $password));
        return $this;
    }

  /**
   * This method is used to establish a database connection and set the connection instance
   *
   * @return FBirdEngine
   */
    public function connect(): FBirdEngine
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
        return $this->connection;
    }

  /**
   * This method is used to assign the database connection instance
   *
   * @param mixed $connection Sets a intance of the connection with the database
   * @return mixed
   */
    public function setConnection(mixed $connection): mixed
    {
        $this->connection = $connection;
        return $this->connection;
    }

  /**
   * Import SQL dump from file - extremely fast.
   *
   * @param string $file The file dumped to be imported
   * @param string $delimiter = ';' The delimiter of the dump
   * @param ?callable $onProgress = null
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
   * @param ?string $name = null Resource name, table or view
   * @return string|int|false
   */
    public function lastInsertId(?string $name = null): string|int|false
    {
        return 0;
    }

  /**
   * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
   *
   * @param mixed $params Content to be quoted
   * @return mixed
   */
    public function quote(mixed ...$params): mixed
    {
        $string = $params[0];
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
   * @param mixed $params Statement to be prepared
   * @return mixed
   */
    public function prepare(mixed ...$params): mixed
    {
        $query = $params[0];
        $transaction = isset($params[1]) ?? null;
        return (is_null($transaction) ? fbird_prepare($this->getInstance()->getConnection(), $query) : fbird_prepare($this->getInstance()->getConnection(), $transaction, $query));
    }

  /**
   * This function executes an SQL statement and returns the result set as a statement object.
   *
   * @param mixed $params Statement to be queried
   * @return mixed
   */
    public function query(mixed ...$params): mixed
    {
        $query = $params[0];
        return fbird_query($this->getInstance()->getConnection(), $query);
    }

  /**
   * This function runs an SQL statement and returns the number of affected rows.
   *
   * @param mixed $params Statement to be executed
   * @return mixed
   */
    public function exec(mixed ...$params): mixed
    {
        $query = $params[0];
        $param = $params[1];
        if (!is_array($param)) {
            return fbird_execute($query, $param);
        }
        array_unshift($param, $query);
        $rc = call_user_func_array('fbird_execute', $param);
        return $rc;
    }

  /**
   * This function retrieves an attribute from the database.
   *
   * @param mixed $name The attribute name
   * @return mixed
   */
    public function getAttribute(mixed $name): mixed
    {
        return FBird::getAttribute($name);
    }

  /**
   * This function sets an attribute from the database.
   *
   * @param mixed $name The attribute name
   * @param mixed $value The attribute value
   * @return mixed
   */
    public function setAttribute(mixed $name, mixed $value): mixed
    {
        return FBird::setAttribute($name, $value);
    }

  /**
   * This function returns an SQLSTATE code for the last operation executed by the database.
   *
   * @param ?int $inst = null Resource name, table or view
   * @return mixed
   */
    public function errorCode(?int $inst = null): mixed
    {
        return fbird_errcode();
    }

  /**
   * This function returns an array containing error information about the last operation performed by the database.
   *
   * @param ?int $inst = null Resource name, table or view
   * @return mixed
   */
    public function errorInfo(?int $inst = null): mixed
    {
        return fbird_errmsg();
    }
}
