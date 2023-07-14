<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use AllowDynamicProperties;
use Exception;
use GenericDatabase\Engine\FBird\FBird;
use GenericDatabase\Helpers\GenericException;
use GenericDatabase\InterfaceConnection;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Traits\Setter;
use GenericDatabase\Traits\Getter;
use GenericDatabase\Traits\Cleaner;
use GenericDatabase\Traits\Singleton;
use GenericDatabase\Engine\FBird\Arguments;
use GenericDatabase\Engine\FBird\Options;
use GenericDatabase\Engine\FBird\Attributes;
use GenericDatabase\Engine\FBird\DSN;
use GenericDatabase\Engine\FBird\Dump;
use GenericDatabase\Engine\FBird\Transaction;

/**
 * @method static FBirdEngine|static setDriver(mixed $value): void
 * @method static FBirdEngine|static getDriver($p = null): mixed
 * @method static FBirdEngine|static setHost(mixed $value): void
 * @method static FBirdEngine|static getHost($p = null): mixed
 * @method static FBirdEngine|static setPort(mixed $value): void
 * @method static FBirdEngine|static getPort($p = null): int
 * @method static FBirdEngine|static setUser(mixed $value): void
 * @method static FBirdEngine|static getUser($p = null): mixed
 * @method static FBirdEngine|static setPassword(mixed $value): void
 * @method static FBirdEngine|static getPassword($p = null): mixed
 * @method static FBirdEngine|static setDatabase(mixed $value): void
 * @method static FBirdEngine|static getDatabase($p = null): mixed
 * @method static FBirdEngine|static setOptions(mixed $value): void
 * @method static FBirdEngine|static getOptions($p = null): mixed
 * @method static FBirdEngine|static setConnected(mixed $value): void
 * @method static FBirdEngine|static getConnected($p = null): mixed
 * @method static FBirdEngine|static setDsn(mixed $value): void
 * @method static FBirdEngine|static getDsn($p = null): mixed
 * @method static FBirdEngine|static setAttributes(mixed $value): void
 * @method static FBirdEngine|static getAttributes($p = null): mixed
 * @method static FBirdEngine|static setCharset(mixed $value): void
 * @method static FBirdEngine|static getCharset($p = null): mixed
 * @method static FBirdEngine|static setException(mixed $value): void
 * @method static FBirdEngine|static getException($p = null): mixed
 */
#[AllowDynamicProperties]
class FBirdEngine implements InterfaceConnection
{
    use Setter;
    use Getter;
    use Cleaner;
    use Singleton;

    /**
     * Instance of the connection with database
     * @var mixed $connection
     */
    private mixed $connection;

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
        if ($method == 'set') {
            $this->__set($field, ...$arguments);
            return $this;
        } elseif ($method == 'get') {
            return $this->__get($field);
        }
        return null;
    }

    /**
     * Triggered when invoking inaccessible methods in a static context
     *
     * @param string $name Name of the static method
     * @param array $arguments Array of arguments
     * @return FBirdEngine
     */
    public static function __callStatic(string $name, array $arguments): FBirdEngine
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return FBirdEngine
     */
    private function preConnect(): FBirdEngine
    {
        Options::setOptions((array) $this->getOptions());
        $options = Options::getOptions();
        $this->setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return FBirdEngine
     * @throws GenericException
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
    private function realConnect(
        string $host,
        string $user,
        string $password,
        string $database,
        mixed $port
    ): FBirdEngine {
        $dsn = vsprintf('%s/%s:%s', [$host, $port, $database]);
        $this->setConnection(
            (string) !Options::getOptions(FBird::ATTR_PERSISTENT)
                ? ibase_connect($dsn, $user, $password)
                : ibase_pconnect($dsn, $user, $password)
        );
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
                ->realConnect(
                    (string) $this->getHost(),
                    (string) $this->getUser(),
                    (string) $this->getPassword(),
                    (string) $this->getDatabase(),
                    $this->getPort()
                )
                ->postConnect()
                ->setConnected(true);
            return $this;
        } catch (Exception $error) {
            $this->setConnected(false);
            Errors::throw($error);
        }
    }

    /**
     * This method is responsible for parsing the DSN from DSN class.
     *
     * @return string|Exception
     * @throws Exception
     */
    private function parseDsn(): string|Exception
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
     * @param mixed $connection Sets an intance of the connection with the database
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
     * This function rolls back any changes made to the database during
     * this transaction and restores the data to its original state.
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return Transaction::rollback();
    }

    /**
     * This function returns the last ID generated by an auto-increment column,
     * either the last one inserted during the current transaction, or by passing in the optional name parameter.
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return Transaction::inTransaction();
    }

    /**
     * This function returns the last ID generated by an auto-increment column,
     * either the last one inserted during the current transaction, or by passing in the optional name parameter.
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
     * @return string|int
     */
    public function quote(mixed ...$params): string|int
    {
        $string = $params[0];
        return match (true) {
            is_int($string) => $string,
            is_float($string) => "'" . str_replace(',', '.', strval($string)) . "'",
            is_bool($string) => $string ? '1' : '0',
            is_null($string) => 'NULL',
            default => "'" . str_replace("'", "''", $string) . "'",
        };
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
        $transaction = isset($params[1]) ? (string) $params[1] : null;
        return $transaction === null
            ? ibase_prepare($this->getInstance()->getConnection(), $query, null)
            : ibase_prepare($this->getInstance()->getConnection(), $transaction, $query);
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
        return ibase_query($this->getInstance()->getConnection(), $query);
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
            return ibase_execute($query, $param);
        }
        array_unshift($param, $query);
        return call_user_func_array('ibase_execute', $param);
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
     * @return void
     */
    public function setAttribute(mixed $name, mixed $value): void
    {
        FBird::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param ?int $inst = null Resource name, table or view
     * @return int|false
     */
    public function errorCode(?int $inst = null): int|false
    {
        return ibase_errcode();
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param ?int $inst = null Resource name, table or view
     * @return string|false
     */
    public function errorInfo(?int $inst = null): string|false
    {
        return ibase_errmsg();
    }
}
