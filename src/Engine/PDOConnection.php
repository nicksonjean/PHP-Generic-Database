<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use SensitiveParameter;
use AllowDynamicProperties;
use Exception;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\PDO\Connection\Arguments;
use GenericDatabase\Engine\PDO\Connection\Options;
use GenericDatabase\Engine\PDO\Connection\Attributes;
use GenericDatabase\Engine\PDO\Connection\DSN;
use GenericDatabase\Engine\PDO\Connection\Dump;
use GenericDatabase\Engine\PDO\Connection\Transaction;
use GenericDatabase\Engine\PDO\Connection\Statements;
use GenericDatabase\Engine\PDO\Connection\Fetchs;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Shared\Setter;
use GenericDatabase\Shared\Getter;
use GenericDatabase\Shared\Cleaner;
use GenericDatabase\Shared\Singleton;
use PDO;
use PDOException;

/**
 * Dynamic and Static container class for PDOConnection connections.
 *
 * @method static PDOConnection|void setDriver(mixed $value): void
 * @method static PDOConnection|string getDriver($value = null): string
 * @method static PDOConnection|void setHost(mixed $value): void
 * @method static PDOConnection|string getHost($value = null): string
 * @method static PDOConnection|void setPort(mixed $value): void
 * @method static PDOConnection|int getPort($value = null): int
 * @method static PDOConnection|void setUser(mixed $value): void
 * @method static PDOConnection|string getUser($value = null): string
 * @method static PDOConnection|void setPassword(mixed $value): void
 * @method static PDOConnection|string getPassword($value = null): string
 * @method static PDOConnection|void setDatabase(mixed $value): void
 * @method static PDOConnection|string getDatabase($value = null): string
 * @method static PDOConnection|void setOptions(mixed $value): void
 * @method static PDOConnection|array|null getOptions($value = null): array|null
 * @method static PDOConnection|static setConnected(mixed $value): void
 * @method static PDOConnection|mixed getConnected($value = null): mixed
 * @method static PDOConnection|void setDsn(mixed $value): void
 * @method static PDOConnection|mixed getDsn($value = null): mixed
 * @method static PDOConnection|void setAttributes(mixed $value): void
 * @method static PDOConnection|mixed getAttributes($value = null): mixed
 * @method static PDOConnection|void setCharset(mixed $value): void
 * @method static PDOConnection|string getCharset($value = null): string
 * @method static PDOConnection|void setException(mixed $value): void
 * @method static PDOConnection|mixed getException($value = null): mixed
 */
#[AllowDynamicProperties]
class PDOConnection implements IConnection
{
    use Setter;
    use Getter;
    use Cleaner;
    use Singleton;

    /**
     * Instance of the connection with database
     * @var mixed $connection
     */
    private static mixed $connection;

    /**
     * Empty constructor since initialization is handled by traits and interface methods
     */
    public function __construct() {}

    /**
     * Triggered when invoking inaccessible methods in an object context
     *
     * @param string $name Name of the method
     * @param array $arguments Array of arguments
     * @return PDOConnection|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): PDOConnection|string|int|bool|array|null
    {
        $method = substr($name, 0, 3);
        $field = mb_strtolower(substr($name, 3));
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
     * @return PDOConnection
     */
    public static function __callStatic(string $name, array $arguments): PDOConnection
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return PDOConnection
     * @throws Exceptions
     */
    private function preConnect(): PDOConnection
    {
        Options::setOptions((array) static::getOptions());
        $options = Options::getOptions();
        static::setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return PDOConnection
     * @throws Exceptions
     */
    private function postConnect(): PDOConnection
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the PDO connection.
     *
     * @param string $dsn The Data source name of the connection
     * @param ?string $user = null The user of the database
     * @param ?string $password = null The password of the database
     * @param ?array $options = null The options of the database
     * @return PDOConnection
     * @throws Exception
     */
    private function realConnect(
        string $dsn,
        mixed $user = null,
        #[SensitiveParameter] mixed $password = null,
        mixed $options = null
    ): PDOConnection {
        $this->setConnection(new PDO($dsn, $user, $password, $options));
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return PDOConnection
     * @throws PDOException|Exception
     */
    public function connect(): PDOConnection
    {
        if (!extension_loaded('pdo')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'pdo',
                'PHP.ini'
            );
            throw new Exceptions($message);
        }

        try {
            $this->setInstance($this);
            $this
                ->preConnect()
                ->getInstance()
                ->realConnect(
                    $this->parseDsn(),
                    static::getUser(),
                    static::getPassword(),
                    static::getOptions()
                )
                ->postConnect()
                ->setConnected(true);
            return $this;
        } catch (PDOException | Exception $error) {
            $this->disconnect();
            die(Errors::throw($error));
        }
    }

    /**
     * Pings a server connection, or tries to reconnect if the connection has gone down
     *
     * @return bool
     */
    public function ping(): bool
    {
        $query = 'SELECT 1';
        if (static::getDriver() == 'oci') {
            $query .= ' FROM DUAL';
        } elseif (static::getDriver() == 'ibase' || static::getDriver() == 'firebird') {
            $query .= ' FROM RDB$DATABASE';
        }
        return $this->query($query) !== false;
    }

    /**
     * Disconnects from a database.
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->getConnection() !== null && $this->ping()) {
            static::setConnected(false);
            if (!$this->getAttribute(PDO::ATTR_PERSISTENT)) {
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
        return ($this->getConnection() !== null) && $this->getInstance()->getConnected();
    }

    /**
     * This method is responsible for parsing the DSN from DSN class.
     *
     * @return string|Exception
     * @throws Exception
     */
    private function parseDsn(): string|Exception
    {
        return DSN::parse();
    }

    /**
     * This method is used to get the database connection instance
     *
     * @return mixed
     */
    public function getConnection(): mixed
    {
        return self::$connection;
    }

    /**
     * This method is used to assign the database connection instance
     *
     * @param mixed $connection Sets an instance of the connection with the database
     * @return mixed
     */
    public function setConnection(mixed $connection): mixed
    {
        self::$connection = $connection;
        return self::$connection;
    }

    /**
     * Import SQL dump from file - extremely fast.
     *
     * @param string $file The file dumped to be imported
     * @param string $delimiter = ';' The delimiter of the dump
     * @param ?callable $onProgress = null
     * @return int
     * @throws Exception
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
     * either the last one inserted during the current transaction, or by passing in the optional name parameter.
     *
     * @param ?string $name = null Resource name, table or view
     * @return string|int|false
     */
    public function lastInsertId(?string $name = null): string|int|false
    {
        return Statements::lastInsertId($name);
    }

    /**
     * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
     *
     * @param mixed $params Content to be quoted
     * @return mixed
     */
    public function quote(mixed ...$params): mixed
    {
        return Statements::quote(...$params);
    }

    /**
     * Reset query metadata
     *
     * @return void
     */
    private function setAllMetadata(): void
    {
        Statements::setAllMetadata();
    }

    /**
     * Returns an object containing the number of queried rows and the number of affected rows.
     *
     * @return object An associative object with keys 'queryRows' and 'affectedRows'.
     */
    public function getAllMetadata(): object
    {
        return Statements::getAllMetadata();
    }

    /**
     * Returns the query string.
     *
     * @return string The query string associated with this instance.
     */
    public function getQueryString(): string
    {
        return Statements::getQueryString();
    }

    /**
     * Sets the query string for the Connection instance.
     *
     * @param string $params The query string to set.
     */
    public function setQueryString(string $params): void
    {
        Statements::setQueryString($params);
    }

    /**
     * Returns the parameters associated with this instance.
     *
     * @return array|null The parameters associated with this instance.
     */
    public function getQueryParameters(): ?array
    {
        return Statements::getQueryParameters();
    }

    /**
     * Sets the query parameters for the Connection instance.
     *
     * @param array|null $params The query parameters to set.
     */
    public function setQueryParameters(?array $params): void
    {
        Statements::setQueryParameters($params);
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public function getQueryRows(): int|false
    {
        return Statements::getQueryRows();
    }

    /**
     * Sets the number of query rows for the Connection instance.
     *
     * @param callable|int|false $params The number of query rows to set.
     * @return void
     */
    public function setQueryRows(callable|int|false $params): void
    {
        Statements::setQueryRows($params);
    }

    /**
     * Returns the number of columns in a statement result.
     *
     * @return int|false The number of columns in the result or false in case of an error.
     */
    public function getQueryColumns(): int|false
    {
        return Statements::getQueryColumns();
    }

    /**
     * Sets the number of columns in the query result.
     *
     * @param int|false $params The number of columns or false if there are no columns.
     * @return void
     */
    public function setQueryColumns(int|false $params): void
    {
        Statements::setQueryColumns($params);
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public function getAffectedRows(): int|false
    {
        return Statements::getAffectedRows();
    }

    /**
     * Sets the number of affected rows for the Connection instance.
     *
     * @param int|false $params The number of affected rows to set.
     * @return void
     */
    public function setAffectedRows(int|false $params): void
    {
        Statements::setAffectedRows($params);
    }

    /**
     * Returns the statement for the function.
     *
     * @return mixed
     */
    public function getStatement(): mixed
    {
        return Statements::getStatement();
    }

    /**
     * Sets the statement for the function.
     *
     * @param mixed $statement The statement to be set.
     */
    public function setStatement(mixed $statement): void
    {
        Statements::setStatement($statement);
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    public function bindParam(mixed ...$params): void
    {
        Statements::bindParam(...$params);
    }

    /**
     * Parses an SQL statement and returns an statement.
     *
     * @param mixed ...$params The parameters for the query function.
     * @return string The statement resulting from the SQL statement.
     */
    public function parse(mixed ...$params): string
    {
        return Statements::parse(...$params);
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return PDOConnection|null
     */
    public function query(mixed ...$params): PDOConnection|null
    {
        return Statements::query(...$params);
    }
    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return PDOConnection|null
     */
    public function prepare(mixed ...$params): PDOConnection|null
    {
        return Statements::prepare(...$params);
    }
    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return bool
     */
    public function exec(mixed ...$params): bool
    {
        return Statements::exec(...$params);
    }

    /**
     * Fetches the next row from the statement and returns it as an array.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed $fetchArgument From the Fetch Into or Fetch Class.
     * @param mixed $optArgs From the Fetch Into or Fetch Class.
     * @return mixed The next row from the statement as an array, or false if there are no more rows.
     */
    public function fetch(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        return match ($fetchStyle) {
            PDO::FETCH_OBJ,
            PDO::FETCH_INTO,
            PDO::FETCH_CLASS => Fetchs::internalFetchClass($this->getStatement(), $fetchArgument, $optArgs),
            PDO::FETCH_COLUMN => Fetchs::internalFetchColumn($this->getStatement(), $fetchArgument),
            PDO::FETCH_ASSOC => Fetchs::internalFetchAssoc($this->getStatement()),
            PDO::FETCH_NUM => Fetchs::internalFetchNum($this->getStatement()),
            default => Fetchs::internalFetchBoth($this->getStatement()),
        };
    }

    /**
     * Fetches all rows from the statement and returns them as an array.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed $fetchArgument From the Fetch Into or Fetch Class.
     * @param mixed $optArgs From the Fetch Into or Fetch Class.
     * @return array|bool The next row from the statement as an array, or false if there are no more rows.
     */
    public function fetchAll(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        return match ($fetchStyle) {
            PDO::FETCH_OBJ,
            PDO::FETCH_INTO,
            PDO::FETCH_CLASS => Fetchs::internalFetchAllClass($this->getStatement(), $fetchArgument, $optArgs),
            PDO::FETCH_COLUMN => Fetchs::internalFetchAllColumn($this->getStatement(), $fetchArgument),
            PDO::FETCH_ASSOC => Fetchs::internalFetchAllAssoc($this->getStatement()),
            PDO::FETCH_NUM => Fetchs::internalFetchAllNum($this->getStatement()),
            default => Fetchs::internalFetchAllBoth($this->getStatement()),
        };
    }

    /**
     * This function retrieves an attribute from the database.
     *
     * @param mixed $name The attribute name
     * @return mixed
     */
    public function getAttribute(mixed $name): mixed
    {
        return $this->getConnection()->getAttribute($name);
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
        $this->getConnection()->setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return int|bool
     * @noinspection PhpUnused
     */
    public function errorCode(mixed $inst = null): int|bool
    {
        return $this->getConnection()->errorCode() || $inst;
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return string|bool
     * @noinspection PhpUnused
     */
    public function errorInfo(mixed $inst = null): string|bool
    {
        return $this->getConnection()->errorInfo() || $inst;
    }
}
