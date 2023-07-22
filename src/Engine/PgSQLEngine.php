<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use AllowDynamicProperties;
use Exception;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\PgSQL\PgSQL;
use GenericDatabase\Engine\PgSQL\Arguments;
use GenericDatabase\Engine\PgSQL\Options;
use GenericDatabase\Engine\PgSQL\Attributes;
use GenericDatabase\Engine\PgSQL\DSN;
use GenericDatabase\Engine\PgSQL\Dump;
use GenericDatabase\Engine\PgSQL\Transaction;
use GenericDatabase\Helpers\GenericException;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Traits\Setter;
use GenericDatabase\Traits\Getter;
use GenericDatabase\Traits\Cleaner;
use GenericDatabase\Traits\Singleton;
use PgSql\Connection;
use PgSql\Result;

/**
 * Dynamic and Static container class for PgSQLEngine connections.
 *
 * @method static PgSQLEngine|static setDriver(mixed $value): void
 * @method static PgSQLEngine|static getDriver($value = null): mixed
 * @method static PgSQLEngine|static setHost(mixed $value): void
 * @method static PgSQLEngine|static getHost($value = null): mixed
 * @method static PgSQLEngine|static setPort(mixed $value): void
 * @method static PgSQLEngine|static getPort($value = null): mixed
 * @method static PgSQLEngine|static setUser(mixed $value): void
 * @method static PgSQLEngine|static getUser($value = null): mixed
 * @method static PgSQLEngine|static setPassword(mixed $value): void
 * @method static PgSQLEngine|static getPassword($value = null): mixed
 * @method static PgSQLEngine|static setDatabase(mixed $value): void
 * @method static PgSQLEngine|static getDatabase($value = null): mixed
 * @method static PgSQLEngine|static setOptions(mixed $value): void
 * @method static PgSQLEngine|static getOptions($value = null): mixed
 * @method static PgSQLEngine|static setConnected(mixed $value): void
 * @method static PgSQLEngine|static getConnected($value = null): mixed
 * @method static PgSQLEngine|static setDsn(mixed $value): void
 * @method static PgSQLEngine|static getDsn($value = null): mixed
 * @method static PgSQLEngine|static setAttributes(mixed $value): void
 * @method static PgSQLEngine|static getAttributes($value = null): mixed
 * @method static PgSQLEngine|static setCharset(mixed $value): void
 * @method static PgSQLEngine|static getCharset($value = null): mixed
 * @method static PgSQLEngine|static setException(mixed $value): void
 * @method static PgSQLEngine|static getException($value = null): mixed
 */
#[AllowDynamicProperties]
class PgSQLEngine implements IConnection
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
     * @return PgSQLEngine|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): PgSQLEngine|string|int|bool|array|null
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
     * @return PgSQLEngine
     */
    public static function __callStatic(string $name, array $arguments): PgSQLEngine
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return PgSQLEngine
     */
    private function preConnect(): PgSQLEngine
    {
        Options::setOptions((array) $this->getOptions());
        $options = Options::getOptions();
        $this->setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return PgSQLEngine
     * @throws GenericException
     */
    private function postConnect(): PgSQLEngine
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the PgSQLEngine connection.
     *
     * @param string $dsn The Data source name of the connection
     * @return PgSQLEngine
     * @throws Exception
     */
    private function realConnect(string $dsn): PgSQLEngine
    {
        $this->setConnection(
            (string) !Options::getOptions(PgSQL::ATTR_PERSISTENT)
                ? pg_connect($dsn, Attributes::getFlags())
                : pg_pconnect($dsn, Attributes::getFlags())
        );
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return PgSQLEngine
     * @throws Exception
     */
    public function connect(): PgSQLEngine
    {
        try {
            $this
                ->preConnect()
                ->setInstance($this)
                ->realConnect(
                    $this->parseDsn()
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
            if (!Options::getOptions(PgSQL::ATTR_PERSISTENT)) {
                if (Compare::connection($this->getConnection()) === 'pgsql') {
                    pg_close($this->getConnection());
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
        return (Compare::connection($this->getConnection()) === 'pgsql') && $this->getConnected();
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
        $filter = sprintf("WHERE column_default LIKE 'nextval%%' AND table_name = '%s'", $name);
        $query = $this->query(sprintf("SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS %s", $filter));
        $autoKeyRes = pg_fetch_assoc($query);
        if (isset($autoKeyRes['column_name'])) {
            $query = vsprintf(
                "SELECT pg_catalog.setval(pg_get_serial_sequence('%s', '%s'), COALESCE(MAX(%s))) AS value FROM %s;",
                [$name, $autoKeyRes['column_name'], $autoKeyRes['column_name'], $name]
            );
            $maxIndex = $this->query($query);
            $maxIndexRes = pg_fetch_assoc($maxIndex);
            return $maxIndexRes['value'];
        } else {
            return 0;
        }
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
        $quote = $params[1];
        if (is_array($string)) {
            return array_map(fn ($str) => $this->quote($str, $quote), $string);
        } elseif ($string && preg_match("/^(?:\d+\.\d+|[1-9]\d*)$/S", $string)) {
            return $string;
        }
        $quoted = fn ($str) => pg_escape_string($this->getConnection(), $str);
        return ($quote) ? "'" . $quoted($string) . "'" : $quoted($string);
    }


    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return bool|Result
     */
    public function prepare(mixed ...$params): bool|Result
    {
        $query = '';
        $stmtname = '';
        if (count($params) === 1) {
            $query = $params[0];
        } elseif (count($params) === 2) {
            $query = $params[0];
            $stmtname = $params[1];
        }
        return pg_prepare($this->getConnection(), $stmtname, $query);
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return bool|Result
     */
    public function query(mixed ...$params): bool|Result
    {
        $query = $params[0];
        return pg_query($this->getConnection(), $query);
    }

    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return bool|Result
     */
    public function exec(mixed ...$params): bool|Result
    {
        $stmtname = $params[0];
        $param = $params[1];
        return pg_execute($this->getConnection(), $stmtname, $param);
    }

    /**
     * This function retrieves an attribute from the database.
     *
     * @param mixed $name The attribute name
     * @return mixed
     */
    public function getAttribute(mixed $name): mixed
    {
        return PgSQL::getAttribute($name);
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
        PgSQL::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param ?Connection $inst = null
     * @return string
     */
    public function errorCode(mixed $inst = null): string
    {
        return pg_last_error($this->getConnection());
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param ?Connection $inst = null
     * @return string
     */
    public function errorInfo(mixed $inst = null): string
    {
        return pg_last_error($this->getConnection());
    }
}
