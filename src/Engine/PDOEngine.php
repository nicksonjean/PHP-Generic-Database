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
use GenericDatabase\Helpers\GenericException;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Helpers\Translater;
use GenericDatabase\Helpers\Regex;
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
    private mixed $connection;

    /**
     * Instance of the Statement of the database
     * @var mixed $statement = null
     */
    private mixed $statement = null;

    /**
     * Affected rows in query post statement
     * @var ?int $queriedRows = 0
     */
    private ?int $queriedRows = 0;

    /**
     * @var ?int $affectedRows = 0
     */
    private ?int $affectedRows = 0;

    /**
     * Last string query runned
     * @var string $query = ''
     */
    private string $query = '';

    /**
     * Lasts params query runned
     * @var array $params = []
     */
    private array $params = [];


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
     * @throws GenericException
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
     * @throws GenericException
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
     * Returns an array containing the number of queried rows and the number of affected rows.
     *
     * @return array An associative array with keys 'queriedRows' and 'affectedRows'.
     */
    public function getRows()
    {
        return [
            'queriedRows' => $this->queriedRows,
            'affectedRows' => $this->affectedRows
        ];
    }

    /**
     * Get the parameters associated with this instance.
     *
     * @return mixed The parameters associated with this instance.
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int|false The number of affected rows
     */
    public function queriedRows(): int|false
    {
        if (Regex::isSelect($this->query)) {
            $this->bindParam(...$GLOBALS['rowCount']);
            return count($this->internalFetchAllAssoc($GLOBALS['rowCount']['sqlStatement']));
        }
        return 0;
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int The number of affected rows
     */
    public function affectedRows(): int|false
    {
        return $this->affectedRows;
    }

    /**
     * Returns the number of columns in an statement result.
     *
     * @return int|false The number of columns in the result or false in case of an error.
     */
    public function columnCount(): int|false
    {
        return $this->statement->columnCount();
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
        $types = 0;
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
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArray(mixed ...$params): void
    {
        $this->params = $params['sqlArgs'];
        if ($params['isMulti']) {
            foreach ($params['sqlArgs'] as $param) {
                $statement = $this->internalBindVariable($param, $params['sqlStatement']);
                $this->exec($statement);
                $this->affectedRows += !Regex::isSelect($this->query) ? $this->statement->rowCount() : 0;
            }
        } else {
            $statement = $this->internalBindVariable($params['sqlArgs'], $params['sqlStatement']);
            $this->exec($statement);
            $this->affectedRows += !Regex::isSelect($this->query) ? $this->statement->rowCount() : 0;
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
        $this->params = $params['sqlArgs'];
        $statement = $this->internalBindVariable($this->params, $params['sqlStatement']);
        $this->exec($statement);
        $this->affectedRows += !Regex::isSelect($this->query) ? $this->statement->rowCount() : 0;
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
        $driver = $this->getDriver();
        $dialectQuote = match ($driver) {
            'mysql' => Translater::SQL_DIALECT_BTICK,
            'pgsql', 'sqlsrv', 'oci', 'firebird' => Translater::SQL_DIALECT_DQUOTE,
            'sqlite' => Translater::SQL_DIALECT_NONE,
            default => Translater::SQL_DIALECT_NONE,
        };
        $this->query = Translater::escape($params[0], $dialectQuote);
        return $this->query;
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return static|null
     */
    public function query(mixed ...$params): static|null
    {
        $this->affectedRows = 0;
        $this->queriedRows = 0;
        if (!empty($params)) {
            $fetchMode = (empty($params) || !isset($params[1])) ? PDO::FETCH_DEFAULT : $params[1];
            $this->statement = $this->getConnection()->query($this->parse(...$params), $fetchMode);
            $rowCount = $params;
            array_unshift($rowCount, $this->getConnection()->prepare($this->parse(...$params)));
            array_unshift($params, $this->statement);
            $GLOBALS['rowCount'] = array_merge($this->makeArgs(...$rowCount), ['rowCount' => true]);
            $this->queriedRows = Regex::isSelect($this->query) ? $this->queriedRows() : 0;
            $this->affectedRows += !Regex::isSelect($this->query) ? $this->statement->rowCount() : 0;
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
        $this->affectedRows = 0;
        $this->queriedRows = 0;
        if (!empty($params)) {
            $this->statement = $this->getConnection()->prepare($this->parse(...$params));
            $rowCount = $params;
            array_unshift($rowCount, $this->getConnection()->prepare($this->parse(...$params)));
            array_unshift($params, $this->statement);
            $bindParams = array_merge($this->makeArgs(...$params), ['rowCount' => false]);
            $GLOBALS['rowCount'] = array_merge($this->makeArgs(...$rowCount), ['rowCount' => true]);
            $this->bindParam(...$bindParams);
            $this->queriedRows = Regex::isSelect($this->query) ? $this->queriedRows() : 0;
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
        int $fetchStyle = PDO_FETCH_BOTH,
        mixed $fetchArgument = null,
        mixed $optArgs = null
    ): mixed {
        switch ($fetchStyle) {
            case PDO_FETCH_OBJ:
            case PDO_FETCH_CLASS:
            case FETCH_OBJ:
            case FETCH_CLASS:
                return $this->internalFetchClassOrObject(
                    isset($optArgs) ? $optArgs : '\stdClass',
                    [],
                    $this->statement,
                );
            case PDO_FETCH_INTO:
            case FETCH_INTO:
                return $this->internalFetchClassOrObject(
                    isset($optArgs) ? $optArgs : null,
                    [],
                    $this->statement,
                );
            case PDO_FETCH_COLUMN:
            case FETCH_COLUMN:
                return $this->internalFetchColumn($this->statement, $fetchArgument == null ? 0 : $fetchArgument);
            case PDO_FETCH_ASSOC:
            case FETCH_ASSOC:
                return $this->internalFetchAssoc($this->statement);
            case PDO_FETCH_NUM:
            case FETCH_NUM:
                return $this->internalFetchNum($this->statement);
            case PDO_FETCH_BOTH:
            case FETCH_BOTH:
                return $this->internalFetchBoth($this->statement);
            default:
                return $this->internalFetchBoth($this->statement);
        }
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
        int $fetchStyle = PDO_FETCH_ASSOC,
        mixed $fetchArgument = null,
        mixed $optArgs = null
    ): mixed {
        switch ($fetchStyle) {
            case PDO_FETCH_OBJ:
            case PDO_FETCH_CLASS:
            case FETCH_OBJ:
            case FETCH_CLASS:
                if (null === $fetchArgument) {
                    $fetchArgument = '\stdClass';
                }
                return $this->internalFetchAllClassOrObjects(
                    $fetchArgument,
                    $optArgs == null ? [] : $optArgs,
                    $this->statement
                );
            case PDO_FETCH_COLUMN:
            case FETCH_COLUMN:
                return $this->internalFetchAllColumn($this->statement, $fetchArgument == null ? 0 : $fetchArgument);
            case PDO_FETCH_ASSOC:
            case FETCH_ASSOC:
                return $this->internalFetchAllAssoc($this->statement);
            case PDO_FETCH_NUM:
            case FETCH_NUM:
                return $this->internalFetchAllNum($this->statement);
            case PDO_FETCH_BOTH:
            case FETCH_BOTH:
                return $this->internalFetchAllBoth($this->statement);
            default:
                return $this->internalFetchAllBoth($this->statement);
        }
    }

    protected function internalFetchClassOrObject(
        $aClassOrObject,
        array $constructorArguments = null,
        $statement = null,
    ) {
        $rowData = $this->internalFetchAssoc($statement);
        if (is_array($rowData)) {
            return Reflections::createObjectAndSetPropertiesCaseInsenstive(
                $aClassOrObject,
                is_array($constructorArguments) ? $constructorArguments : [],
                $rowData
            );
        }
        return $rowData;
    }

    protected function internalFetchBoth($statement = null)
    {
        return $statement->fetch(PDO::FETCH_BOTH);
    }

    protected function internalFetchAssoc($statement = null)
    {
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    protected function internalFetchNum($statement = null)
    {
        return $statement->fetch(PDO::FETCH_NUM);
    }

    protected function internalFetchColumn($statement = null, $columnIndex = 0)
    {
        $rowData = $this->internalFetchNum($statement);
        if (is_array($rowData)) {
            return isset($rowData[$columnIndex]) ? $rowData[$columnIndex] : null;
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
        while ($data = $this->internalFetchColumn($statement, $columnIndex)) {
            $result[] = $data;
        }
        return $result;
    }

    protected function internalFetchAllClassOrObjects($aClassOrObject, array $constructorArguments, $statement = null)
    {
        $result = [];
        while ($row = $this->internalFetchClassOrObject($aClassOrObject, $constructorArguments, $statement)) {
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
