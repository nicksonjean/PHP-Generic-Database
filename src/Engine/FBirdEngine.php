<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use SensitiveParameter;
use AllowDynamicProperties;
use Exception;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\FBird\FBird;
use GenericDatabase\Engine\FBird\Arguments;
use GenericDatabase\Engine\FBird\Options;
use GenericDatabase\Engine\FBird\Attributes;
use GenericDatabase\Engine\FBird\DSN;
use GenericDatabase\Engine\FBird\Dump;
use GenericDatabase\Engine\FBird\Transaction;
use GenericDatabase\Helpers\GenericException;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Helpers\Types;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Helpers\Regex;
use GenericDatabase\Traits\Setter;
use GenericDatabase\Traits\Getter;
use GenericDatabase\Traits\Cleaner;
use GenericDatabase\Traits\Singleton;

/**
 * Dynamic and Static container class for FBirdEngine connections.
 *
 * @method static FBirdEngine|static setDriver(mixed $value): void
 * @method static FBirdEngine|static getDriver($value = null): mixed
 * @method static FBirdEngine|static setHost(mixed $value): void
 * @method static FBirdEngine|static getHost($value = null): mixed
 * @method static FBirdEngine|static setPort(mixed $value): void
 * @method static FBirdEngine|static getPort($value = null): mixed
 * @method static FBirdEngine|static setUser(mixed $value): void
 * @method static FBirdEngine|static getUser($value = null): mixed
 * @method static FBirdEngine|static setPassword(mixed $value): void
 * @method static FBirdEngine|static getPassword($value = null): mixed
 * @method static FBirdEngine|static setDatabase(mixed $value): void
 * @method static FBirdEngine|static getDatabase($value = null): mixed
 * @method static FBirdEngine|static setOptions(mixed $value): void
 * @method static FBirdEngine|static getOptions($value = null): mixed
 * @method static FBirdEngine|static setConnected(mixed $value): void
 * @method static FBirdEngine|static getConnected($value = null): mixed
 * @method static FBirdEngine|static setDsn(mixed $value): void
 * @method static FBirdEngine|static getDsn($value = null): mixed
 * @method static FBirdEngine|static setAttributes(mixed $value): void
 * @method static FBirdEngine|static getAttributes($value = null): mixed
 * @method static FBirdEngine|static setCharset(mixed $value): void
 * @method static FBirdEngine|static getCharset($value = null): mixed
 * @method static FBirdEngine|static setException(mixed $value): void
 * @method static FBirdEngine|static getException($value = null): mixed
 */
#[AllowDynamicProperties]
class FBirdEngine implements IConnection
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
     * @var ?int $rows = 0
     */
    private ?int $rows = 0;

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
     * @return FBirdEngine|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): FBirdEngine|string|int|bool|array|null
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
     * @return FBirdEngine
     */
    public static function __callStatic(string $name, array $arguments): FBirdEngine
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return FBirdEngine
     */
    private function preConnect(): FBirdEngine
    {
        Options::setOptions((array) $this->getOptions());
        $options = Options::getOptions();
        $this->setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return FBirdEngine
     * @throws GenericException
     */
    private function postConnect(): FBirdEngine
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the FBirdEngine connection.
     *
     * @param string $host The host of the database
     * @param string $user The user of the database
     * @param string $password The password of the database
     * @param string $database The name of the database
     * @param mixed $port The port of the database
     * @return FBirdEngine
     * @throws Exception
     */
    private function realConnect(
        string $host,
        string $user,
        #[SensitiveParameter] string $password,
        string $database,
        mixed $port
    ): FBirdEngine {
        $dsn = vsprintf('%s/%s:%s', [$host, $port, $database]);
        $this->setConnection(
            (string) !Options::getOptions(FBird::ATTR_PERSISTENT)
                ? ibase_connect($dsn, $user, $password)
                : ibase_pconnect($dsn, $user, $password)
        );
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return FBirdEngine
     * @throws Exception
     */
    public function connect(): FBirdEngine
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
        return $this->query('SELECT 1 FROM RDB$DATABASE') !== false;
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
            if (!Options::getOptions(FBird::ATTR_PERSISTENT)) {
                if (Compare::connection($this->getConnection()) === 'fbird/ibase') {
                    ibase_close($this->getConnection());
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
        return (Compare::connection($this->getConnection()) === 'fbird/ibase') && $this->getConnected();
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
        return 0;
    }

    /**
     * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
     *
     * @param mixed $params Content to be quoted
     * @return string|int
     */
    public function quote(mixed ...$params): string|int
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
     * Returns the number of rows affected by the executed statement.
     *
     * @return int|null The number of rows affected by the statement or null if the number is not available.
     */
    public function getRows(): ?int
    {
        return $this->rows;
    }

    /**
     * Returns the parameters that were bound to the SQL statement.
     *
     * @return array|null An array containing the parameters bound
     * to the SQL statement, or null if no parameters were bound.
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

    /**
     * Binds a value to a parameter in the SQL statement.
     *
     * @param mixed $param The name of the parameter or an associative array of parameters and values.
     * @param mixed $value The value to be bound to the parameter.
     * @return mixed The value bound to the parameter.
     */
    public function bindValue($stmt, $param, $value)
    {
        return $this->bindParam($stmt, $param, $value);
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $param The name of the parameter or an associative array of parameters and values.
     * @param mixed $value A variable that will be bound to the parameter.
     * @return mixed The value of the variable bound to the parameter.
     */
    public function bindParam($stmt, $param, &$value)
    {
        if (is_array($param)) {
            $this->params = [];
            foreach ($param as $key => $val) {
                $this->params[$key] = $val;
                $this->statement = $this->exec($stmt, $val);
            }
        } else {
            $value = (string) $value;
            if (is_float($value)) {
                $value = (float) $value;
            } elseif (is_int($value)) {
                $value = (int) $value;
            } elseif (is_bool($value)) {
                $value = (bool) $value;
            }
            $this->params = [$param => $value];
            $this->statement = $this->exec($stmt, $value);
        }
        return $value;
    }

    /**
     * Returns the number of rows returned by an statement.
     *
     * @param mixed $stmt The statement (optional).
     * @param bool|null $useQuery Defines whether to use the internal class statement (optional).
     * @return int|false The number of rows returned by the statement or false in case of an error.
     */
    public function rowCount(mixed ...$params): int|false
    {
        Errors::turnOff();
        if (!empty($params) && is_bool($params[0]) && $params[0] === true) {
            $stmt = $this->parse($this->query);
        } else {
            $query = Regex::noBinding($params[0]);
            $statement = ibase_prepare($this->getConnection(), $query);
            if (count($params) > 1) {
                $param = $params[1];
                $value = null;
                if (count($params) > 2) {
                    $value = $params[2];
                }
                if (is_array($param)) {
                    $this->params = [];
                    foreach ($param as $key => $val) {
                        $this->params[$key] = $val;
                        $stmt = $this->exec($statement, $val);
                    }
                } else {
                    $value = (string) $value;
                    if (is_float($value)) {
                        $value = (float) $value;
                    } elseif (is_int($value)) {
                        $value = (int) $value;
                    } elseif (is_bool($value)) {
                        $value = (bool) $value;
                    }
                    $stmt = $this->exec($statement, $value);
                }
            } else {
                $stmt = $this->exec($statement);
            }
        }
        $result = [];
        while ($data = ibase_fetch_assoc($stmt, IBASE_TEXT)) {
            $result[] = $data;
        }
        Errors::turnOn();
        return count($result);
    }

    /**
     * Returns the number of columns in an statement result.
     *
     * @return int|false The number of columns in the result or false in case of an error.
     */
    public function columnCount(): int|false
    {
        return ibase_num_fields($this->statement);
    }

    /**
     * Parses an SQL statement and returns an statement.
     *
     * @param mixed ...$params The parameters for the ibase_query() function.
     * @return mixed The statement resulting from the SQL statement.
     */
    private function parse(mixed ...$params): mixed
    {
        $this->query = Regex::noBinding($params[0]);
        return ibase_query($this->getConnection(), $this->query);
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return static|null
     */
    public function query(mixed ...$params): static|null
    {
        $this->rows = null;
        $this->statement = $this->parse(...$params);
        $count = $this->rowCount(true);
        if ($count > 0) {
            $this->rows = Types::false2Null($count);
        }
        return $this->run();
    }

    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return static|null
     */
    public function prepare(mixed ...$params): static|null
    {
        $this->query = Regex::noBinding($params[0]);
        $this->rows = null;
        $statement = ibase_prepare($this->getConnection(), $this->query);
        if (count($params) > 1) {
            $param = $params[1];
            $value = null;
            if (count($params) > 2) {
                $value = $params[2];
            }
            $this->bindValue($statement, $param, $value);
        } else {
            $this->statement = $this->exec($statement);
        }
        $count = $this->rowCount(...$params);
        if ($count > 0) {
            $this->rows = Types::false2Null($count);
        }
        return $this->run();
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return static|null
     * @throws GenericException
     */
    private function run(): static|null
    {
        if ($this->statement) {
            $err = $this->errorInfo($this->statement);
            if ($err) {
                throw new GenericException();
            } elseif (is_resource($this->statement)) {
                return $this;
            }
        } else {
            $this->errorInfo($this->getConnection());
            throw new GenericException();
        }
        return null;
    }

    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return mixed
     */
    public function exec(mixed ...$params): mixed
    {
        $stmt = $params[0];
        if (count($params) > 1) {
            $value = $params[1];
            return ibase_execute($stmt, $value);
        }
        return ibase_execute($stmt);
    }

    /**
     * Fetches the next row from the statement and returns it as an array.
     *
     * @param int $fetchStyle The fetch style (optional). Default is IBASE_FETCH_BOTH.
     * @return array|false The next row from the statement as an array, or false if there are no more rows.
     */
    public function fetch($fetchStyle = IBASE_FETCH_BOTH, $optArg1 = null)
    {
        switch ($fetchStyle) {
            case IBASE_FETCH_OBJ:
            case IBASE_FETCH_CLASS:
                return $this->internalFetchClassOrObject(isset($optArg1) ? $optArg1 : '\stdClass', []);
            case IBASE_FETCH_INTO:
                return $this->internalFetchClassOrObject(isset($optArg1) ? $optArg1 : null, []);
            case IBASE_FETCH_ASSOC:
                return $this->internalFetchAssoc();
            case IBASE_FETCH_NUM:
                return $this->internalFetchNum();
            case IBASE_FETCH_BOTH:
                return $this->internalFetchBoth();
            default:
                return $this->internalFetchBoth();
        }
    }

    /**
     * Fetches all rows from the statement and returns them as an array.
     *
     * @param int $fetchStyle The fetch style (optional). Default is IBASE_FETCH_ASSOC.
     * @return array An array containing all rows from the statement.
     */
    public function fetchAll($fetchStyle = IBASE_FETCH_ASSOC, $fetchArgument = null, $ctorArgs = null)
    {
        switch ($fetchStyle) {
            case IBASE_FETCH_OBJ:
            case IBASE_FETCH_CLASS:
                if (null === $fetchArgument) {
                    $fetchArgument = '\stdClass';
                }
                return $this->internalFetchAllClassOrObjects($fetchArgument, $ctorArgs == null ? [] : $ctorArgs);
            case IBASE_FETCH_COLUMN:
                return $this->internalFetchAllColumn($fetchArgument == null ? 0 : $fetchArgument);
            case IBASE_FETCH_BOTH:
                return $this->internalFetchAllBoth();
            case IBASE_FETCH_ASSOC:
                return $this->internalFetchAllAssoc();
            case IBASE_FETCH_NUM:
                return $this->internalFetchAllNum();
            default:
                return $this->internalFetchAllBoth();
        }
    }

    protected function internalFetchClassOrObject($aClassOrObject, array $constructorArguments = null)
    {
        $rowData = $this->internalFetchAssoc();
        if (is_array($rowData)) {
            return Reflections::createObjectAndSetPropertiesCaseInsenstive(
                $aClassOrObject,
                is_array($constructorArguments) ? $constructorArguments : [],
                $rowData
            );
        }
        return $rowData;
    }

    protected function internalFetchBoth()
    {
        $tmpData = ibase_fetch_assoc($this->statement, IBASE_TEXT);
        if (is_array($tmpData)) {
            return Arrays::toBoth($tmpData);
        }
        return false;
    }

    protected function internalFetchAssoc()
    {
        return ibase_fetch_assoc($this->statement, IBASE_TEXT);
    }

    protected function internalFetchNum()
    {
        return ibase_fetch_row($this->statement, IBASE_TEXT);
    }

    protected function internalFetchColumn($columnIndex = 0)
    {
        $rowData = $this->internalFetchNum();
        if (is_array($rowData)) {
            return isset($rowData[$columnIndex]) ? $rowData[$columnIndex] : null;
        }
        return false;
    }

    protected function internalFetchAllAssoc()
    {
        $result = [];
        while ($data = $this->internalFetchAssoc()) {
            $result[] = $data;
        }
        return $result;
    }

    protected function internalFetchAllNum()
    {
        $result = [];
        while ($data = $this->internalFetchNum()) {
            $result[] = $data;
        }
        return $result;
    }

    protected function internalFetchAllBoth()
    {
        $result = [];
        while ($data = $this->internalFetchBoth()) {
            $result[] = $data;
        }
        return $result;
    }

    protected function internalFetchAllColumn($columnIndex = 0)
    {
        $result = [];
        while ($data = $this->internalFetchColumn($columnIndex)) {
            $result[] = $data;
        }
        return $result;
    }

    protected function internalFetchAllClassOrObjects($aClassOrObject, array $constructorArguments)
    {
        $result = [];
        while ($row = $this->internalFetchClassOrObject($aClassOrObject, $constructorArguments)) {
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
        return FBird::getAttribute($name);
    }

    /**
     * This function sets an attribute from the database.
     *
     * @param mixed $name The attribute name
     * @param mixed $value The attribute value
     * @return void
     */
    public function setAttribute(mixed $name, mixed $value): void
    {
        FBird::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return int|false
     */
    public function errorCode(mixed $inst = null): int|false
    {
        return ibase_errcode();
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return array|false
     */
    public function errorInfo(mixed $inst = null): array|false
    {
        $errorCode = $this->errorCode();
        $result = false;
        if ($errorCode) {
            $result = [
                'code' => $this->errorCode(),
                'message' => ibase_errmsg(),
            ];
        }
        return $result;
    }
}
