<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use ReflectionException;
use SensitiveParameter;
use AllowDynamicProperties;
use Exception;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\OCI\Connection\OCI;
use GenericDatabase\Engine\OCI\Connection\Arguments;
use GenericDatabase\Engine\OCI\Connection\Options;
use GenericDatabase\Engine\OCI\Connection\Attributes;
use GenericDatabase\Engine\OCI\Connection\DSN;
use GenericDatabase\Engine\OCI\Connection\Dump;
use GenericDatabase\Engine\OCI\Connection\Transaction;
use GenericDatabase\Engine\OCI\Connection\Statements;
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
use stdClass;

/**
 * Dynamic and Static container class for OCIConnection connections.
 *
 * @method static OCIConnection|void setDriver(mixed $value): void
 * @method static OCIConnection|string getDriver($value = null): string
 * @method static OCIConnection|void setHost(mixed $value): void
 * @method static OCIConnection|string getHost($value = null): string
 * @method static OCIConnection|void setPort(mixed $value): void
 * @method static OCIConnection|int getPort($value = null): int
 * @method static OCIConnection|void setUser(mixed $value): void
 * @method static OCIConnection|string getUser($value = null): string
 * @method static OCIConnection|void setPassword(mixed $value): void
 * @method static OCIConnection|string getPassword($value = null): string
 * @method static OCIConnection|void setDatabase(mixed $value): void
 * @method static OCIConnection|string getDatabase($value = null): string
 * @method static OCIConnection|void setOptions(mixed $value): void
 * @method static OCIConnection|array|null getOptions($value = null): array|null
 * @method static OCIConnection|static setConnected(mixed $value): void
 * @method static OCIConnection|mixed getConnected($value = null): mixed
 * @method static OCIConnection|void setDsn(mixed $value): void
 * @method static OCIConnection|mixed getDsn($value = null): mixed
 * @method static OCIConnection|void setAttributes(mixed $value): void
 * @method static OCIConnection|mixed getAttributes($value = null): mixed
 * @method static OCIConnection|void setCharset(mixed $value): void
 * @method static OCIConnection|string getCharset($value = null): string
 * @method static OCIConnection|void setException(mixed $value): void
 * @method static OCIConnection|mixed getException($value = null): mixed
 */
#[AllowDynamicProperties]
class OCIConnection implements IConnection
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
     * Lasts params query executed
     * @var ?array $queryParameters = []
     */
    private ?array $queryParameters = [];

    /**
     * Last string query executed
     * @var string $queryString = ''
     */
    private string $queryString = '';

    public function __construct()
    {
    }

    /**
     * Triggered when invoking inaccessible methods in an object context
     *
     * @param string $name Name of the method
     * @param array $arguments Array of arguments
     * @return OCIConnection|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): OCIConnection|string|int|bool|array|null
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
     * @return OCIConnection
     * @throws ReflectionException
     */
    public static function __callStatic(string $name, array $arguments): OCIConnection
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return OCIConnection
     * @throws ReflectionException
     */
    private function preConnect(): OCIConnection
    {
        Options::setOptions((array) static::getOptions());
        $options = Options::getOptions();
        static::setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return OCIConnection
     * @throws CustomException
     */
    private function postConnect(): OCIConnection
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the OCIConnection connection.
     *
     * @param string $host The host of the database
     * @param string $user The user of the database
     * @param string $password The password of the database
     * @param string $database The name of the database
     * @param mixed $port The port of the database
     * @param string $charset The charset of the database
     * @return OCIConnection
     * @throws Exception
     */
    private function realConnect(
        mixed $host,
        mixed $user,
        #[SensitiveParameter] mixed $password,
        mixed $database,
        mixed $port,
        mixed $charset
    ): OCIConnection {
        $dsn = vsprintf('%s:%s/%s', [$host, $port, $database]);
        $this->setConnection(
            (string) !Options::getOptions(OCI::ATTR_PERSISTENT)
            ? oci_connect($user, $password, $dsn, $charset)
            : oci_pconnect($user, $password, $dsn, $charset)
        );
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return OCIConnection
     * @throws Exception
     */
    public function connect(): OCIConnection
    {
        if (!extension_loaded('oci8')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'interbase',
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
                    static::getHost(),
                    static::getUser(),
                    static::getPassword(),
                    static::getDatabase(),
                    static::getPort(),
                    static::getCharset()
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
        return $this->exec(oci_parse($this->getConnection(), 'SELECT 1 FROM DUAL')) !== false;
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
            if (!Options::getOptions(OCI::ATTR_PERSISTENT)) {
                if (Compare::connection($this->getConnection()) === 'oci') {
                    oci_close($this->getConnection());
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
        return (Compare::connection($this->getConnection()) === 'oci') && $this->getInstance()->getConnected();
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
        return $name;
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
            default => "'" . str_replace("'", "''", (string) $string) . "'",
        };
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
        $fetchCount = (function () use ($params) {
            $this->bindParam(...self::$statementCount);
            return $params();
        });
        $this->queryRows = Validations::isSelect($this->getQueryString()) ? $fetchCount() : 0;
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
        $this->affectedRows = Validations::isSelect($this->getQueryString()) ? 0 : ($this->getAffectedRows() + $params);
    }

    /**
     * A description of the entire PHP function.
     *
     * @return mixed
     */
    public function getStatement(): mixed
    {
        return self::$statement;
    }

    /**
     * Set the statement for the function.
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
     * @param mixed $statement An array containing the parameters to bind.
     * @param mixed $param The prepared statement to bind variables to.
     * @param mixed $value The prepared statement to bind variables to.
     * @return void
     */
    private function internalBindVariable(mixed $statement, mixed $param, mixed $value): void
    {
        if (is_numeric($value) && is_string($value) && str_contains($value, '.')) {
            $floatValue = (float) $value;
            oci_bind_by_name($statement, $param, $floatValue, 8, SQLT_FLT);
        } elseif (is_string($value)) {
            $stringValue = $value;
            oci_bind_by_name($statement, $param, $stringValue, -1);
        } elseif (is_bool($value)) {
            $boolValue = $value;
            oci_bind_by_name($statement, $param, $boolValue, -1, SQLT_BOL);
        } elseif (is_int($value)) {
            $intValue = $value;
            oci_bind_by_name($statement, $param, $intValue, -1, SQLT_INT);
        } elseif (is_array($value)) {
            foreach ($param as $key) {
                oci_bind_by_name($statement, $key, $value[$key]);
            }
        }
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
            for ($index = 0; $index < count($param); $index++) {
                $this->internalBindVariable(
                    $params['sqlStatement'],
                    array_keys($param)[$index],
                    array_values($param)[$index]
                );
            }
            $this->exec($params['sqlStatement']);
            $this->setAffectedRows((int) oci_num_rows($params['sqlStatement']));
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
        for ($i = 0; $i < count($params['sqlArgs']); $i++) {
            $key = key($params['sqlArgs']);
            $this->internalBindVariable($params['sqlStatement'], $key, $params['sqlArgs'][$key]);
            next($params['sqlArgs']);
        }
        $this->exec($params['sqlStatement']);
        $this->setAffectedRows((int) oci_num_rows($params['sqlStatement']));
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
    }

    /**
     * Parses an SQL statement and returns an statement.
     *
     * @param mixed ...$params The parameters for the query function.
     * @return string The statement resulting from the SQL statement.
     */
    private function parse(mixed ...$params): string
    {
        $queryString = Translater::escape(reset($params), Translater::SQL_DIALECT_DOUBLE_QUOTE);
        $this->setQueryString($queryString);
        return $this->getQueryString();
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return static|null
     */
    public function query(mixed ...$params): static|null
    {
        $driver = Compare::connection($this->getConnection());
        $this->setAllMetadata();
        if (!empty($params)) {
            $this->setStatement(oci_parse($this->getConnection(), $this->parse(...$params)));
            $rowCount = $params;
            array_unshift($rowCount, oci_parse($this->getConnection(), $this->parse(...$params)));
            array_unshift($params, $this->getStatement());
            self::$statementCount = array_merge($this->makeArgs($driver, ...$rowCount), ['rowCount' => true]);
            $this->exec($this->getStatement());
            $this->setQueryRows(fn() => count(Statements::internalFetchAllAssoc(
                self::$statementCount['sqlStatement']
            )));
            $this->setQueryColumns((int) oci_num_fields($this->getStatement()));
            $this->setAffectedRows((int) oci_num_rows($this->getStatement()));
        }
        return $this;
    }

    public function prepare(mixed ...$params): static|null
    {
        $driver = Compare::connection($this->getConnection());
        $this->setAllMetadata();
        if (!empty($params)) {
            $this->setStatement(oci_parse($this->getConnection(), $this->parse(...$params)));
            $rowCount = $params;
            array_unshift($rowCount, oci_parse($this->getConnection(), $this->parse(...$params)));
            array_unshift($params, $this->getStatement());
            $bindParams = array_merge($this->makeArgs($driver, ...$params), ['rowCount' => false]);
            self::$statementCount = array_merge($this->makeArgs($driver, ...$rowCount), ['rowCount' => true]);
            $this->bindParam(...$bindParams);
            $this->setQueryRows(fn() => count(Statements::internalFetchAllAssoc(
                self::$statementCount['sqlStatement']
            )));
            $this->setQueryColumns((int) oci_num_fields($this->getStatement()));
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
        $statement = reset($params) ?? $this->getStatement();
        $resultMode = $params[1] ?? OCI_COMMIT_ON_SUCCESS;
        return oci_execute($statement, $resultMode);
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
        $fetch = is_null($fetchStyle) ? Options::getOptions(OCI::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            OCI::FETCH_OBJ,
            OCI::FETCH_INTO,
            OCI::FETCH_CLASS => Statements::internalFetchClass($this->getStatement(), $fetchArgument, $optArgs),
            OCI::FETCH_COLUMN => Statements::internalFetchColumn($this->getStatement(), $fetchArgument),
            OCI::FETCH_ASSOC => Statements::internalFetchAssoc($this->getStatement()),
            OCI::FETCH_NUM => Statements::internalFetchNum($this->getStatement()),
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
        $fetch = is_null($fetchStyle) ? Options::getOptions(OCI::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            OCI::FETCH_OBJ,
            OCI::FETCH_CLASS => Statements::internalFetchAllClass($this->getStatement(), $fetchArgument, $optArgs),
            OCI::FETCH_COLUMN => Statements::internalFetchAllColumn($this->getStatement(), $fetchArgument),
            OCI::FETCH_ASSOC => Statements::internalFetchAllAssoc($this->getStatement()),
            OCI::FETCH_NUM => Statements::internalFetchAllNum($this->getStatement()),
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
        return OCI::getAttribute($name);
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
        OCI::setAttribute($name, $value);
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
        $error = oci_error($inst);
        return @$error['code'];
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
        $error = oci_error($inst);
        return @$error['message'];
    }
}
