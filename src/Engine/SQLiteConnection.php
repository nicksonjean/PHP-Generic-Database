<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use ReflectionException;
use SensitiveParameter;
use AllowDynamicProperties;
use Exception;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\SQLite\Connection\SQLite;
use GenericDatabase\Engine\SQLite\Connection\Arguments;
use GenericDatabase\Engine\SQLite\Connection\Options;
use GenericDatabase\Engine\SQLite\Connection\Attributes;
use GenericDatabase\Engine\SQLite\Connection\DSN;
use GenericDatabase\Engine\SQLite\Connection\Dump;
use GenericDatabase\Engine\SQLite\Connection\Transaction;
use GenericDatabase\Engine\SQLite\Connection\Statements;
use GenericDatabase\Engine\SQLite\Connection\Fetchs;
use GenericDatabase\Helpers\CustomException;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Shared\Setter;
use GenericDatabase\Shared\Getter;
use GenericDatabase\Shared\Cleaner;
use GenericDatabase\Shared\Singleton;
use SQLite3;

/**
 * Dynamic and Static container class for SQLiteConnection connections.
 *
 * @method static SQLiteConnection|void setDriver(mixed $value): void
 * @method static SQLiteConnection|string getDriver($value = null): string
 * @method static SQLiteConnection|void setHost(mixed $value): void
 * @method static SQLiteConnection|string getHost($value = null): string
 * @method static SQLiteConnection|void setPort(mixed $value): void
 * @method static SQLiteConnection|int getPort($value = null): int
 * @method static SQLiteConnection|void setUser(mixed $value): void
 * @method static SQLiteConnection|string getUser($value = null): string
 * @method static SQLiteConnection|void setPassword(mixed $value): void
 * @method static SQLiteConnection|string getPassword($value = null): string
 * @method static SQLiteConnection|void setDatabase(mixed $value): void
 * @method static SQLiteConnection|string getDatabase($value = null): string
 * @method static SQLiteConnection|void setOptions(mixed $value): void
 * @method static SQLiteConnection|array|null getOptions($value = null): array|null
 * @method static SQLiteConnection|static setConnected(mixed $value): void
 * @method static SQLiteConnection|mixed getConnected($value = null): mixed
 * @method static SQLiteConnection|void setDsn(mixed $value): void
 * @method static SQLiteConnection|mixed getDsn($value = null): mixed
 * @method static SQLiteConnection|void setAttributes(mixed $value): void
 * @method static SQLiteConnection|mixed getAttributes($value = null): mixed
 * @method static SQLiteConnection|void setCharset(mixed $value): void
 * @method static SQLiteConnection|string getCharset($value = null): string
 * @method static SQLiteConnection|void setException(mixed $value): void
 * @method static SQLiteConnection|mixed getException($value = null): mixed
 */
#[AllowDynamicProperties]
class SQLiteConnection implements IConnection
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
    public function __construct()
    {

    }

    /**
     * Triggered when invoking inaccessible methods in an object context
     *
     * @param string $name Name of the method
     * @param array $arguments Array of arguments
     * @return SQLiteConnection|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): SQLiteConnection|string|int|bool|array|null
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
     * @return SQLiteConnection
     * @throws ReflectionException
     */
    public static function __callStatic(string $name, array $arguments): SQLiteConnection
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return SQLiteConnection
     * @throws ReflectionException
     */
    private function preConnect(): SQLiteConnection
    {
        Options::setOptions((array) static::getOptions());
        $options = Options::getOptions();
        static::setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return SQLiteConnection
     * @throws CustomException
     */
    private function postConnect(): SQLiteConnection
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the SQLiteConnection connection.
     *
     * @param mixed $database The path of the database file
     * @param int|null $flags = null Flags of the database behavior
     * @return SQLiteConnection
     * @throws Exception
     */
    private function realConnect(mixed $database, int $flags = null): SQLiteConnection
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
     * @return SQLiteConnection
     * @throws Exception
     */
    public function connect(): SQLiteConnection
    {
        if (!extension_loaded('sqlite3')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'sqlite3',
                'PHP.ini'
            );
            throw new CustomException($message);
        }

        try {
            $this->setInstance($this);
            $this
                ->preConnect()
                ->getInstance()
                ->setDsn($this->parseDsn())
                ->realConnect(
                    static::getDatabase(),
                    Options::flags()
                )
                ->postConnect()
                ->setConnected(true);
            return $this;
        } catch (Exception $error) {
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
            static::setConnected(false);
            if (!Options::getOptions(SQLite::ATTR_PERSISTENT)) {
                if (
                    Compare::connection($this->getConnection()) === 'sqlite'
                    || Compare::connection($this->getConnection()) === 'sqlite3'
                ) {
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
        return (Compare::connection($this->getConnection()) === 'sqlite'
            || Compare::connection($this->getConnection()) === 'sqlite3') && $this->getInstance()->getConnected();
    }

    /**
     * This method is responsible for parsing the DSN from DSN class.
     *
     * @return string|CustomException
     * @throws CustomException
     */
    private function parseDsn(): string|CustomException
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
        return Statements::lastInsertId($name);
    }

    /**
     * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
     *
     * @param mixed $params Content to be quoted
     * @return string|int
     */
    public function quote(mixed ...$params): string|int
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
     * @param mixed $params The name of the parameter or an array and args of parameters and values.
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
    private function parse(mixed ...$params): string
    {
        return Statements::parse(...$params);
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return static|null
     */
    public function query(mixed ...$params): static|null
    {
        return Statements::query(...$params);
    }

    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return static|null
     */
    public function prepare(mixed ...$params): static|null
    {
        return Statements::prepare(...$params);
    }

    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return mixed
     */
    public function exec(mixed ...$params): mixed
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
     * @throws ReflectionException
     */
    public function fetch(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $fetch = is_null($fetchStyle) ? Options::getOptions(SQLite::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            SQLite::FETCH_OBJ,
            SQLite::FETCH_INTO,
            SQLite::FETCH_CLASS => Fetchs::internalFetchClass($this->getStatement(), $fetchArgument, $optArgs),
            SQLite::FETCH_COLUMN => Fetchs::internalFetchColumn($this->getStatement(), $fetchArgument),
            SQLite::FETCH_ASSOC => Fetchs::internalFetchAssoc($this->getStatement()),
            SQLite::FETCH_NUM => Fetchs::internalFetchNum($this->getStatement()),
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
     * @throws ReflectionException
     */
    public function fetchAll(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        $fetch = is_null($fetchStyle) ? Options::getOptions(SQLite::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            SQLite::FETCH_OBJ,
            SQLite::FETCH_INTO,
            SQLite::FETCH_CLASS => Fetchs::internalFetchAllClass($this->getStatement(), $fetchArgument, $optArgs),
            SQLite::FETCH_COLUMN => Fetchs::internalFetchAllColumn($this->getStatement(), $fetchArgument),
            SQLite::FETCH_ASSOC => Fetchs::internalFetchAllAssoc($this->getStatement()),
            SQLite::FETCH_NUM => Fetchs::internalFetchAllNum($this->getStatement()),
            default => Fetchs::internalFetchAllBoth($this->getStatement()),
        };
    }

    /**
     * This function retrieves an attribute from the database.
     *
     * @param mixed $name The attribute name
     * @return mixed
     * @throws ReflectionException
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
     * @throws ReflectionException
     */
    public function setAttribute(mixed $name, mixed $value): void
    {
        SQLite::setAttribute($name, $value);
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
        return $this->getConnection()->lastErrorCode() || $inst;
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
        return $this->getConnection()->lastErrorMsg() || $inst;
    }
}
