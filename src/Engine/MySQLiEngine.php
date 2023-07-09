<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use GenericDatabase\InterfaceConnection;
use GenericDatabase\Traits\Errors;
use GenericDatabase\Traits\Caller;
use GenericDatabase\Traits\Cleaner;
use GenericDatabase\Traits\Singleton;
use GenericDatabase\Engine\MySQLi\Arguments;
use GenericDatabase\Engine\MySQLi\Options;
use GenericDatabase\Engine\MySQLi\Attributes;
use GenericDatabase\Engine\MySQLi\DSN;
use GenericDatabase\Engine\MySQLi\Dump;
use GenericDatabase\Engine\MySQLi\Transaction;

/**
 * @method MySQLiEngine setDriver(mixed $value): void
 * @method MySQLiEngine getDriver(): mixed
 * @method MySQLiEngine setHost(mixed $value): void
 * @method MySQLiEngine getHost(): mixed
 * @method MySQLiEngine setPort(mixed $value): void
 * @method MySQLiEngine getPort(): mixed
 * @method MySQLiEngine setUser(mixed $value): void
 * @method MySQLiEngine getUser(): mixed
 * @method MySQLiEngine setPassword(mixed $value): void
 * @method MySQLiEngine getPassword(): mixed
 * @method MySQLiEngine setDatabase(mixed $value): void
 * @method MySQLiEngine getDatabase(): mixed
 * @method MySQLiEngine setOptions(mixed $value): void
 * @method MySQLiEngine getOptions(): mixed
 * @method MySQLiEngine setConnected(mixed $value): void
 * @method MySQLiEngine getConnected(): mixed
 * @method MySQLiEngine setDsn(mixed $value): void
 * @method MySQLiEngine getDsn(): mixed
 * @method MySQLiEngine setAttributes(mixed $value): void
 * @method MySQLiEngine getAttributes(): mixed
 * @method MySQLiEngine setCharset(mixed $value): void
 * @method MySQLiEngine getCharset(): mixed
 * @method MySQLiEngine setException(mixed $value): void
 * @method MySQLiEngine getException(): mixed
 * @method static MySQLiEngine|static setDriver(mixed $value): mixed
 * @method static MySQLiEngine|static getDriver(): mixed
 * @method static MySQLiEngine|static setHost(mixed $value): mixed
 * @method static MySQLiEngine|static getHost(): mixed
 * @method static MySQLiEngine|static setPort(mixed $value): mixed
 * @method static MySQLiEngine|static getPort(): mixed
 * @method static MySQLiEngine|static setUser(mixed $value): mixed
 * @method static MySQLiEngine|static getUser(): mixed
 * @method static MySQLiEngine|static setPassword(mixed $value): mixed
 * @method static MySQLiEngine|static getPassword(): mixed
 * @method static MySQLiEngine|static setDatabase(mixed $value): mixed
 * @method static MySQLiEngine|static getDatabase(): mixed
 * @method static MySQLiEngine|static setOptions(mixed $value): mixed
 * @method static MySQLiEngine|static getOptions(): mixed
 * @method static MySQLiEngine|static setConnected(mixed $value): mixed
 * @method static MySQLiEngine|static getConnected(): mixed
 * @method static MySQLiEngine|static setDsn(mixed $value): mixed
 * @method static MySQLiEngine|static getDsn(): mixed
 * @method static MySQLiEngine|static setAttributes(mixed $value): mixed
 * @method static MySQLiEngine|static getAttributes(): mixed
 * @method static MySQLiEngine|static setCharset(mixed $value): mixed
 * @method static MySQLiEngine|static getCharset(): mixed
 * @method static MySQLiEngine|static setException(mixed $value): mixed
 * @method static MySQLiEngine|static getException(): mixed
 */
#[\AllowDynamicProperties]
class MySQLiEngine implements InterfaceConnection
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
        Options::setOptions((array) $this->getOptions());
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
     * @param string $host The host of the database
     * @param string $user The user of the database
     * @param string $password The password of the database
     * @param string $database The name of the database
     * @param int $port The port of the database
     * @return MySQLiEngine
     */
    private function realConnect(string $host, string $user, string $password, string $database, int $port): MySQLiEngine
    {
        $host = (string) !Options::getOptions(\GenericDatabase\Engine\MySQLi\MySQL::ATTR_PERSISTENT) ? $host : 'p:' . $host;
        $this->setHost($host);
        $this->parseDsn();
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
        if (!$name) {
            return $this->getInstance()->getConnection()->insert_id;
        } else {
            $query = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = ? AND column_key = ? AND extra = ?";
            $stmt = $this->getInstance()->getConnection()->prepare($query);
            $stmt->bind_param("sss", $name, 'PRI', 'auto_increment');
            $stmt->execute();
            $autoKeyResult = $stmt->get_result();
            $autoKey = $autoKeyResult->fetch_assoc();

            if (isset($autoKey['column_name'])) {
                $query = "SELECT COALESCE(MAX(" . $autoKey['column_name'] . ")) AS value FROM " . $name;
                $stmt = $this->getInstance()->getConnection()->prepare($query);
                $stmt->execute();
                $maxIndexResult = $stmt->get_result();
                $maxIndex = $maxIndexResult->fetch_assoc()['value'];

                if ($maxIndex !== null) {
                    return $maxIndex;
                } else {
                    return false;
                }
            } else {
                return 0;
            }
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
            return array_map([get_called_class(), 'quote'], $string, array_fill(0, count($string), $quote));
        } elseif ($string && preg_match("/^(?:\d+\.\d+|[1-9]\d*)$/S", $string)) {
            return $string;
        }

        $quoted = function ($string, $quote) {
            $val = $this->getInstance()->getConnection()->real_escape_string($string);
            return ($quote) ? "'$val'" : $val;
        };
        return $quoted($string, $quote);
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
        return $this->getInstance()->getConnection()->prepare($query);
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
        $result_mode = isset($params[1]) ?? MYSQLI_STORE_RESULT;
        return $this->getInstance()->getConnection()->query($query, $result_mode);
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
        $param = isset($params[1]) ?? null;
        return $this->getInstance()->getConnection()->execute_query($query, $param);
    }

    /**
     * This function retrieves an attribute from the database.
     *
     * @param mixed $name The attribute name
     * @return mixed
     */
    public function getAttribute(mixed $name): mixed
    {
        return \GenericDatabase\Engine\MySQLi\MySQL::getAttribute($name);
    }

    /**
     * This function sets an attribute on the database.
     *
     * @param mixed $name The attribute name
     * @param mixed $value The attribute value
     * @return void
     */
    public function setAttribute(mixed $name, mixed $value): void
    {
        \GenericDatabase\Engine\MySQLi\MySQL::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param ?int $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorCode(?int $inst = null): mixed
    {
        return $this->getInstance()->getConnection()->errno;
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param ?int $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorInfo(?int $inst = null): mixed
    {
        return $this->getInstance()->getConnection()->error;
    }
}
