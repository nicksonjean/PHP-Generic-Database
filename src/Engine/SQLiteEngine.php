<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use AllowDynamicProperties;
use Exception;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\SQLite\SQLite;
use GenericDatabase\Engine\SQLite\Arguments;
use GenericDatabase\Engine\SQLite\Options;
use GenericDatabase\Engine\SQLite\Attributes;
use GenericDatabase\Engine\SQLite\DSN;
use GenericDatabase\Engine\SQLite\Dump;
use GenericDatabase\Engine\SQLite\Transaction;
use GenericDatabase\Helpers\GenericException;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Traits\Setter;
use GenericDatabase\Traits\Getter;
use GenericDatabase\Traits\Cleaner;
use GenericDatabase\Traits\Singleton;
use SQLite3;

/**
 * Dynamic and Static container class for SQLiteEngine connections.
 *
 * @method static SQLiteEngine|static setDriver(mixed $value): void
 * @method static SQLiteEngine|static getDriver($value = null): mixed
 * @method static SQLiteEngine|static setHost(mixed $value): void
 * @method static SQLiteEngine|static getHost($value = null): mixed
 * @method static SQLiteEngine|static setPort(mixed $value): void
 * @method static SQLiteEngine|static getPort($value = null): mixed
 * @method static SQLiteEngine|static setUser(mixed $value): void
 * @method static SQLiteEngine|static getUser($value = null): mixed
 * @method static SQLiteEngine|static setPassword(mixed $value): void
 * @method static SQLiteEngine|static getPassword($value = null): mixed
 * @method static SQLiteEngine|static setDatabase(mixed $value): void
 * @method static SQLiteEngine|static getDatabase($value = null): mixed
 * @method static SQLiteEngine|static setOptions(mixed $value): void
 * @method static SQLiteEngine|static getOptions($value = null): mixed
 * @method static SQLiteEngine|static setConnected(mixed $value): void
 * @method static SQLiteEngine|static getConnected($value = null): mixed
 * @method static SQLiteEngine|static setDsn(mixed $value): void
 * @method static SQLiteEngine|static getDsn($value = null): mixed
 * @method static SQLiteEngine|static setAttributes(mixed $value): void
 * @method static SQLiteEngine|static getAttributes($value = null): mixed
 * @method static SQLiteEngine|static setCharset(mixed $value): void
 * @method static SQLiteEngine|static getCharset($value = null): mixed
 * @method static SQLiteEngine|static setException(mixed $value): void
 * @method static SQLiteEngine|static getException($value = null): mixed
 */
#[AllowDynamicProperties]
class SQLiteEngine implements IConnection
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
     * @return SQLiteEngine|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): SQLiteEngine|string|int|bool|array|null
    {
        $method = substr($name, 0, 3);
        $field = strtolower(substr($name, 3));
        if ($method == 'set') {
            $this->__set($field, ...$arguments);
        } elseif ($method == 'get') {
            return $this->__get($field);
        }
        return $this;
    }

    /**
     * Triggered when invoking inaccessible methods in a static context
     *
     * @param string $name Name of the static method
     * @param array $arguments Array of arguments
     * @return SQLiteEngine
     */
    public static function __callStatic(string $name, array $arguments): SQLiteEngine
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return SQLiteEngine
     */
    private function preConnect(): SQLiteEngine
    {
        Options::setOptions((array) $this->getOptions());
        $options = Options::getOptions();
        $this->setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return SQLiteEngine
     * @throws GenericException
     */
    private function postConnect(): SQLiteEngine
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the SQLiteEngine connection.
     *
     * @param string $database The path of the database file
     * @param int|null $flags = null Flags of the database behavior
     * @return SQLiteEngine
     * @throws Exception
     */
    private function realConnect(string $database, int $flags = null): SQLiteEngine
    {
        if (!$flags) {
            $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
        }
        $database = $database !== 'memory' ? $database : ':' . $database . ':';
        $this->setConnection(new SQLite3($database, $flags));
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return SQLiteEngine
     * @throws Exception
     */
    public function connect(): SQLiteEngine
    {
        try {
            $this
                ->preConnect()
                ->setInstance($this)
                ->setDsn($this->parseDsn())
                ->realConnect(
                    (string) $this->getDatabase(),
                    Options::flags()
                )
                ->postConnect()
                ->setConnected(true);
            return $this;
        } catch (Exception $error) {
            $this->disconnect();
            Errors::throw($error);
        }
    }

    /**
     * Pings a server connection, or tries to reconnect if the connection has gone down
     *
     * @return bool
     */
    public function ping(): bool
    {
        return $this->query('SELECT 1') !== false;
    }

    /**
     * Disconnects from a database.
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->isConnected()) {
            $this->setConnected(false);
            if (!Options::getOptions(SQLite::ATTR_PERSISTENT)) {
                if (Compare::connection($this->getConnection()) === 'sqlite3') {
                    $this->getConnection()->close();
                }
                $this->setConnection(null);
            }
        }
    }

    /**
     * Returns true when connection was established.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return (Compare::connection($this->getConnection()) === 'sqlite3') && $this->getConnected();
    }

    /**
     * This method is responsible for parsing the DSN from DSN class.
     *
     * @return string|GenericException
     * @throws GenericException
     */
    private function parseDsn(): string|GenericException
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
     * @param mixed $connection Sets an instance of the connection with the database
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
     *  this transaction and restores the data to its original state.
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return Transaction::rollback();
    }

    /**
     * This function returns the last ID generated by an auto-increment column,
     *  either the last one inserted during the current transaction, or by passing in the optional name parameter.
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return Transaction::inTransaction();
    }

    /**
     * This function returns the last ID generated by an auto-increment column,
     *  either the last one inserted during the current transaction, or by passing in the optional name parameter.
     *
     * @param ?string $name = null Resource name, table or view
     * @return string|int|false
     */
    public function lastInsertId(?string $name = null): string|int|false
    {
        return $this->getConnection()->lastInsertRowID();
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
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return mixed
     */
    public function prepare(mixed ...$params): mixed
    {
        $query = $params[0];
        return $this->getConnection()->prepare($query);
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
        return $this->getConnection()->query($query);
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
        return $this->getConnection()->exec($query);
    }

    /**
     * This function retrieves an attribute from the database.
     *
     * @param mixed $name The attribute name
     * @return mixed
     */
    public function getAttribute(mixed $name): mixed
    {
        return SQLite::getAttribute($name);
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $name The attribute name
     * @param mixed $value The attribute value
     * @return void
     */
    public function setAttribute(mixed $name, mixed $value): void
    {
        SQLite::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorCode(mixed $inst = null): mixed
    {
        return $this->getConnection()->lastErrorCode();
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorInfo(mixed $inst = null): mixed
    {
        return $this->getConnection()->lastErrorMsg();
    }
}
