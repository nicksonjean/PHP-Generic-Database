<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use ReflectionException;
use SensitiveParameter;
use AllowDynamicProperties;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\MySQLi\Connection\MySQL;
use GenericDatabase\Engine\MySQLi\Connection\Arguments;
use GenericDatabase\Engine\MySQLi\Connection\Options;
use GenericDatabase\Engine\MySQLi\Connection\Attributes;
use GenericDatabase\Engine\MySQLi\Connection\DSN;
use GenericDatabase\Engine\MySQLi\Connection\Dump;
use GenericDatabase\Engine\MySQLi\Connection\Transaction;
use GenericDatabase\Engine\MySQLi\Connection\Statements;
use GenericDatabase\Helpers\CustomException;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Translate;
use GenericDatabase\Shared\Setter;
use GenericDatabase\Shared\Getter;
use GenericDatabase\Shared\Cleaner;
use GenericDatabase\Shared\Singleton;
use mysqli_result;
use mysqli_stmt;
use Exception;
use stdClass;

/**
 * Dynamic and Static container class for MySQLiConnection connections.
 *
 * @method static MySQLiConnection|void setDriver(mixed $value): void
 * @method static MySQLiConnection|string getDriver($value = null): string
 * @method static MySQLiConnection|void setHost(mixed $value): void
 * @method static MySQLiConnection|string getHost($value = null): string
 * @method static MySQLiConnection|void setPort(mixed $value): void
 * @method static MySQLiConnection|int getPort($value = null): int
 * @method static MySQLiConnection|void setUser(mixed $value): void
 * @method static MySQLiConnection|string getUser($value = null): string
 * @method static MySQLiConnection|void setPassword(mixed $value): void
 * @method static MySQLiConnection|string getPassword($value = null): string
 * @method static MySQLiConnection|void setDatabase(mixed $value): void
 * @method static MySQLiConnection|string getDatabase($value = null): string
 * @method static MySQLiConnection|void setOptions(mixed $value): void
 * @method static MySQLiConnection|array|null getOptions($value = null): array|null
 * @method static MySQLiConnection|static setConnected(mixed $value): void
 * @method static MySQLiConnection|mixed getConnected($value = null): mixed
 * @method static MySQLiConnection|void setDsn(mixed $value): void
 * @method static MySQLiConnection|mixed getDsn($value = null): mixed
 * @method static MySQLiConnection|void setAttributes(mixed $value): void
 * @method static MySQLiConnection|mixed getAttributes($value = null): mixed
 * @method static MySQLiConnection|void setCharset(mixed $value): void
 * @method static MySQLiConnection|string getCharset($value = null): string
 * @method static MySQLiConnection|void setException(mixed $value): void
 * @method static MySQLiConnection|mixed getException($value = null): mixed
 */
#[AllowDynamicProperties]
class MySQLiConnection implements IConnection
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
     * Instance of the Statement of the database
     * @var mixed $statement = null
     */
    private static mixed $statement = null;

    /**
     * Count rows in query statement
     * @var ?int $queryRows = 0
     */
    private ?int $queryRows = 0;

    /**
     * Count columns in query statement
     * @var ?int $queryColumns = 0
     */
    private ?int $queryColumns = 0;

    /**
     * Affected row in query statement
     * @var ?int $affectedRows = 0
     */
    private ?int $affectedRows = 0;

    /**
     * Lasts params query executed
     * @var ?array $queryParameters = []
     */
    private ?array $queryParameters = [];

    /**
     * Last string query executed
     * @var string $queryString = ''
     */
    private string $queryString = '';

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
     * @return MySQLiConnection|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): MySQLiConnection|string|int|bool|array|null
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
     * @return MySQLiConnection
     * @throws ReflectionException
     */
    public static function __callStatic(string $name, array $arguments): MySQLiConnection
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return MySQLiConnection
     * @throws ReflectionException
     */
    private function preConnect(): MySQLiConnection
    {
        $this->setConnection(mysqli_init());
        Options::setOptions((array) static::getOptions());
        $options = Options::getOptions();
        static::setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return MySQLiConnection
     * @throws CustomException
     */
    private function postConnect(): MySQLiConnection
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
     * @param mixed $port The port of the database
     * @return MySQLiConnection
     * @throws Exception
     */
    private function realConnect(
        mixed $host,
        mixed $user,
        #[SensitiveParameter] mixed $password,
        mixed $database,
        mixed $port
    ): MySQLiConnection {
        if (!static::getHost()) {
            $host = (string) !Options::getOptions(MySQL::ATTR_PERSISTENT)
                ? $host
                : 'p:' . $host;
            static::setHost($host);
        }
        $this->parseDsn();
        $this->getConnection()->real_connect($host, $user, $password, $database, $port);
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return MySQLiConnection
     * @throws Exception
     */
    public function connect(): MySQLiConnection
    {
        if (!extension_loaded('mysqli')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'mysqli',
                'PHP.ini'
            );
            throw new CustomException($message);
        }

        try {
            $this->setInstance($this);
            $this
                ->preConnect()
                ->getInstance()
                ->realConnect(
                    static::getHost(),
                    static::getUser(),
                    static::getPassword(),
                    static::getDatabase(),
                    static::getPort()
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
        return $this->getConnection()->ping();
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
            if (!Options::getOptions(MySQL::ATTR_PERSISTENT)) {
                if (Compare::connection($this->getConnection()) === 'mysqli') {
                    mysqli_close($this->getConnection());
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
        return (Compare::connection($this->getConnection()) === 'mysqli') && $this->getInstance()->getConnected();
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
        if (!$name) {
            return (int) $this->getConnection()->insert_id;
        }
        $filter = "WHERE TABLE_NAME = ? AND COLUMN_KEY = ? AND EXTRA = ?";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = $this->getConnection()->prepare($query);
        $stmt = $this->internalBindVariable([$name, 'PRI', 'auto_increment'], $stmt);
        $stmt->execute();
        $autoKeyResult = $stmt->get_result();
        $autoKey = $autoKeyResult->fetch_assoc();
        if (isset($autoKey['COLUMN_NAME'])) {
            $query = sprintf("SELECT MAX(%s) AS value FROM %s", $autoKey['COLUMN_NAME'], $name);
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $maxIndexResult = $stmt->get_result();
            $maxIndex = $maxIndexResult->fetch_assoc()['value'];
            if ($maxIndex !== null) {
                return (int) $maxIndex;
            }
        }
        return (int) $autoKey['COLUMN_NAME'] ?? 0;
    }

    /**
     * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
     *
     * @param mixed $params Content to be quoted
     * @return mixed
     */
    public function quote(mixed ...$params): mixed
    {
        $string = reset($params);
        $quote = $params[1] ?? false;
        if (is_array($string)) {
            return array_map(fn($str) => $this->quote($str, $quote), $string);
        } elseif ($string && preg_match("/^(?:\d+\.\d+|[1-9]\d*)$/S", (string) $string)) {
            return $string;
        }
        $quoted = fn($str) => $this->getConnection()->real_escape_string($str);
        return $quote ? "'" . $quoted($string) . "'" : $quoted($string);
    }

    /**
     * Reset query metadata
     *
     * @return void
     */
    private function setAllMetadata(): void
    {
        $this->queryString = '';
        $this->queryParameters = [];
        $this->queryRows = 0;
        $this->queryColumns = 0;
        $this->affectedRows = 0;
    }

    /**
     * Returns an object containing the number of queried rows and the number of affected rows.
     *
     * @return object An associative object with keys 'queryRows' and 'affectedRows'.
     */
    public function getAllMetadata(): object
    {
        $metadata = new stdClass();
        $metadata->queryString = $this->getQueryString();
        $metadata->queryParameters = $this->getQueryParameters();
        $metadata->queryRows = $this->getQueryRows();
        $metadata->queryColumns = $this->getQueryColumns();
        $metadata->affectedRows = $this->getAffectedRows();
        return $metadata;
    }

    /**
     * Returns the query string.
     *
     * @return string The query string associated with this instance.
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }

    /**
     * Sets the query string for the Connection instance.
     *
     * @param string $params The query string to set.
     */
    public function setQueryString(string $params): void
    {
        $this->queryString = $params;
    }

    /**
     * Returns the parameters associated with this instance.
     *
     * @return array|null The parameters associated with this instance.
     */
    public function getQueryParameters(): ?array
    {
        return $this->queryParameters;
    }

    /**
     * Sets the query parameters for the Connection instance.
     *
     * @param array|null $params The query parameters to set.
     */
    public function setQueryParameters(?array $params): void
    {
        $this->queryParameters = $params;
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public function getQueryRows(): int|false
    {
        return $this->queryRows;
    }

    /**
     * Sets the number of query rows for the Connection instance.
     *
     * @param callable|int|false $params The number of query rows to set.
     * @return void
     */
    public function setQueryRows(callable|int|false $params): void
    {
        $this->queryRows = $params;
    }

    /**
     * Returns the number of columns in a statement result.
     *
     * @return int|false The number of columns in the result or false in case of an error.
     */
    public function getQueryColumns(): int|false
    {
        return $this->queryColumns;
    }

    /**
     * Sets the number of columns in the query result.
     *
     * @param int|false $params The number of columns or false if there are no columns.
     * @return void
     */
    public function setQueryColumns(int|false $params): void
    {
        $this->queryColumns = $params;
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public function getAffectedRows(): int|false
    {
        return $this->affectedRows;
    }

    /**
     * Sets the number of affected rows for the Connection instance.
     *
     * @param int|false $params The number of affected rows to set.
     * @return void
     */
    public function setAffectedRows(int|false $params): void
    {
        $this->affectedRows = (int) $params;
    }

    /**
     * Returns the statement for the function.
     *
     * @return mixed
     */
    public function getStatement(): mixed
    {
        return self::$statement;
    }

    /**
     * Sets the statement for the function.
     *
     * @param mixed $statement The statement to be set.
     */
    public function setStatement(mixed $statement): void
    {
        self::$statement = $statement;
    }
    /**
     * Binds variables to a prepared statement with specified types.
     * This method binds variables to a prepared statement based on their types,
     * allowing for more precise parameter binding.
     *
     * @param array &$preparedParams An array containing the parameters to bind.
     * @param mixed $stmt The prepared statement to bind variables to.
     * @return mixed The prepared statement with bound variables.
     */
    private function internalBindVariable(mixed $params, mysqli_stmt $statement): mysqli_stmt
    {
        if (is_array($params)) {
            $types = '';
            $values = [];
            foreach ($params as $value) {
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } elseif (is_string($value)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
                $values[] = $value;
            }
            if (!empty($types)) {
                $statement->bind_param($types, ...array_values($values));
            }
        }
        return $statement;
    }

    /**
     * Binds an array multiple parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArrayMulti(mixed ...$params): void
    {
        $affectedRows = 0;
        foreach ($params['sqlArgs'] as $param) {
            $this->setStatement($this->internalBindVariable($param, $params['sqlStatement']));
            if ($this->exec($this->getStatement())) {
                if ($this->getQueryColumns() === 0) {
                    $affectedRows++;
                    $this->setAffectedRows((int) $affectedRows);
                }
            }
        }
    }

    /**
     * Binds an array single parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArraySingle(mixed ...$params): void
    {
        $this->internalBindParamArgs(...$params);
    }

    /**
     * Binds an array parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArray(mixed ...$params): void
    {
        if ($params['isMulti']) {
            $this->internalBindParamArrayMulti(...$params);
        } else {
            $this->internalBindParamArraySingle(...$params);
        }
    }
    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an args of parameters and values.
     * @return void
     */
    private function internalBindParamArgs(mixed ...$params): void
    {
        $this->setStatement($this->internalBindVariable($params['sqlArgs'], $params['sqlStatement']));
        if ($this->exec($this->getStatement())) {
            if ($this->getStatement()->field_count > 0) {
                $result = $this->getStatement()->get_result();
                if ($result) {
                    $this->setStatement($result);
                    $this->setQueryRows((int) $result->num_rows);
                }
            } else {
                $this->setAffectedRows($this->getStatement()->affected_rows);
            }
        }
    }
    /**
     * This function makes an arguments list
     *
     * @param mixed $params Arguments list
     * @param mixed $driver Driver name
     * @return array
     */
    private function makeArgs(mixed $driver, mixed ...$params): array
    {
        return Arrays::makeArgs($driver, ...$params);
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    public function bindParam(mixed ...$params): void
    {
        $this->setQueryParameters($params['sqlArgs']);
        if ($params['isArray']) {
            $this->internalBindParamArray(...$params);
        } else {
            $this->internalBindParamArgs(...$params);
        }
        $this->setQueryColumns((int) $this->getStatement()->field_count);
    }

    /**
     * Parses an SQL statement and returns an statement.
     *
     * @param mixed ...$params The parameters for the query function.
     * @return string The statement resulting from the SQL statement.
     */
    public function parse(mixed ...$params): string
    {
        $queryString = Translate::binding(Translate::escape(reset($params), Translate::SQL_DIALECT_BACKTICK));
        $this->setQueryString($queryString);
        return $this->getQueryString();
    }

    /**
     * This function binds the parameters to a prepared statement.
     *
     * @param mixed ...$params
     * @return mysqli_stmt|false
     */
    private function prepareStatement(mixed ...$params): mysqli_stmt|false
    {
        $this->setAllMetadata();
        if (!empty($params)) {
            $statement = $this->getConnection()->prepare($this->parse(...$params));
            if ($statement) {
                $this->setStatement($statement);
            }
        }
        return $statement;
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return static|null
     */
    public function query(mixed ...$params): static|null
    {
        $statement = $this->prepareStatement(...$params);
        if ($statement && $this->exec($statement)) {
            $this->setQueryColumns((int) $this->getStatement()->field_count);
            if ($this->getQueryColumns() > 0) {
                $result = $statement->get_result();
                if ($result) {
                    $results = $result->fetch_all(MYSQLI_ASSOC);
                    $this->setStatement(['results' => $results]);
                    $this->setQueryRows($result->num_rows);
                }
            } else {
                $this->setStatement(['results' => []]);
                $this->setAffectedRows((int) $statement->affected_rows);
            }
        }
        return $this;
    }
    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return static|null
     */
    public function prepare(mixed ...$params): static|null
    {
        $statement = $this->prepareStatement(...$params);
        $driver = static::getDriver();
        if (!empty($params)) {
            if ($statement) {
                array_unshift($params, $this->getStatement());
                $bindParams = $this->makeArgs($driver, ...$params);
            }
            $this->bindParam(...$bindParams);
        }
        return $this;
    }
    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return bool
     */
    public function exec(mixed ...$params): bool
    {
        $stmt = reset($params);
        return $stmt->execute();
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
        $fetch = is_null($fetchStyle) ? Options::getOptions(MySQL::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            MySQL::FETCH_OBJ,
            MySQL::FETCH_INTO,
            MySQL::FETCH_CLASS => Statements::internalFetchClass($this->getStatement(), $fetchArgument, $optArgs),
            MySQL::FETCH_COLUMN => Statements::internalFetchColumn($this->getStatement(), $fetchArgument),
            MySQL::FETCH_ASSOC => Statements::internalFetchAssoc($this->getStatement()),
            MySQL::FETCH_NUM => Statements::internalFetchNum($this->getStatement()),
            default => Statements::internalFetchBoth($this->getStatement()),
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
        $fetch = is_null($fetchStyle) ? Options::getOptions(MySQL::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            MySQL::FETCH_OBJ,
            MySQL::FETCH_INTO,
            MySQL::FETCH_CLASS => Statements::internalFetchAllClass($this->getStatement(), $fetchArgument, $optArgs),
            MySQL::FETCH_COLUMN => Statements::internalFetchAllColumn($this->getStatement(), $fetchArgument),
            MySQL::FETCH_ASSOC => Statements::internalFetchAllAssoc($this->getStatement()),
            MySQL::FETCH_NUM => Statements::internalFetchAllNum($this->getStatement()),
            default => Statements::internalFetchAllBoth($this->getStatement()),
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
        return MySQL::getAttribute($name);
    }

    /**
     * This function sets an attribute on the database.
     *
     * @param mixed $name The attribute name
     * @param mixed $value The attribute value
     * @return void
     * @throws ReflectionException
     */
    public function setAttribute(mixed $name, mixed $value): void
    {
        MySQL::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return int|string|bool
     * @noinspection PhpUnused
     */
    public function errorCode(mixed $inst = null): int|string|bool
    {
        return $this->getConnection()->errno || $inst;
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return string|bool|array
     * @noinspection PhpUnused
     */
    public function errorInfo(mixed $inst = null): string|bool|array
    {
        return $this->getConnection()->error || $inst;
    }
}
