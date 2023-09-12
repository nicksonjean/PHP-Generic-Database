<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use PDO;
use SensitiveParameter;
use AllowDynamicProperties;
use Exception;
use PDOException;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\PDO\Arguments;
use GenericDatabase\Engine\PDO\Options;
use GenericDatabase\Engine\PDO\Attributes;
use GenericDatabase\Engine\PDO\DSN;
use GenericDatabase\Engine\PDO\Dump;
use GenericDatabase\Engine\PDO\Transaction;
use GenericDatabase\Engine\PDO\Statements;
use GenericDatabase\Helpers\CustomException;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Translater;
use GenericDatabase\Helpers\Validations;
use GenericDatabase\Traits\Setter;
use GenericDatabase\Traits\Getter;
use GenericDatabase\Traits\Cleaner;
use GenericDatabase\Traits\Singleton;

if (!defined('PDO_FETCH_NUM')) {
    define('PDO_FETCH_NUM', 8);
}
if (!defined('PDO_FETCH_OBJ')) {
    define('PDO_FETCH_OBJ', 9);
}
if (!defined('PDO_FETCH_BOTH')) {
    define('PDO_FETCH_BOTH', 10);
}
if (!defined('PDO_FETCH_INTO')) {
    define('PDO_FETCH_INTO', 11);
}
if (!defined('PDO_FETCH_CLASS')) {
    define('PDO_FETCH_CLASS', 12);
}
if (!defined('PDO_FETCH_ASSOC')) {
    define('PDO_FETCH_ASSOC', 13);
}
if (!defined('PDO_FETCH_COLUMN')) {
    define('PDO_FETCH_COLUMN', 14);
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
 * Dynamic and Static container class for PDOEngine connections.
 *
 * @method static PDOEngine|static setDriver(mixed $value): void
 * @method static PDOEngine|static getDriver($value = null): mixed
 * @method static PDOEngine|static setHost(mixed $value): void
 * @method static PDOEngine|static getHost($value = null): mixed
 * @method static PDOEngine|static setPort(mixed $value): void
 * @method static PDOEngine|static getPort($value = null): mixed
 * @method static PDOEngine|static setUser(mixed $value): void
 * @method static PDOEngine|static getUser($value = null): mixed
 * @method static PDOEngine|static setPassword(mixed $value): void
 * @method static PDOEngine|static getPassword($value = null): mixed
 * @method static PDOEngine|static setDatabase(mixed $value): void
 * @method static PDOEngine|static getDatabase($value = null): mixed
 * @method static PDOEngine|static setOptions(mixed $value): void
 * @method static PDOEngine|static getOptions($value = null): mixed
 * @method static PDOEngine|static setConnected(mixed $value): void
 * @method static PDOEngine|static getConnected($value = null): mixed
 * @method static PDOEngine|static setDsn(mixed $value): void
 * @method static PDOEngine|static getDsn($value = null): mixed
 * @method static PDOEngine|static setAttributes(mixed $value): void
 * @method static PDOEngine|static getAttributes($value = null): mixed
 * @method static PDOEngine|static setCharset(mixed $value): void
 * @method static PDOEngine|static getCharset($value = null): mixed
 * @method static PDOEngine|static setException(mixed $value): void
 * @method static PDOEngine|static getException($value = null): mixed
 */
#[AllowDynamicProperties]
class PDOEngine implements IConnection
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
     * Instance of the Statement of the database
     * @var mixed $statementCount = null
     */
    private static mixed $statementCount = null;

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
     * @return PDOEngine|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): PDOEngine|string|int|bool|array|null
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
     * @return PDOEngine
     */
    public static function __callStatic(string $name, array $arguments): PDOEngine
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return PDOEngine
     * @throws CustomException
     */
    private function preConnect(): PDOEngine
    {
        Options::setOptions((array) $this->getOptions());
        $options = Options::getOptions();
        $this->setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return PDOEngine
     * @throws CustomException
     */
    private function postConnect(): PDOEngine
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
     * @return PDOEngine
     * @throws Exception
     */
    private function realConnect(
        string $dsn,
        ?string $user = null,
        #[SensitiveParameter] ?string $password = null,
        ?array $options = null
    ): PDOEngine {
        $this->setConnection(new PDO($dsn, $user, $password, $options));
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return PDOEngine
     * @throws PDOException|Exception
     */
    public function connect(): PDOEngine
    {
        try {
            $this
                ->setInstance($this)
                ->preConnect()
                ->realConnect(
                    (string) $this->parseDsn(),
                    (string) $this->getUser(),
                    (string) $this->getPassword(),
                    (array) $this->getOptions()
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
        if ($this->getDriver() == 'oci') {
            $query .= ' FROM DUAL';
        } elseif ($this->getDriver() == 'ibase' || $this->getDriver() == 'firebird') {
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
            $this->setConnected(false);
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
        return ($this->getConnection() !== null) && $this->getConnected();
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
     *  either the last one inserted during the current transaction, or by passing in the optional name parameter.
     *
     * @param ?string $name = null Resource name, table or view
     * @return string|int|false
     */
    public function lastInsertId(?string $name = null): string|int|false
    {
        return $this->getConnection()->lastInsertId($name);
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
        $type = (empty($params) || !isset($params[1])) ? PDO::PARAM_STR : $params[1];
        return $this->getConnection()->quote($string, $type);
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
    public function queryMetadata(): array
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
        if (Validations::isSelect($this->queryString)) {
            $this->bindParam(...self::$statementCount);
            return count(Statements::internalFetchAllAssoc(self::$statementCount['sqlStatement']));
        }
        return 0;
    }

    /**
     * Returns the number of columns in a statement result.
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
        $index = 0;
        foreach ($preparedParams as &$arg) {
            if (is_bool($arg)) {
                $types = PDO::PARAM_BOOL;
            } elseif (is_integer($arg)) {
                $types = PDO::PARAM_INT;
            } elseif (is_float($arg)) {
                $types = PDO::PARAM_STR;
            } elseif (is_string($arg)) {
                $types = PDO::PARAM_STR;
            } elseif (is_null($arg)) {
                $types = PDO::PARAM_NULL;
            } else {
                $types = PDO::PARAM_LOB;
            }
            call_user_func_array([$stmt, 'bindParam'], [array_keys($preparedParams)[$index], &$arg, $types]);
            $index++;
        }
        return $stmt;
    }

    /**
     * Binds an array multiple parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArrayMulti(mixed ...$params): void
    {
        foreach ($params['sqlArgs'] as $param) {
            $statement = $this->internalBindVariable($param, $params['sqlStatement']);
            $this->exec($statement);
            $this->affectedRows += !Validations::isSelect($params['sqlQuery']) ? self::$statement->rowCount() : 0;
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
        $this->affectedRows += !Validations::isSelect($params['sqlQuery']) ? self::$statement->rowCount() : 0;
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
                $isMulti = Arrays::isMultidimensional($params[2]);
                $sqlArgs = $params[2];
            } else {
                $isArgs = true;
                $isArray = false;
                $isMulti = false;
                $sqlArgs = Translater::arguments($params[1], array_slice($params, 2));
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
     * @return string The statement resulting from the SQL statement.
     */
    private function parse(mixed ...$params): string
    {
        $driver = $this->getDriver();
        $dialectQuote = match ($driver) {
            'mysql' => Translater::SQL_DIALECT_BTICK,
            'pgsql', 'sqlsrv', 'oci', 'firebird' => Translater::SQL_DIALECT_DQUOTE,
            default => Translater::SQL_DIALECT_NONE,
        };
        $this->queryString = Translater::escape($params[0], $dialectQuote);
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
            $fetchMode = (array_key_exists(1, $params)) ? $params[1] : PDO::FETCH_DEFAULT;
            self::$statement = $this->getConnection()->query($this->parse(...$params), $fetchMode);
            $rowCount = $params;
            array_unshift($rowCount, $this->getConnection()->prepare($this->parse(...$params)));
            array_unshift($params, self::$statement);
            self::$statementCount = array_merge($this->makeArgs(...$rowCount), ['rowCount' => true]);
            $this->queryRows = Validations::isSelect($this->queryString) ? $this->queryRows() : 0;
            $this->queryColumns = self::$statement->columnCount();
            $this->affectedRows += !Validations::isSelect($this->queryString) ? self::$statement->rowCount() : 0;
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
            self::$statement = $this->getConnection()->prepare($this->parse(...$params));
            $rowCount = $params;
            array_unshift($rowCount, $this->getConnection()->prepare($this->parse(...$params)));
            array_unshift($params, self::$statement);
            $bindParams = array_merge($this->makeArgs(...$params), ['rowCount' => false]);
            self::$statementCount = array_merge($this->makeArgs(...$rowCount), ['rowCount' => true]);
            $this->bindParam(...$bindParams);
            $this->queryRows = Validations::isSelect($this->queryString) ? $this->queryRows() : 0;
            $this->queryColumns = self::$statement->columnCount();
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
        $stmt = $params[0];
        return $stmt->execute();
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
            9, 11, 12 => Statements::internalFetchClassOrObject(self::$statement, $fetchArgument, $optArgs),
            14 => Statements::internalFetchColumn(self::$statement, $fetchArgument),
            13 => Statements::internalFetchAssoc(self::$statement),
            8 => Statements::internalFetchNum(self::$statement),
            default => Statements::internalFetchBoth(self::$statement),
        };
    }

    /**
     * Fetches all rows from the statement and returns them as an array.
     *
     * @param int $fetchStyle The fetch style (optional). Default is *_FETCH_ASSOC.
     * @param mixed $fetchArgument From the Fetch Into or Fetch Class.
     * @param mixed $optArgs From the Fetch Into or Fetch Class.
     * @return array An array containing all rows from the statement.
     */
    public function fetchAll(
        int $fetchStyle = FETCH_ASSOC,
        mixed $fetchArgument = null,
        mixed $optArgs = null
    ): array {
        return match ($fetchStyle) {
            9, 12 => Statements::internalFetchAllClassOrObjects(self::$statement, $fetchArgument, $optArgs),
            14 => Statements::internalFetchAllColumn(self::$statement, $fetchArgument),
            13 => Statements::internalFetchAllAssoc(self::$statement),
            8 => Statements::internalFetchAllNum(self::$statement),
            default => Statements::internalFetchAllBoth(self::$statement),
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
     * @return mixed
     */
    public function errorCode(mixed $inst = null): mixed
    {
        return $this->getConnection()->errorCode();
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorInfo(mixed $inst = null): mixed
    {
        return $this->getConnection()->errorInfo();
    }
}
