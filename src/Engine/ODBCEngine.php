<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use ReflectionException;
use SensitiveParameter;
use AllowDynamicProperties;
use Exception;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\ODBC\ODBC;
use GenericDatabase\Engine\ODBC\Arguments;
use GenericDatabase\Engine\ODBC\Options;
use GenericDatabase\Engine\ODBC\Attributes;
use GenericDatabase\Engine\ODBC\DSN;
use GenericDatabase\Engine\ODBC\Dump;
use GenericDatabase\Engine\ODBC\Transaction;
use GenericDatabase\Engine\ODBC\Statements;
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

/**
 * Dynamic and Static container class for ODBCEngine connections.
 *
 * @method static ODBCEngine|void setDriver(mixed $value): void
 * @method static ODBCEngine|string getDriver($value = null): string
 * @method static ODBCEngine|void setHost(mixed $value): void
 * @method static ODBCEngine|string getHost($value = null): string
 * @method static ODBCEngine|void setPort(mixed $value): void
 * @method static ODBCEngine|int getPort($value = null): int
 * @method static ODBCEngine|void setUser(mixed $value): void
 * @method static ODBCEngine|string getUser($value = null): string
 * @method static ODBCEngine|void setPassword(mixed $value): void
 * @method static ODBCEngine|string getPassword($value = null): string
 * @method static ODBCEngine|void setDatabase(mixed $value): void
 * @method static ODBCEngine|string getDatabase($value = null): string
 * @method static ODBCEngine|void setOptions(mixed $value): void
 * @method static ODBCEngine|array|null getOptions($value = null): array|null
 * @method static ODBCEngine|static setConnected(mixed $value): void
 * @method static ODBCEngine|mixed getConnected($value = null): mixed
 * @method static ODBCEngine|void setDsn(mixed $value): void
 * @method static ODBCEngine|mixed getDsn($value = null): mixed
 * @method static ODBCEngine|void setAttributes(mixed $value): void
 * @method static ODBCEngine|mixed getAttributes($value = null): mixed
 * @method static ODBCEngine|void setCharset(mixed $value): void
 * @method static ODBCEngine|string getCharset($value = null): string
 * @method static ODBCEngine|void setException(mixed $value): void
 * @method static ODBCEngine|mixed getException($value = null): mixed
 */
#[AllowDynamicProperties]
class ODBCEngine implements IConnection
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
     * Instance of the Statement of the database
     * @var mixed $statementResult = null
     */
    private static mixed $statementResult = null;

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
     * @return ODBCEngine|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): ODBCEngine|string|int|bool|array|null
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
     * @return ODBCEngine
     */
    public static function __callStatic(string $name, array $arguments): ODBCEngine
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return ODBCEngine
     * @throws CustomException
     */
    private function preConnect(): ODBCEngine
    {
        Options::setOptions((array) static::getOptions());
        $options = Options::getOptions();
        static::setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return ODBCEngine
     * @throws CustomException
     */
    private function postConnect(): ODBCEngine
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the ODBC connection.
     *
     * @param string $dsn The Data source name of the connection
     * @param ?string $user = null The user of the database
     * @param ?string $password = null The password of the database
     * @return ODBCEngine
     * @throws Exception
     */
    private function realConnect(
        string $dsn,
        string $user = null,
        #[SensitiveParameter] string $password = null,
        int $options = null
    ): ODBCEngine {
        $this->setConnection(
            (string) !Options::getOptions(ODBC::ATTR_PERSISTENT)
                ? odbc_connect($dsn, (string) $user, (string) $password, $options)
                : odbc_pconnect($dsn, (string) $user, (string) $password, $options)
        );
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return ODBCEngine
     * @throws Exception
     */
    public function connect(): ODBCEngine
    {
        try {
            $this
                ->preConnect()
                ->setInstance($this)
                ->realConnect(
                    $this->parseDsn(),
                    static::getUser(),
                    static::getPassword(),
                    0
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
        $query = 'SELECT 1';
        if (static::getDriver() == 'oci') {
            $query .= ' FROM DUAL';
        } elseif (static::getDriver() == 'ibase' || static::getDriver() == 'firebird') {
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
            static::setConnected(false);
            if (!$this->getAttribute(ODBC::ATTR_PERSISTENT)) {
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
        return ($this->getConnection() !== null) && $this->getInstance()->getConnected();
    }

    /**
     * This method is responsible for parsing the DSN from DSN class.
     *
     * @return string|Exception
     * @throws Exception
     */
    private function parseDsn(): string|Exception
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
     * @param mixed $data The prepared statement to bind variables to.
     * @return mixed The prepared statement with bound variables.
     */
    private function internalBindVariable(mixed $data): mixed
    {
        $data = array_values($data);
        foreach ($data as $i => $v) {
            switch (gettype($v)) {
                case 'boolean':
                case 'integer':
                    $data[$i] = (int) $v;
                    break;
                case 'string':
                    $data[$i] = (string) $v;
                    break;
                case 'array':
                    $data[$i] = implode(',', $v);
                    break;
                case 'object':
                    $data[$i] = serialize($v);
                    break;
                case 'resource':
                    if (is_resource($v) && get_resource_type($v) === 'stream') {
                        $data[$i] = stream_get_contents($v);
                    } else {
                        $data[$i] = serialize($v);
                    }
                    break;
                default:
                    $data[$i] = $v;
            }
        }
        return $data;
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
            $referenceParams = array_values($param);
            (!$params['rowCount'])
                ? self::$statement = $this->exec($params['sqlStatement'], $referenceParams)
                : self::$statementResult = $this->exec($params['sqlStatement'], $referenceParams);
            $this->affectedRows += odbc_num_rows(self::$statement);
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
        $referenceParams = array_values($params['sqlArgs']);
        $this->exec($params['sqlStatement'], $referenceParams);
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
        $driver = static::getDriver();
        $dialectQuote = match ($driver) {
            'mysql' => Translater::SQL_DIALECT_BTICK,
            'pgsql', 'sqlsrv', 'oci', 'firebird' => Translater::SQL_DIALECT_DQUOTE,
            default => Translater::SQL_DIALECT_DQUOTE,
        };
        $this->queryString = Translater::binding(
            Translater::escape(reset($params), $dialectQuote),
            Translater::BIND_QUESTION_MARK
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
            self::$statement = odbc_exec($this->getConnection(), $this->parse(...$params));
            $rowCount = $params;
            self::$statementResult = odbc_prepare($this->getConnection(), $this->parse(...$params));
            array_unshift($rowCount, self::$statementResult);
            array_unshift($params, self::$statement);
            self::$statementCount = array_merge($this->makeArgs('', ...$rowCount), ['rowCount' => true]);
            $this->queryRows = $this->queryRows();
            $this->queryColumns = odbc_num_fields(self::$statement);
            $this->affectedRows += odbc_num_rows(self::$statement);
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
            self::$statement = odbc_prepare($this->getConnection(), $this->parse(...$params));
            $rowCount = $params;
            array_unshift($rowCount, odbc_prepare($this->getConnection(), $this->parse(...$params)));
            array_unshift($params, self::$statement);
            $bindParams = array_merge($this->makeArgs('', ...$params), ['rowCount' => false]);
            self::$statementCount = array_merge($this->makeArgs('', ...$rowCount), ['rowCount' => true]);
            $this->bindParam(...$bindParams);
            $this->queryRows = $this->queryRows();
            $this->queryColumns = odbc_num_fields(self::$statement);
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
        $data = $params[1] ?? false;
        $data = $this->internalBindVariable($data);
        return odbc_execute($statement, $data);
    }

    /**
     * Fetches the next row from the statement and returns it as an array.
     *
     * @param int $fetchStyle The fetch style.
     * @param mixed $fetchArgument From the Fetch Into or Fetch Class.
     * @param mixed $optArgs From the Fetch Into or Fetch Class.
     * @return mixed The next row from the statement as an array, or false if there are no more rows.
     * @throws ReflectionException
     */
    public function fetch(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $fetch = is_null($fetchStyle) ? Options::getOptions(ODBC::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            ODBC::FETCH_OBJ,
            ODBC::FETCH_INTO,
            ODBC::FETCH_CLASS => Statements::internalFetchClassOrObject(self::$statement, $fetchArgument, $optArgs),
            ODBC::FETCH_COLUMN => Statements::internalFetchColumn(self::$statement, $fetchArgument),
            ODBC::FETCH_ASSOC => Statements::internalFetchAssoc(self::$statement),
            ODBC::FETCH_NUM => Statements::internalFetchNum(self::$statement),
            ODBC::FETCH_BOTH => Statements::internalFetchBoth(self::$statement),
            default => Statements::internalFetchBoth(self::$statement),
        };
    }

    /**
     * Fetches all rows from the statement and returns them as an array.
     *
     * @param int $fetchStyle The fetch style.
     * @param mixed $fetchArgument From the Fetch Into or Fetch Class.
     * @param mixed $optArgs From the Fetch Into or Fetch Class.
     * @return mixed The next row from the statement as an array, or false if there are no more rows.
     * @throws ReflectionException
     */
    public function fetchAll(int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $fetch = is_null($fetchStyle) ? Options::getOptions(ODBC::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            ODBC::FETCH_OBJ,
            ODBC::FETCH_CLASS => Statements::internalFetchAllClassOrObjects(self::$statement, $fetchArgument, $optArgs),
            ODBC::FETCH_COLUMN => Statements::internalFetchAllColumn(self::$statement, $fetchArgument),
            ODBC::FETCH_ASSOC => Statements::internalFetchAllAssoc(self::$statement),
            ODBC::FETCH_NUM => Statements::internalFetchAllNum(self::$statement),
            ODBC::FETCH_BOTH => Statements::internalFetchAllBoth(self::$statement),
            default => Statements::internalFetchAllBoth(self::$statement),
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
        return ODBC::getAttribute($name);
    }

    /**
     * This function sets an attribute from the database.
     *
     * @param mixed $name The attribute name
     * @param mixed $value The attribute value
     * @return void
     * @throws ReflectionException
     */
    public function setAttribute(mixed $name, mixed $value): void
    {
        ODBC::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return int|bool
     */
    public function errorCode(mixed $inst = null): int|bool
    {
        return odbc_error($this->getConnection()) ?: (int) $inst;
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return array|bool
     */
    public function errorInfo(mixed $inst = null): array|bool
    {
        $errorCode = $this->errorCode() || $inst;
        $result = false;
        if ($errorCode) {
            $result = [
                'code' => $this->errorCode(),
                'message' => odbc_errormsg($this->getConnection()),
            ];
        }
        return $result;
    }
}
