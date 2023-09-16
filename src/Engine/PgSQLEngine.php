<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

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
use GenericDatabase\Engine\PgSQL\Statements;
use GenericDatabase\Helpers\CustomException;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Errors;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Translater;
use GenericDatabase\Helpers\Validations;
use GenericDatabase\Shared\Setter;
use GenericDatabase\Shared\Getter;
use GenericDatabase\Shared\Cleaner;
use GenericDatabase\Shared\Singleton;
use PgSql\Result;

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
     * @return PgSQLEngine|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): PgSQLEngine|string|int|bool|array|null
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
     * @throws CustomException
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
     * @return string|CustomException
     * @throws CustomException
     */
    private function parseDsn(): string|CustomException
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
        $query = pg_query(
            $this->getConnection(),
            sprintf("SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS %s", $filter)
        );
        $autoKeyRes = pg_fetch_assoc($query);
        if (isset($autoKeyRes['column_name'])) {
            $query = vsprintf(
                "SELECT pg_catalog.setval(pg_get_serial_sequence('%s', '%s'), MAX(%s)) AS value FROM %s;",
                [$name, $autoKeyRes['column_name'], $autoKeyRes['column_name'], $name]
            );
            $maxIndex = pg_query($this->getConnection(), $query);
            $maxIndexRes = pg_fetch_assoc($maxIndex);
            return $maxIndexRes['value'];
        } else {
            return pg_last_oid(self::$statement);
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
        return $this->queryRows;
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
     * Binds an array multiple parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArrayMulti(mixed ...$params): void
    {
        foreach (Arrays::arrayValuesRecursive($params['sqlArgs']) as $param) {
            $this->exec($params['sqlStatement'], $param);
            $this->affectedRows += (Validations::isSelect($this->queryString)) ? 0 : pg_affected_rows(self::$statement);
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
        $this->exec($params['sqlStatement'], array_values($params['sqlArgs']));
        $this->affectedRows += (Validations::isSelect($this->queryString)) ? 0 : pg_affected_rows(self::$statement);
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
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
        $this->exec($params['sqlStatement'], $params['sqlArgs']);
        $this->affectedRows += (Validations::isSelect($this->queryString)) ? 0 : pg_affected_rows(self::$statement);
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
     * @return string The statement resulting from the SQL statement.
     */
    private function parse(mixed ...$params): string
    {
        $this->queryString = Translater::binding(
            Translater::escape($params[0], Translater::SQL_DIALECT_DQUOTE),
            Translater::BIND_DOLLAR_SIGN
        );
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
            self::$statement = pg_query($this->getConnection(), $this->parse(...$params));
            $this->queryRows = pg_num_rows(self::$statement);
            $this->queryColumns = pg_num_fields(self::$statement);
            $this->affectedRows += (Validations::isSelect($this->queryString)) ? 0 : pg_affected_rows(self::$statement);
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
        $driver = Compare::connection($this->getConnection());
        $this->resetMetadata();
        if (!empty($params)) {
            $stmtName = Validations::randomString(18);
            self::$statement = pg_prepare($this->getConnection(), $stmtName, $this->parse(...$params));
            array_unshift($params, $stmtName);
            $bindParams = $this->makeArgs($driver, ...$params);
            (array_key_exists(1, $params)) ? $this->bindParam(...$bindParams) : $this->query(...$params);
            $this->queryRows = pg_num_rows(self::$statement);
            $this->queryColumns = pg_num_fields(self::$statement);
        }
        return $this;
    }

    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return bool|Result
     */
    public function exec(mixed ...$params): bool|Result
    {
        $stmtname = !empty($params[0]) ? $params[0] : Validations::randomString(18);
        $param = !empty($params[1]) ? $params[1] : [];
        return self::$statement = pg_execute($this->getConnection(), $stmtname, $param);
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
        return pg_last_error($this->getConnection()) ? pg_last_error($this->getConnection()) : $inst;
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param ?mixed $inst = null
     * @return string
     */
    public function errorInfo(mixed $inst = null): string
    {
        return pg_last_error($this->getConnection()) ? pg_last_error($this->getConnection()) : $inst;
    }
}
