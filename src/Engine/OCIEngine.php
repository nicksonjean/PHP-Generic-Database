<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use AllowDynamicProperties;
use Exception;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\OCI\OCI;
use GenericDatabase\Helpers\GenericException;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Traits\Setter;
use GenericDatabase\Traits\Getter;
use GenericDatabase\Traits\Cleaner;
use GenericDatabase\Traits\Singleton;
use GenericDatabase\Engine\OCI\Arguments;
use GenericDatabase\Engine\OCI\Options;
use GenericDatabase\Engine\OCI\Attributes;
use GenericDatabase\Engine\OCI\DSN;
use GenericDatabase\Engine\OCI\Dump;
use GenericDatabase\Engine\OCI\Transaction;

/**
 * @method static OCIEngine|static setDriver(mixed $value): void
 * @method static OCIEngine|static getDriver($p = null): mixed
 * @method static OCIEngine|static setHost(mixed $value): void
 * @method static OCIEngine|static getHost($p = null): mixed
 * @method static OCIEngine|static setPort(mixed $value): void
 * @method static OCIEngine|static getPort($p = null): mixed
 * @method static OCIEngine|static setUser(mixed $value): void
 * @method static OCIEngine|static getUser($p = null): mixed
 * @method static OCIEngine|static setPassword(mixed $value): void
 * @method static OCIEngine|static getPassword($p = null): mixed
 * @method static OCIEngine|static setDatabase(mixed $value): void
 * @method static OCIEngine|static getDatabase($p = null): mixed
 * @method static OCIEngine|static setOptions(mixed $value): void
 * @method static OCIEngine|static getOptions($p = null): mixed
 * @method static OCIEngine|static setConnected(mixed $value): void
 * @method static OCIEngine|static getConnected($p = null): mixed
 * @method static OCIEngine|static setDsn(mixed $value): void
 * @method static OCIEngine|static getDsn($p = null): mixed
 * @method static OCIEngine|static setAttributes(mixed $value): void
 * @method static OCIEngine|static getAttributes($p = null): mixed
 * @method static OCIEngine|static setCharset(mixed $value): void
 * @method static OCIEngine|static getCharset($p = null): mixed
 * @method static OCIEngine|static setException(mixed $value): void
 * @method static OCIEngine|static getException($p = null): mixed
 */
#[AllowDynamicProperties]
class OCIEngine implements IConnection
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
     * @return OCIEngine
     */
    public static function __callStatic(string $name, array $arguments): OCIEngine
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return OCIEngine
     */
    private function preConnect(): OCIEngine
    {
        Options::setOptions((array) $this->getOptions());
        $options = Options::getOptions();
        $this->setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return OCIEngine
     * @throws GenericException
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
     * @param string $host The host of the database
     * @param string $user The user of the database
     * @param string $password The password of the database
     * @param string $database The name of the database
     * @param mixed $port The port of the database
     * @param string $charset The charset of the database
     * @return OCIEngine
     */
    private function realConnect(
        string $host,
        string $user,
        string $password,
        string $database,
        mixed $port,
        string $charset
    ): OCIEngine {
        $dsn = vsprintf('%s:%s/%s', [$host, $port, $database]);
        $this->setConnection(
            (string) !Options::getOptions(OCI::ATTR_PERSISTENT)
                ? oci_connect($user, $password, $dsn, $charset)
                : oci_pconnect($user, $password, $dsn, $charset)
        );
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
                ->realConnect(
                    (string) $this->getHost(),
                    (string) $this->getUser(),
                    (string) $this->getPassword(),
                    (string) $this->getDatabase(),
                    $this->getPort(),
                    (string) $this->getCharset()
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
     * Pings a server connection, or tries to reconnect if the connection has gone down
     *
     * @param mixed $connection A link identifier returned by a simple query
     * @return bool
     */
    public function ping(mixed $connection): bool
    {
        $result = $this->query($connection, 'SELECT 1 FROM DUAL');
        return $this->exec($result) !== false;
    }

    /**
     * Disconnects from a database.
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->getConnection() !== null && $this->ping($this->getConnection())) {
            oci_close($this->getConnection());
            $this->connection = null;
        }
    }

    /**
     * Returns true when connection was established.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return (bool) $this->getConnected();
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
     * @return mixed
     */
    public function quote(mixed ...$params): mixed
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
        $param = $params[1];
        $value = $params[2];
        if (is_numeric($value) && is_string($value) && str_contains($value, '.')) {
            $floatValue = (float) $value;
            oci_bind_by_name($query, $param, $floatValue, 8, SQLT_FLT);
        } elseif (is_string($value)) {
            $stringValue = $value;
            oci_bind_by_name($query, $param, $stringValue, -1);
        } elseif (is_bool($value)) {
            $boolValue = $value;
            oci_bind_by_name($query, $param, $boolValue, -1, SQLT_BOL);
        } elseif (is_array($value)) {
            foreach ($param as $key) {
                oci_bind_by_name($query, $key, $value[$key]);
            }
        }
        return $this;
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
        return oci_parse($this->getInstance()->getConnection(), $query);
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
        $resultMode = isset($params[1]) ? (int) $params[1] : OCI_COMMIT_ON_SUCCESS;
        return oci_execute($query, $resultMode);
    }

    /**
     * This function retrieves an attribute from the database.
     *
     * @param mixed $name The attribute name
     * @return mixed
     */
    public function getAttribute(mixed $name): mixed
    {
        return OCI::getAttribute($name);
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
        OCI::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorCode(mixed $inst = null): mixed
    {
        $error = oci_error($inst);
        return $error['code'];
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorInfo(mixed $inst = null): mixed
    {
        $error = oci_error($inst);
        return $error['message'];
    }
}
