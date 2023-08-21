<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use SQLite3;
use AllowDynamicProperties;
use Exception;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\SQLite\SQLite;
use GenericDatabase\Engine\SQLite\Arguments;
use GenericDatabase\Engine\SQLite\Options;
use GenericDatabase\Engine\SQLite\Attributes;
use GenericDatabase\Engine\SQLite\DSN;
use GenericDatabase\Engine\SQLite\Dump;
use GenericDatabase\Engine\SQLite\Transaction;
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

/**
 * Dynamic and Static container class for SQLiteEngine connections.
 *
 * @method static SQLiteEngine|static setDriver(mixed $value): void
 * @method static SQLiteEngine|static getDriver($value = null): mixed
 * @method static SQLiteEngine|static setHost(mixed $value): void
 * @method static SQLiteEngine|static getHost($value = null): mixed
 * @method static SQLiteEngine|static setPort(mixed $value): void
 * @method static SQLiteEngine|static getPort($value = null): mixed
 * @method static SQLiteEngine|static setUser(mixed $value): void
 * @method static SQLiteEngine|static getUser($value = null): mixed
 * @method static SQLiteEngine|static setPassword(mixed $value): void
 * @method static SQLiteEngine|static getPassword($value = null): mixed
 * @method static SQLiteEngine|static setDatabase(mixed $value): void
 * @method static SQLiteEngine|static getDatabase($value = null): mixed
 * @method static SQLiteEngine|static setOptions(mixed $value): void
 * @method static SQLiteEngine|static getOptions($value = null): mixed
 * @method static SQLiteEngine|static setConnected(mixed $value): void
 * @method static SQLiteEngine|static getConnected($value = null): mixed
 * @method static SQLiteEngine|static setDsn(mixed $value): void
 * @method static SQLiteEngine|static getDsn($value = null): mixed
 * @method static SQLiteEngine|static setAttributes(mixed $value): void
 * @method static SQLiteEngine|static getAttributes($value = null): mixed
 * @method static SQLiteEngine|static setCharset(mixed $value): void
 * @method static SQLiteEngine|static getCharset($value = null): mixed
 * @method static SQLiteEngine|static setException(mixed $value): void
 * @method static SQLiteEngine|static getException($value = null): mixed
 */
#[AllowDynamicProperties]
class SQLiteEngine implements IConnection
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
     * @return SQLiteEngine|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): SQLiteEngine|string|int|bool|array|null
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
     * @return SQLiteEngine
     */
    public static function __callStatic(string $name, array $arguments): SQLiteEngine
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return SQLiteEngine
     */
    private function preConnect(): SQLiteEngine
    {
        Options::setOptions((array) $this->getOptions());
        $options = Options::getOptions();
        $this->setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return SQLiteEngine
     * @throws GenericException
     */
    private function postConnect(): SQLiteEngine
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the SQLiteEngine connection.
     *
     * @param string $database The path of the database file
     * @param int|null $flags = null Flags of the database behavior
     * @return SQLiteEngine
     * @throws Exception
     */
    private function realConnect(string $database, int $flags = null): SQLiteEngine
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
     * @return SQLiteEngine
     * @throws Exception
     */
    public function connect(): SQLiteEngine
    {
        try {
            $this
                ->preConnect()
                ->setInstance($this)
                ->setDsn($this->parseDsn())
                ->realConnect(
                    (string) $this->getDatabase(),
                    Options::flags()
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
            if (!Options::getOptions(SQLite::ATTR_PERSISTENT)) {
                if (Compare::connection($this->getConnection()) === 'sqlite3') {
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
        return (Compare::connection($this->getConnection()) === 'sqlite3') && $this->getConnected();
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
        return $this->getConnection()->lastInsertRowID();
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
        $internalPrepare = function (mixed $preparedParams, $stmt) {
            $types = 0;
            $index = 0;
            foreach ($preparedParams as &$arg) {
                if (is_float($arg)) {
                    $types = SQLITE3_FLOAT;
                } elseif (is_integer($arg)) {
                    $types = SQLITE3_INTEGER;
                } elseif (is_string($arg)) {
                    $types = SQLITE3_TEXT;
                } elseif (is_null($arg)) {
                    $types = SQLITE3_NULL;
                } else {
                    $types = SQLITE3_BLOB;
                }
                call_user_func_array([$stmt, 'bindParam'], [array_keys($preparedParams)[$index], &$arg, $types]);
                $index++;
            }
            return $stmt;
        };

        if (!empty($params)) {
            $stmt = $params[0];
            if (isset($params[2]) && is_array($params[2])) {
                if (Arrays::isMultidimensional($params[2])) {
                    foreach ($params[2] as $param) {
                        $statement = $internalPrepare($param, $stmt);
                        $stmt = $statement->execute();
                    }
                } else {
                    $statement = $internalPrepare($params[2], $stmt);
                    $stmt = $statement->execute();
                }
            } else {
                $paramValues = [];
                for ($i = 2; $i < count($params); $i++) {
                    $paramValues[] = $params[$i];
                }
                $param = Translater::parameters($params[1], $paramValues);
                $statement = $internalPrepare($param, $stmt);
                $stmt = $statement->execute();
            }
        }
        return count($this->internalFetchAllAssoc($stmt));
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int The number of affected rows
     */
    public function affectedRows(): int|false
    {
        return $this->getConnection()->changes();
    }

    /**
     * Returns the number of columns in an statement result.
     *
     * @param mixed ...$params The parameters required for the function.
     * @return int|false The number of columns in the result or false in case of an error.
     */
    public function columnCount(mixed ...$params): int|false
    {
        return 0;
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
            $types = 0;
            $index = 0;
            foreach ($preparedParams as &$arg) {
                if (is_float($arg)) {
                    $types = SQLITE3_FLOAT;
                } elseif (is_integer($arg)) {
                    $types = SQLITE3_INTEGER;
                } elseif (is_string($arg)) {
                    $types = SQLITE3_TEXT;
                } elseif (is_null($arg)) {
                    $types = SQLITE3_NULL;
                } else {
                    $types = SQLITE3_BLOB;
                }
                call_user_func_array([$stmt, 'bindParam'], [array_keys($preparedParams)[$index], &$arg, $types]);
                $index++;
            }
            return $stmt;
        };

        if (!empty($params)) {
            $stmt = $params[0];
            if (isset($params[2]) && is_array($params[2])) {
                $this->params = $params[2];
                if (Arrays::isMultidimensional($params[2])) {
                    foreach ($params[2] as $param) {
                        $statement = $internalPrepare($param, $stmt);
                        $this->exec($statement);
                        $this->affectedRows += $this->affectedRows();
                    }
                } else {
                    $statement = $internalPrepare($params[2], $stmt);
                    $this->exec($statement);
                    $this->affectedRows += $this->affectedRows();
                }
            } else {
                $paramValues = [];
                for ($i = 2; $i < count($params); $i++) {
                    $paramValues[] = $params[$i];
                }
                $this->params = Translater::parameters($params[1], $paramValues);
                $statement = $internalPrepare($this->params, $stmt);
                $this->exec($statement);
                $this->affectedRows += $this->affectedRows();
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
        return $this->query = Translater::escape($params[0], Translater::SQL_DIALECT_NONE);
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
        $this->statement = $this->getConnection()->query($this->parse($query));
        $stmt = $this->getConnection()->prepare($this->parse($query));
        array_unshift($params, $stmt);
        $this->queriedRows = Regex::isSelect($this->query) ? $this->numRows(...$params) : 0;
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
        $query = $params[0];
        $stmt = $this->getConnection()->prepare($this->parse($query));
        array_unshift($params, $stmt);
        if (isset($params[1])) {
            $this->bindParam(...$params);
            $this->queriedRows = Regex::isSelect($this->query) ? $this->numRows(...$params) : 0;
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
        $stmt = $params[0];
        return $this->statement = $stmt->execute();
    }

    /**
     * Fetches the next row from the statement and returns it as an array.
     *
     * @param int $fetchStyle The fetch style (optional). Default is SQLITE_FETCH_BOTH.
     * @return array|false The next row from the statement as an array, or false if there are no more rows.
     */
    public function fetch($fetchStyle = SQLITE_FETCH_BOTH, $fetchArgument = null, $optArg1 = null)
    {
        switch ($fetchStyle) {
            case SQLITE_FETCH_OBJ:
            case SQLITE_FETCH_CLASS:
            case FETCH_OBJ:
            case FETCH_CLASS:
                return $this->internalFetchClassOrObject(
                    isset($optArg1) ? $optArg1 : '\stdClass',
                    [],
                    $this->statement,
                );
            case SQLITE_FETCH_INTO:
            case FETCH_INTO:
                return $this->internalFetchClassOrObject(
                    isset($optArg1) ? $optArg1 : null,
                    [],
                    $this->statement,
                );
            case SQLITE_FETCH_COLUMN:
            case FETCH_COLUMN:
                return $this->internalFetchColumn($this->statement, $fetchArgument == null ? 0 : $fetchArgument);
            case SQLITE_FETCH_ASSOC:
            case FETCH_ASSOC:
                return $this->internalFetchAssoc($this->statement);
            case SQLITE_FETCH_NUM:
            case FETCH_NUM:
                return $this->internalFetchNum($this->statement);
            case SQLITE_FETCH_BOTH:
            case FETCH_BOTH:
                return $this->internalFetchBoth($this->statement);
            default:
                return $this->internalFetchBoth($this->statement);
        }
    }

    /**
     * Fetches all rows from the statement and returns them as an array.
     *
     * @param int $fetchStyle The fetch style (optional). Default is SQLITE_FETCH_ASSOC.
     * @return array An array containing all rows from the statement.
     */
    public function fetchAll($fetchStyle = SQLITE_FETCH_ASSOC, $fetchArgument = null, $ctorArgs = null)
    {
        switch ($fetchStyle) {
            case SQLITE_FETCH_OBJ:
            case SQLITE_FETCH_CLASS:
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
            case SQLITE_FETCH_COLUMN:
            case FETCH_COLUMN:
                return $this->internalFetchAllColumn($this->statement, $fetchArgument == null ? 0 : $fetchArgument);
            case SQLITE_FETCH_ASSOC:
            case FETCH_ASSOC:
                return $this->internalFetchAllAssoc($this->statement);
            case SQLITE_FETCH_NUM:
            case FETCH_NUM:
                return $this->internalFetchAllNum($this->statement);
            case SQLITE_FETCH_BOTH:
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
        return $statement->fetchArray(SQLITE3_BOTH);
    }

    protected function internalFetchAssoc($statement = null)
    {
        return $statement->fetchArray(SQLITE3_ASSOC);
    }

    protected function internalFetchNum($statement = null)
    {
        return $statement->fetchArray(SQLITE3_NUM);
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
        return SQLite::getAttribute($name);
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
        SQLite::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorCode(mixed $inst = null): mixed
    {
        return $this->getConnection()->lastErrorCode();
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorInfo(mixed $inst = null): mixed
    {
        return $this->getConnection()->lastErrorMsg();
    }
}
