<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use SensitiveParameter;
use AllowDynamicProperties;
use Exception;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\MySQLi\MySQL;
use GenericDatabase\Engine\MySQLi\Arguments;
use GenericDatabase\Engine\MySQLi\Options;
use GenericDatabase\Engine\MySQLi\Attributes;
use GenericDatabase\Engine\MySQLi\DSN;
use GenericDatabase\Engine\MySQLi\Dump;
use GenericDatabase\Engine\MySQLi\Transaction;
use GenericDatabase\Helpers\GenericException;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Helpers\Translater;
use GenericDatabase\Helpers\Regex;
use GenericDatabase\Traits\Setter;
use GenericDatabase\Traits\Getter;
use GenericDatabase\Traits\Cleaner;
use GenericDatabase\Traits\Singleton;

if (!defined('MYSQLI_FETCH_NUM')) {
    define('MYSQLI_FETCH_NUM', 8);
}
if (!defined('MYSQLI_FETCH_OBJ')) {
    define('MYSQLI_FETCH_OBJ', 9);
}
if (!defined('MYSQLI_FETCH_BOTH')) {
    define('MYSQLI_FETCH_BOTH', 10);
}
if (!defined('MYSQLI_FETCH_INTO')) {
    define('MYSQLI_FETCH_INTO', 11);
}
if (!defined('MYSQLI_FETCH_CLASS')) {
    define('MYSQLI_FETCH_CLASS', 12);
}
if (!defined('MYSQLI_FETCH_ASSOC')) {
    define('MYSQLI_FETCH_ASSOC', 13);
}
if (!defined('MYSQLI_FETCH_COLUMN')) {
    define('MYSQLI_FETCH_COLUMN', 14);
}

if (!defined('FETCH_NUM')) {
    define('FETCH_NUM', 8);
}
if (!defined('FETCH_OBJ')) {
    define('FETCH_OBJ', 9);
}
if (!defined('FETCH_BOTH')) {
    define('FETCH_BOTH', 10);
}
if (!defined('FETCH_INTO')) {
    define('FETCH_INTO', 11);
}
if (!defined('FETCH_CLASS')) {
    define('FETCH_CLASS', 12);
}
if (!defined('FETCH_ASSOC')) {
    define('FETCH_ASSOC', 13);
}
if (!defined('FETCH_COLUMN')) {
    define('FETCH_COLUMN', 14);
}

/**
 * Dynamic and Static container class for MySQLiEngine connections.
 *
 * @method static MySQLiEngine|static setDriver(mixed $value): void
 * @method static MySQLiEngine|static getDriver($value = null): mixed
 * @method static MySQLiEngine|static setHost(mixed $value): void
 * @method static MySQLiEngine|static getHost($value = null): mixed
 * @method static MySQLiEngine|static setPort(mixed $value): void
 * @method static MySQLiEngine|static getPort($value = null): mixed
 * @method static MySQLiEngine|static setUser(mixed $value): void
 * @method static MySQLiEngine|static getUser($value = null): mixed
 * @method static MySQLiEngine|static setPassword(mixed $value): void
 * @method static MySQLiEngine|static getPassword($value = null): mixed
 * @method static MySQLiEngine|static setDatabase(mixed $value): void
 * @method static MySQLiEngine|static getDatabase($value = null): mixed
 * @method static MySQLiEngine|static setOptions(mixed $value): void
 * @method static MySQLiEngine|static getOptions($value = null): mixed
 * @method static MySQLiEngine|static setConnected(mixed $value): void
 * @method static MySQLiEngine|static getConnected($value = null): mixed
 * @method static MySQLiEngine|static setDsn(mixed $value): void
 * @method static MySQLiEngine|static getDsn($value = null): mixed
 * @method static MySQLiEngine|static setAttributes(mixed $value): void
 * @method static MySQLiEngine|static getAttributes($value = null): mixed
 * @method static MySQLiEngine|static setCharset(mixed $value): void
 * @method static MySQLiEngine|static getCharset($value = null): mixed
 * @method static MySQLiEngine|static setException(mixed $value): void
 * @method static MySQLiEngine|static getException($value = null): mixed
 */
#[AllowDynamicProperties]
class MySQLiEngine implements IConnection
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
     * Last string query runned
     * @var string $queryString = ''
     */
    private string $queryString = '';

    /**
     * Lasts params query runned
     * @var array $queryParameters = []
     */
    private array $queryParameters = [];

    /**
     * Triggered when invoking inaccessible methods in an object context
     *
     * @param string $name Name of the method
     * @param array $arguments Array of arguments
     * @return MySQLiEngine|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): MySQLiEngine|string|int|bool|array|null
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
     * @return MySQLiEngine
     */
    public static function __callStatic(string $name, array $arguments): MySQLiEngine
    {
        return Arguments::call($name, $arguments);
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
        $options = Options::getOptions();
        $this->setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return MySQLiEngine
     * @throws GenericException
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
     * @param mixed $port The port of the database
     * @return MySQLiEngine
     * @throws Exception
     */
    private function realConnect(
        string $host,
        string $user,
        #[SensitiveParameter] string $password,
        string $database,
        mixed $port
    ): MySQLiEngine {
        if (!$this->getHost()) {
            $host = (string) !Options::getOptions(MySQL::ATTR_PERSISTENT)
                ? $host
                : 'p:' . $host;
            $this->setHost($host);
        }
        $this->parseDsn();
        $this->getConnection()->real_connect($host, $user, $password, $database, $port);
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return MySQLiEngine
     * @throws Exception
     */
    public function connect(): MySQLiEngine
    {
        try {
            $this
                ->preConnect()
                ->setInstance($this)
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
        return (Compare::connection($this->getConnection()) === 'mysqli') && $this->getConnected();
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
            return $this->getConnection()->insert_id;
        }
        $filter = "WHERE table_name = ? AND column_key = ? AND extra = ?";
        $query = sprintf("SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = $this->getConnection()->prepare($query);
        $stmt->bind_param("sss", $name, 'PRI', 'auto_increment');
        $stmt->execute();
        $autoKeyResult = $stmt->get_result();
        $autoKey = $autoKeyResult->fetch_assoc();
        if (isset($autoKey['column_name'])) {
            $query = sprintf("SELECT COALESCE(MAX(%s)) AS value FROM %s", $autoKey['column_name'], $name);
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $maxIndexResult = $stmt->get_result();
            $maxIndex = $maxIndexResult->fetch_assoc()['value'];

            if ($maxIndex !== null) {
                return $maxIndex;
            }
        }
        return $autoKey['column_name'] ?? 0;
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
        $quoted = fn ($str) => $this->getConnection()->real_escape_string($str);
        return ($quote) ? "'" . $quoted($string) . "'" : $quoted($string);
    }

    /**
     * Reset query metadata
     *
     * @return void
     */
    private function resetMetadata(): void
    {
        $this->queryString = '';
        $this->queryParameters = [];
        $this->queryRows = 0;
        $this->queryColumns = 0;
        $this->affectedRows = 0;
    }

    /**
     * Returns an array containing the number of queried rows and the number of affected rows.
     *
     * @return array An associative array with keys 'queryRows' and 'affectedRows'.
     */
    public function queryMetadata()
    {
        return [
            'queryString' => $this->queryString,
            'queryParameters' => $this->queryParameters ?? null,
            'queryRows' => $this->queryRows,
            'queryColumns' => $this->queryColumns,
            'affectedRows' => $this->affectedRows
        ];
    }

    /**
     * Returns the query string.
     *
     * @return string The query string associated with this instance.
     */
    public function queryString(): string
    {
        return $this->queryString;
    }

    /**
     * Returns the parameters associated with this instance.
     *
     * @return array|null The parameters associated with this instance.
     */
    public function queryParameters(): array|null
    {
        return $this->queryParameters;
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public function queryRows(): int|false
    {
        return $this->queryRows;
    }

    /**
     * Returns the number of columns in an statement result.
     *
     * @return int|false The number of columns in the result or false in case of an error.
     */
    public function queryColumns(): int|false
    {
        return $this->queryColumns;
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public function affectedRows(): int|false
    {
        return $this->affectedRows;
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
    private function internalBindVariable(array &$preparedParams, mixed $stmt): mixed
    {
        $types = '';
        $referenceParams = [];
        foreach ($preparedParams as &$arg) {
            $referenceParams[] = &$arg;
            $types .= match (true) {
                is_float($arg) => 'd',
                is_integer($arg) => 'i',
                is_string($arg) => 's',
                default => 'b'
            };
        }
        array_unshift($referenceParams, $types);
        call_user_func_array([$stmt, 'bind_param'], $referenceParams);
        return $stmt;
    }

    /**
     * Binds a array multiple parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArrayMulti(mixed ...$params): void
    {
        foreach ($params['sqlArgs'] as $param) {
            $statement = $this->internalBindVariable($param, $params['sqlStatement']);
            $this->exec($statement);
            $this->affectedRows += Regex::isSelect($params['sqlQuery']) ? 0 : $this->getConnection()->affected_rows;
        }
    }

    /**
     * Binds a array single parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArraySingle(mixed ...$params): void
    {
        $this->internalBindParamArgs(...$params);
    }

    /**
     * Binds a array parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArray(mixed ...$params): void
    {
        $this->queryParameters = $params['sqlArgs'];
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
        $statement = $this->internalBindVariable($params['sqlArgs'], $params['sqlStatement']);
        $this->exec($statement);
        $this->affectedRows += Regex::isSelect($params['sqlQuery']) ? 0 : $this->getConnection()->affected_rows;
    }

    /**
     * This function makes an arguments list
     *
     * @param mixed $params Arguments list
     * @return array
     */
    private function makeArgs(mixed ...$params): array
    {
        if (array_key_exists(2, $params)) {
            if (is_array($params[2])) {
                $isArgs = false;
                $isArray = true;
                $isMulti = Arrays::isMultidimensional($params[2]) ? true : false;
                $sqlArgs = $params[2];
            } else {
                $isArgs = true;
                $isArray = false;
                $isMulti = false;
                $sqlArgs = Translater::parameters($params[1], array_slice($params, 2));
            }
        }
        return [
            'sqlStatement' => $params[0],
            'sqlQuery' => $params[1],
            'sqlArgs' => $sqlArgs ?? [],
            'isArray' => $isArray ?? false,
            'isMulti' => $isMulti ?? false,
            'isArgs' => $isArgs ?? false
        ];
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    public function bindParam(mixed ...$params): void
    {
        $this->queryParameters = $params['sqlArgs'];
        if ($params['isArray']) {
            $this->internalBindParamArray(...$params);
        } else {
            $this->internalBindParamArgs(...$params);
        }
    }

    /**
     * Parses an SQL statement and returns an statement.
     *
     * @param mixed ...$params The parameters for the query function.
     * @return mixed The statement resulting from the SQL statement.
     */
    private function parse(mixed ...$params): mixed
    {
        $this->queryString = Translater::binding(Translater::escape($params[0], Translater::SQL_DIALECT_BTICK));
        return $this->queryString;
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return static|null
     */
    public function query(mixed ...$params): static|null
    {
        $this->resetMetadata();
        if (!empty($params)) {
            self::$statement = mysqli_query(
                $this->getConnection(),
                $this->parse(...$params),
                array_key_exists(1, $params) ? (int) $params[1] : MYSQLI_STORE_RESULT
            );
            $this->queryRows = Regex::isSelect($this->queryString) && get_class(self::$statement) === 'mysqli_result'
                ? self::$statement->num_rows
                : 0;
            $this->queryColumns = Regex::isSelect($this->queryString) && get_class(self::$statement) === 'mysqli_result'
                ? self::$statement->field_count :
                0;
            $this->affectedRows += $this->queryRows === $this->getConnection()->affected_rows
                ? 0
                : $this->getConnection()->affected_rows;
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
        $this->resetMetadata();
        if (!empty($params)) {
            $statement = mysqli_prepare($this->getConnection(), $this->parse(...$params));
            array_unshift($params, $statement);
            if (array_key_exists(2, $params)) {
                $bindParams = array_merge($this->makeArgs(...$params), ['rowCount' => false]);
                $this->bindParam(...$bindParams);
            } else {
                $this->exec($statement);
            }
            $this->queryRows = self::$statement && get_class(self::$statement) === 'mysqli_result'
                ? self::$statement->num_rows
                : $statement->num_rows;
            $this->queryColumns = self::$statement && get_class(self::$statement) === 'mysqli_result'
                ? self::$statement->field_count
                : $statement->field_count;
        }
        return $this;
    }

    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return mixed
     */
    public function exec(mixed ...$params): mixed
    {
        $statement = $params[0];
        self::$statement = $statement->execute() ? $statement->get_result() : false;
        return self::$statement;
    }

    /**
     * Fetches the next row from the statement and returns it as an array.
     *
     * @param int $fetchStyle The fetch style (optional). Default is *_FETCH_BOTH.
     * @param mixed $fetchArgument From the Fetch Into or Fetch Class.
     * @param mixed $optArgs From the Fetch Into or Fetch Class.
     * @return mixed The next row from the statement as an array, or false if there are no more rows.
     */
    public function fetch(
        int $fetchStyle = FETCH_BOTH,
        mixed $fetchArgument = null,
        mixed $optArgs = null
    ): mixed {
        return match ($fetchStyle) {
            9, 11, 12 => $this->internalFetchClassOrObject(self::$statement, $fetchArgument, $optArgs),
            14 => $this->internalFetchColumn(self::$statement, $fetchArgument),
            13 => $this->internalFetchAssoc(self::$statement),
            8 => $this->internalFetchNum(self::$statement),
            10 => $this->internalFetchBoth(self::$statement),
            default => $this->internalFetchBoth(self::$statement),
        };
    }

    /**
     * Fetches all rows from the statement and returns them as an array.
     *
     * @param int $fetchStyle The fetch style (optional). Default is *_FETCH_ASSOC.
     * @param mixed $fetchArgument From the Fetch Into or Fetch Class.
     * @param mixed $optArgs From the Fetch Into or Fetch Class.
     * @return mixed An array containing all rows from the statement.
     */
    public function fetchAll(
        int $fetchStyle = FETCH_ASSOC,
        mixed $fetchArgument = null,
        mixed $optArgs = null
    ): mixed {
        return match ($fetchStyle) {
            9, 12 => $this->internalFetchAllClassOrObjects(self::$statement, $fetchArgument, $optArgs),
            14 => $this->internalFetchAllColumn(self::$statement, $fetchArgument),
            13 => $this->internalFetchAllAssoc(self::$statement),
            8 => $this->internalFetchAllNum(self::$statement),
            10 => $this->internalFetchAllBoth(self::$statement),
            default => $this->internalFetchAllBoth(self::$statement),
        };
    }

    protected function internalFetchClassOrObject(
        $statement = null,
        $constructorArguments = [],
        $aClassOrObject = '\stdClass',
    ) {
        $rowData = $this->internalFetchAssoc($statement);
        $fetchArgument = $constructorArguments === null ? [] : $constructorArguments;
        if (is_array($rowData)) {
            return Reflections::createObjectAndSetPropertiesCaseInsenstive($aClassOrObject, $fetchArgument, $rowData);
        }
        return $rowData;
    }

    protected function internalFetchBoth($statement = null)
    {
        $tmpData = mysqli_fetch_assoc($statement);
        if (is_array($tmpData)) {
            return Arrays::toBoth($tmpData);
        }
        return false;
    }

    protected function internalFetchAssoc($statement = null)
    {
        return mysqli_fetch_assoc($statement);
    }

    protected function internalFetchNum($statement = null)
    {
        return mysqli_fetch_row($statement);
    }

    protected function internalFetchColumn($statement = null, $columnIndex = 0)
    {
        $rowData = $this->internalFetchNum($statement);
        $fetchArgument = $columnIndex === null ? 0 : $columnIndex;
        if (is_array($rowData)) {
            return isset($rowData[$fetchArgument]) ? $rowData[$fetchArgument] : null;
        }
        return false;
    }

    protected function internalFetchAllAssoc($statement = null)
    {
        $result = [];
        while ($data = $this->internalFetchAssoc($statement)) {
            $result[] = $data;
        }
        return $result;
    }

    protected function internalFetchAllNum($statement = null)
    {
        $result = [];
        while ($data = $this->internalFetchNum($statement)) {
            $result[] = $data;
        }
        return $result;
    }

    protected function internalFetchAllBoth($statement = null)
    {
        $result = [];
        while ($data = $this->internalFetchBoth($statement)) {
            $result[] = $data;
        }
        return $result;
    }

    protected function internalFetchAllColumn($statement = null, $columnIndex = 0)
    {
        $result = [];
        $fetchArgument = $columnIndex === null ? 0 : $columnIndex;
        while ($data = $this->internalFetchColumn($statement, $fetchArgument)) {
            $result[] = $data;
        }
        return $result;
    }

    protected function internalFetchAllClassOrObjects(
        $statement = null,
        $constructorArguments = [],
        $aClassOrObject = '\sstdClass',
    ) {
        $result = [];
        $fetchArgument = $constructorArguments === null ? [] : $constructorArguments;
        while ($row = $this->internalFetchClassOrObject($statement, $fetchArgument, $aClassOrObject)) {
            if ($row !== false) {
                $result[] = $row;
            }
        }
        return $result;
    }

    /**
     * This function retrieves an attribute from the database.
     *
     * @param mixed $name The attribute name
     * @return mixed
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
     */
    public function setAttribute(mixed $name, mixed $value): void
    {
        MySQL::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorCode(mixed $inst = null): mixed
    {
        return $this->getConnection()->errno;
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorInfo(mixed $inst = null): mixed
    {
        return $this->getConnection()->error;
    }
}
