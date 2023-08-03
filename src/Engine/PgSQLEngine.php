<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use SensitiveParameter;
use AllowDynamicProperties;
use Exception;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\PgSQL\PgSQL;
use GenericDatabase\Engine\PgSQL\Arguments;
use GenericDatabase\Engine\PgSQL\Options;
use GenericDatabase\Engine\PgSQL\Attributes;
use GenericDatabase\Engine\PgSQL\DSN;
use GenericDatabase\Engine\PgSQL\Dump;
use GenericDatabase\Engine\PgSQL\Transaction;
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
 * Dynamic and Static container class for PgSQLEngine connections.
 *
 * @method static PgSQLEngine|static setDriver(mixed $value): void
 * @method static PgSQLEngine|static getDriver($value = null): mixed
 * @method static PgSQLEngine|static setHost(mixed $value): void
 * @method static PgSQLEngine|static getHost($value = null): mixed
 * @method static PgSQLEngine|static setPort(mixed $value): void
 * @method static PgSQLEngine|static getPort($value = null): mixed
 * @method static PgSQLEngine|static setUser(mixed $value): void
 * @method static PgSQLEngine|static getUser($value = null): mixed
 * @method static PgSQLEngine|static setPassword(mixed $value): void
 * @method static PgSQLEngine|static getPassword($value = null): mixed
 * @method static PgSQLEngine|static setDatabase(mixed $value): void
 * @method static PgSQLEngine|static getDatabase($value = null): mixed
 * @method static PgSQLEngine|static setOptions(mixed $value): void
 * @method static PgSQLEngine|static getOptions($value = null): mixed
 * @method static PgSQLEngine|static setConnected(mixed $value): void
 * @method static PgSQLEngine|static getConnected($value = null): mixed
 * @method static PgSQLEngine|static setDsn(mixed $value): void
 * @method static PgSQLEngine|static getDsn($value = null): mixed
 * @method static PgSQLEngine|static setAttributes(mixed $value): void
 * @method static PgSQLEngine|static getAttributes($value = null): mixed
 * @method static PgSQLEngine|static setCharset(mixed $value): void
 * @method static PgSQLEngine|static getCharset($value = null): mixed
 * @method static PgSQLEngine|static setException(mixed $value): void
 * @method static PgSQLEngine|static getException($value = null): mixed
 */
#[AllowDynamicProperties]
class PgSQLEngine implements IConnection
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
     * @return PgSQLEngine|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): PgSQLEngine|string|int|bool|array|null
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
     * @return PgSQLEngine
     */
    public static function __callStatic(string $name, array $arguments): PgSQLEngine
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return PgSQLEngine
     */
    private function preConnect(): PgSQLEngine
    {
        Options::setOptions((array) $this->getOptions());
        $options = Options::getOptions();
        $this->setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return PgSQLEngine
     * @throws GenericException
     */
    private function postConnect(): PgSQLEngine
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the PgSQLEngine connection.
     *
     * @param string $dsn The Data source name of the connection
     * @return PgSQLEngine
     * @throws Exception
     */
    private function realConnect(string $dsn): PgSQLEngine
    {
        $this->setConnection(
            (string) !Options::getOptions(PgSQL::ATTR_PERSISTENT)
                ? pg_connect($dsn, Attributes::getFlags())
                : pg_pconnect($dsn, Attributes::getFlags())
        );
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return PgSQLEngine
     * @throws Exception
     */
    public function connect(): PgSQLEngine
    {
        try {
            $this
                ->preConnect()
                ->setInstance($this)
                ->realConnect(
                    $this->parseDsn()
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
            if (!Options::getOptions(PgSQL::ATTR_PERSISTENT)) {
                if (Compare::connection($this->getConnection()) === 'pgsql') {
                    pg_close($this->getConnection());
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
        return (Compare::connection($this->getConnection()) === 'pgsql') && $this->getConnected();
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
        $filter = sprintf("WHERE column_default LIKE 'nextval%%' AND table_name = '%s'", $name);
        $query = $this->parse(sprintf("SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS %s", $filter));
        $autoKeyRes = pg_fetch_assoc($query);
        if (isset($autoKeyRes['column_name'])) {
            $query = vsprintf(
                "SELECT pg_catalog.setval(pg_get_serial_sequence('%s', '%s'), COALESCE(MAX(%s))) AS value FROM %s;",
                [$name, $autoKeyRes['column_name'], $autoKeyRes['column_name'], $name]
            );
            $maxIndex = $this->parse($query);
            $maxIndexRes = pg_fetch_assoc($maxIndex);
            return $maxIndexRes['value'];
        } else {
            return 0;
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
            return array_map(fn ($str) => $this->quote($str, $quote), $string);
        } elseif ($string && preg_match("/^(?:\d+\.\d+|[1-9]\d*)$/S", $string)) {
            return $string;
        }
        $quoted = fn ($str) => pg_escape_string($this->getConnection(), $str);
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
     * @return int The number of affected rows
     */
    private function numRows(mixed ...$params): int
    {
        return pg_num_rows(...$params);
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @param mixed ...$params The parameters required for the function.
     * @return int The number of affected rows
     */
    private function affectedRows(mixed ...$params): int
    {
        return pg_affected_rows(...$params);
    }

    /**
     * Returns the number of columns in an statement result.
     *
     * @return int|false The number of columns in the result or false in case of an error.
     */
    public function columnCount(): int|false
    {
        return pg_num_fields($this->statement);
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    public function bindParam(mixed ...$params): void
    {
        if (!empty($params)) {
            $stmtname = $params[0];
            if (is_array($params[2])) {
                $this->statement = pg_prepare($this->getConnection(), $stmtname, $this->query);
                if (Arrays::isMultidimensional($params[2])) {
                    foreach ((array) Arrays::arrayValuesRecursive($params[2]) as $key => $param) {
                        $this->params[$key] = $param;
                        $this->exec($stmtname, $param);
                        $this->queriedRows += $this->numRows($this->statement);
                        $this->affectedRows += $this->queriedRows !== 0 ? 0 : $this->affectedRows($this->statement);
                    }
                } else {
                    foreach ($params[2] as $key => $val) {
                        $this->params[$key] = $val;
                    }
                    $this->exec($stmtname, array_values($this->params));
                    $this->queriedRows += $this->numRows($this->statement);
                    $this->affectedRows += $this->queriedRows !== 0 ? 0 : $this->affectedRows($this->statement);
                }
            } else {
                $this->statement = pg_prepare($this->getConnection(), $stmtname, $this->query);
                for ($i = 2; $i < count($params); $i++) {
                    $this->params[] = $params[$i];
                }
                $this->exec($stmtname, $this->params);
                $this->queriedRows += $this->numRows($this->statement);
                $this->affectedRows += $this->queriedRows !== 0 ? 0 : $this->affectedRows($this->statement);
            }
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
        $this->query = Regex::noBinding($params[0], false);
        return pg_query($this->getConnection(), $this->query);
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return static|null
     */
    public function query(mixed ...$params): static|null
    {
        if (!empty($params)) {
            $this->statement = $this->parse(...$params);
            $this->queriedRows += $this->numRows($this->statement);
            $this->affectedRows += $this->queriedRows !== 0 ? 0 : $this->affectedRows($this->statement);
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
        if (!empty($params)) {
            $this->query = Regex::noBinding($params[0], false);
            if (isset($params[1])) {
                array_unshift($params, Regex::randomString(18));
                $this->bindParam(...$params);
            } else {
                $this->query(...$params);
            }
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
        $stmtname = !empty($params[0]) ? $params[0] : Regex::randomString(18);
        $param = !empty($params[1]) ? $params[1] : [];
        return $this->statement = pg_execute($this->getConnection(), $stmtname, $param);
    }

    /**
     * Fetches the next row from the statement and returns it as an array.
     *
     * @param int $fetchStyle The fetch style (optional). Default is PGSQL_FETCH_BOTH.
     * @return array|false The next row from the statement as an array, or false if there are no more rows.
     */
    public function fetch($fetchStyle = PGSQL_FETCH_BOTH, $fetchArgument = null, $optArg1 = null)
    {
        switch ($fetchStyle) {
            case PGSQL_FETCH_OBJ:
            case PGSQL_FETCH_CLASS:
            case FETCH_OBJ:
            case FETCH_CLASS:
                return $this->internalFetchClassOrObject(
                    isset($optArg1) ? $optArg1 : '\stdClass',
                    [],
                    $this->statement,
                );
            case PGSQL_FETCH_INTO:
            case FETCH_INTO:
                return $this->internalFetchClassOrObject(
                    isset($optArg1) ? $optArg1 : null,
                    [],
                    $this->statement,
                );
            case PGSQL_FETCH_COLUMN:
            case FETCH_COLUMN:
                return $this->internalFetchColumn($this->statement, $fetchArgument == null ? 0 : $fetchArgument);
            case PGSQL_FETCH_ASSOC:
            case FETCH_ASSOC:
                return $this->internalFetchAssoc($this->statement);
            case PGSQL_FETCH_NUM:
            case FETCH_NUM:
                return $this->internalFetchNum($this->statement);
            case PGSQL_FETCH_BOTH:
            case FETCH_BOTH:
                return $this->internalFetchBoth($this->statement);
            default:
                return $this->internalFetchBoth($this->statement);
        }
    }

    /**
     * Fetches all rows from the statement and returns them as an array.
     *
     * @param int $fetchStyle The fetch style (optional). Default is PGSQL_FETCH_ASSOC.
     * @return array An array containing all rows from the statement.
     */
    public function fetchAll($fetchStyle = PGSQL_FETCH_ASSOC, $fetchArgument = null, $ctorArgs = null)
    {
        switch ($fetchStyle) {
            case PGSQL_FETCH_OBJ:
            case PGSQL_FETCH_CLASS:
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
            case PGSQL_FETCH_COLUMN:
            case FETCH_COLUMN:
                return $this->internalFetchAllColumn($this->statement, $fetchArgument == null ? 0 : $fetchArgument);
            case PGSQL_FETCH_ASSOC:
            case FETCH_ASSOC:
                return $this->internalFetchAllAssoc($this->statement);
            case PGSQL_FETCH_NUM:
            case FETCH_NUM:
                return $this->internalFetchAllNum($this->statement);
            case PGSQL_FETCH_BOTH:
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
        return pg_fetch_array($statement);
    }

    protected function internalFetchAssoc($statement = null)
    {
        return pg_fetch_assoc($statement);
    }

    protected function internalFetchNum($statement = null)
    {
        return pg_fetch_row($statement);
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
        return pg_fetch_all($statement);
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
        return pg_fetch_all_columns($statement, $columnIndex);
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
        return PgSQL::getAttribute($name);
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
        PgSQL::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param ?mixed $inst = null
     * @return string
     */
    public function errorCode(mixed $inst = null): string
    {
        return pg_last_error($this->getConnection());
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param ?mixed $inst = null
     * @return string
     */
    public function errorInfo(mixed $inst = null): string
    {
        return pg_last_error($this->getConnection());
    }
}
