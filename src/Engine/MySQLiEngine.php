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
use GenericDatabase\Traits\Setter;
use GenericDatabase\Traits\Getter;
use GenericDatabase\Traits\Cleaner;
use GenericDatabase\Traits\Singleton;

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
     * @param mixed ...$params The parameters required for the function.
     * @return int|false The number of affected rows
     */
    public function numRows(mixed ...$params): int|false
    {
        return mysqli_num_rows(...$params);
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @param mixed ...$params The parameters required for the function.
     * @return int The number of affected rows
     */
    public function affectedRows(mixed ...$params): int|false
    {
        return mysqli_affected_rows(...$params);
    }

    /**
     * Returns the number of columns in an statement result.
     *
     * @param mixed ...$params The parameters required for the function.
     * @return int|false The number of columns in the result or false in case of an error.
     */
    public function columnCount(mixed ...$params): int|false
    {
        return mysqli_field_count(...$params);
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return int
     */
    public function bindParam(mixed ...$params): int
    {
        $internalPrepare = function (mixed $preparedParams, $stmt) {
            $types = '';
            $referenceParams = [];
            foreach ($preparedParams as &$arg) {
                $referenceParams[] = &$arg;
                if (is_float($arg)) {
                    $types .= 'd';
                } elseif (is_integer($arg)) {
                    $types .= 'i';
                } elseif (is_string($arg)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
            }
            array_unshift($referenceParams, $types);
            call_user_func_array([$stmt, 'bind_param'], $referenceParams);
        };

        if (!empty($params)) {
            $stmt = $params[0];
            if (isset($params[2]) && is_array($params[2])) {
                if (Arrays::isMultidimensional($params[2])) {
                    foreach ((array) Arrays::arrayValuesRecursive($params[2]) as $key => $param) {
                        $this->params[$key] = $param;
                        $internalPrepare($this->params[$key], $stmt);
                        $this->exec($stmt);
                    }
                } else {
                    foreach ($params[2] as $key => $param) {
                        $this->params[$key] = $param;
                    }
                    $internalPrepare($params[2], $stmt);
                    $this->exec($stmt);
                }
            } else {
                $paramValues = [];
                for ($i = 2; $i < count($params); $i++) {
                    $paramValues[] = $params[$i];
                }
                $this->params = Translater::parameters($params[1], $paramValues);
                $internalPrepare($this->params, $stmt);
                $this->exec($stmt);
            }
        }
        return 0;
    }

    /**
     * Parses an SQL statement and returns an statement.
     *
     * @param mixed ...$params The parameters for the query function.
     * @return mixed The statement resulting from the SQL statement.
     */
    private function parse(mixed ...$params): mixed
    {
        $this->query = Translater::binding(Translater::escape($params[0], Translater::SQL_DIALECT_BTICK));
        $resultMode = isset($params[1]) ? (int) $params[1] : MYSQLI_STORE_RESULT;
        return mysqli_query($this->getConnection(), $this->query, $resultMode);
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return static|null
     */
    public function query(mixed ...$params): static|null
    {
        $query = $params[0];
        $resultMode = isset($params[1]) ? (int) $params[1] : MYSQLI_STORE_RESULT;
        $this->statement = $this->parse($query, $resultMode);
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
        $this->query = Translater::binding(Translater::escape($params[0], Translater::SQL_DIALECT_BTICK));
        $stmt = mysqli_prepare($this->getConnection(), $this->query);
        if (isset($params[1])) {
            array_unshift($params, $stmt);
            $this->bindParam(...$params);
        } else {
            $this->exec($stmt);
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
        $this->statement = $statement->execute() ? $statement->get_result() : false;
        $this->queriedRows = get_class($this->statement) === 'mysqli_result' ? $this->statement?->num_rows : 0;
        $this->affectedRows += $statement->affected_rows === $this->statement?->num_rows
            ? 0
            : $statement->affected_rows;
        return $this->statement;
    }

    /**
     * Fetches the next row from the statement and returns it as an array.
     *
     * @param int $fetchStyle The fetch style (optional). Default is MYSQLI_FETCH_BOTH.
     * @return array|false The next row from the statement as an array, or false if there are no more rows.
     */
    public function fetch($fetchStyle = MYSQLI_FETCH_BOTH, $fetchArgument = null, $optArg1 = null)
    {
        switch ($fetchStyle) {
            case MYSQLI_FETCH_OBJ:
            case MYSQLI_FETCH_CLASS:
            case FETCH_OBJ:
            case FETCH_CLASS:
                return $this->internalFetchClassOrObject(
                    isset($optArg1) ? $optArg1 : '\stdClass',
                    [],
                    $this->statement,
                );
            case MYSQLI_FETCH_INTO:
            case FETCH_INTO:
                return $this->internalFetchClassOrObject(
                    isset($optArg1) ? $optArg1 : null,
                    [],
                    $this->statement,
                );
            case MYSQLI_FETCH_COLUMN:
            case FETCH_COLUMN:
                return $this->internalFetchColumn($this->statement, $fetchArgument == null ? 0 : $fetchArgument);
            case MYSQLI_FETCH_ASSOC:
            case FETCH_ASSOC:
                return $this->internalFetchAssoc($this->statement);
            case MYSQLI_FETCH_NUM:
            case FETCH_NUM:
                return $this->internalFetchNum($this->statement);
            case MYSQLI_FETCH_BOTH:
            case FETCH_BOTH:
                return $this->internalFetchBoth($this->statement);
            default:
                return $this->internalFetchBoth($this->statement);
        }
    }

    /**
     * Fetches all rows from the statement and returns them as an array.
     *
     * @param int $fetchStyle The fetch style (optional). Default is MYSQLI_FETCH_ASSOC.
     * @return array An array containing all rows from the statement.
     */
    public function fetchAll($fetchStyle = MYSQLI_FETCH_ASSOC, $fetchArgument = null, $ctorArgs = null)
    {
        switch ($fetchStyle) {
            case MYSQLI_FETCH_OBJ:
            case MYSQLI_FETCH_CLASS:
            case FETCH_OBJ:
            case FETCH_CLASS:
                if (null === $fetchArgument) {
                    $fetchArgument = '\stdClass';
                }
                return $this->internalFetchAllClassOrObjects(
                    $fetchArgument,
                    $ctorArgs == null ? [] : $ctorArgs,
                    $this->statement
                );
            case MYSQLI_FETCH_COLUMN:
            case FETCH_COLUMN:
                return $this->internalFetchAllColumn($this->statement, $fetchArgument == null ? 0 : $fetchArgument);
            case MYSQLI_FETCH_ASSOC:
            case FETCH_ASSOC:
                return $this->internalFetchAllAssoc($this->statement);
            case MYSQLI_FETCH_NUM:
            case FETCH_NUM:
                return $this->internalFetchAllNum($this->statement);
            case MYSQLI_FETCH_BOTH:
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
