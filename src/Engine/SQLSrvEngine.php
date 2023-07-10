<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use AllowDynamicProperties;
use Exception;
use GenericDatabase\Engine\SQLSrv\SQLSrv;
use GenericDatabase\InterfaceConnection;
use GenericDatabase\Traits\Errors;
use GenericDatabase\Traits\Caller;
use GenericDatabase\Traits\Cleaner;
use GenericDatabase\Traits\Singleton;
use GenericDatabase\Engine\SQLSrv\Arguments;
use GenericDatabase\Engine\SQLSrv\Options;
use GenericDatabase\Engine\SQLSrv\Attributes;
use GenericDatabase\Engine\SQLSrv\DSN;
use GenericDatabase\Engine\SQLSrv\Dump;
use GenericDatabase\Engine\SQLSrv\Transaction;

/**
 * @method static SQLSrvEngine|static setDriver(mixed $value): void
 * @method static SQLSrvEngine|static getDriver(): mixed
 * @method static SQLSrvEngine|static setHost(mixed $value): void
 * @method static SQLSrvEngine|static getHost(): mixed
 * @method static SQLSrvEngine|static setPort(mixed $value): void
 * @method static SQLSrvEngine|static getPort(): mixed
 * @method static SQLSrvEngine|static setUser(mixed $value): void
 * @method static SQLSrvEngine|static getUser(): mixed
 * @method static SQLSrvEngine|static setPassword(mixed $value): void
 * @method static SQLSrvEngine|static getPassword(): mixed
 * @method static SQLSrvEngine|static setDatabase(mixed $value): void
 * @method static SQLSrvEngine|static getDatabase(): mixed
 * @method static SQLSrvEngine|static setOptions(mixed $value): void
 * @method static SQLSrvEngine|static getOptions(): mixed
 * @method static SQLSrvEngine|static setConnected(mixed $value): void
 * @method static SQLSrvEngine|static getConnected(): mixed
 * @method static SQLSrvEngine|static setDsn(mixed $value): void
 * @method static SQLSrvEngine|static getDsn(): mixed
 * @method static SQLSrvEngine|static setAttributes(mixed $value): void
 * @method static SQLSrvEngine|static getAttributes(): mixed
 * @method static SQLSrvEngine|static setCharset(mixed $value): void
 * @method static SQLSrvEngine|static getCharset(): mixed
 * @method static SQLSrvEngine|static setException(mixed $value): void
 * @method static SQLSrvEngine|static getException(): mixed
 */
#[AllowDynamicProperties]
class SQLSrvEngine implements InterfaceConnection
{
    use Errors;
    use Caller;
    use Cleaner;
    use Singleton;

    /**
     *  Instance of the connection with database
     */
    private mixed $connection;

    /**
     * This method is responsible for call the static instance to
     *  Arguments class with a Magic Method __call and __callStatic.
     *
     * @param string $method The method name to be called
     * @param array $arguments The arguments of the method
     * @return SQLSrvEngine
     */
    private static function call(string $method, array $arguments): SQLSrvEngine
    {
        return Arguments::call($method, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return SQLSrvEngine
     */
    private function preConnect(): SQLSrvEngine
    {
        Options::setOptions((array) $this->getOptions());
        $options = Options::getOptions();
        $this->setOptions($options);
        if ($this->getCharset()) {
            $this->setCharset(((string) $this->getCharset() === 'utf8') ? 'UTF-8' : $this->getCharset());
        }
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return SQLSrvEngine
     */
    private function postConnect(): SQLSrvEngine
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the SQLSrvEngine connection.
     *
     * @param string $host The host of the database
     * @param string $user The user of the database
     * @param string $password The password of the database
     * @param string $database The name of the database
     * @param int $port The port of the database
     * @return SQLSrvEngine
     */
    private function realConnect(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port
    ): SQLSrvEngine {
        $sn = vsprintf('%s,%s', [$host, $port]);
        $cnx = ["Database" => $database, "UID" => $user, "PWD" => $password];
        if ($this->getCharset()) {
            $cnx['CharacterSet'] = $this->getCharset();
        }
        if (Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)) {
            $cnx['LoginTimeout'] = Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT);
        }
        $this->setConnection(sqlsrv_connect($sn, $cnx));
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return SQLSrvEngine
     */
    public function connect(): SQLSrvEngine
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
        $statement = $params[0];
        $param = $params[1];
        $options = $params[2];
        return sqlsrv_prepare($this->getInstance()->getConnection(), $statement, $param, $options);
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return mixed
     */
    public function query(mixed ...$params): mixed
    {
        $statement = $params[0];
        $param = $params[1];
        $options = $params[2];
        return sqlsrv_query($this->getInstance()->getConnection(), $statement, $param, $options);
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
        return sqlsrv_execute($query);
    }

    /**
     * This function retrieves an attribute from the database.
     *
     * @param mixed $name The attribute name
     * @return mixed
     */
    public function getAttribute(mixed $name): mixed
    {
        return SQLSrv::getAttribute($name);
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
        SQLSrv::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param ?int $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorCode(?int $inst = null): mixed
    {
        return sqlsrv_errors($inst);
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param ?int $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorInfo(?int $inst = null): mixed
    {
        return sqlsrv_errors($inst);
    }
}
