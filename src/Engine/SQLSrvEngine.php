<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use ReflectionException;
use SensitiveParameter;
use AllowDynamicProperties;
use Exception;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\SQLSrv\SQLSrv;
use GenericDatabase\Engine\SQLSrv\Arguments;
use GenericDatabase\Engine\SQLSrv\Options;
use GenericDatabase\Engine\SQLSrv\Attributes;
use GenericDatabase\Engine\SQLSrv\DSN;
use GenericDatabase\Engine\SQLSrv\Dump;
use GenericDatabase\Engine\SQLSrv\Transaction;
use GenericDatabase\Engine\SQLSrv\Statements;
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
 * Dynamic and Static container class for SQLSrvEngine connections.
 *
 * @method static SQLSrvEngine|void setDriver(mixed $value): void
 * @method static SQLSrvEngine|string getDriver($value = null): string
 * @method static SQLSrvEngine|void setHost(mixed $value): void
 * @method static SQLSrvEngine|string getHost($value = null): string
 * @method static SQLSrvEngine|void setPort(mixed $value): void
 * @method static SQLSrvEngine|int getPort($value = null): int
 * @method static SQLSrvEngine|void setUser(mixed $value): void
 * @method static SQLSrvEngine|string getUser($value = null): string
 * @method static SQLSrvEngine|void setPassword(mixed $value): void
 * @method static SQLSrvEngine|string getPassword($value = null): string
 * @method static SQLSrvEngine|void setDatabase(mixed $value): void
 * @method static SQLSrvEngine|string getDatabase($value = null): string
 * @method static SQLSrvEngine|void setOptions(mixed $value): void
 * @method static SQLSrvEngine|array|null getOptions($value = null): array|null
 * @method static SQLSrvEngine|static setConnected(mixed $value): void
 * @method static SQLSrvEngine|mixed getConnected($value = null): mixed
 * @method static SQLSrvEngine|void setDsn(mixed $value): void
 * @method static SQLSrvEngine|mixed getDsn($value = null): mixed
 * @method static SQLSrvEngine|void setAttributes(mixed $value): void
 * @method static SQLSrvEngine|mixed getAttributes($value = null): mixed
 * @method static SQLSrvEngine|void setCharset(mixed $value): void
 * @method static SQLSrvEngine|string getCharset($value = null): string
 * @method static SQLSrvEngine|void setException(mixed $value): void
 * @method static SQLSrvEngine|mixed getException($value = null): mixed
 */
#[AllowDynamicProperties]
class SQLSrvEngine implements IConnection
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
     * @return SQLSrvEngine|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): SQLSrvEngine|string|int|bool|array|null
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
     * @return SQLSrvEngine
     * @throws ReflectionException
     */
    public static function __callStatic(string $name, array $arguments): SQLSrvEngine
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return SQLSrvEngine
     * @throws ReflectionException
     */
    private function preConnect(): SQLSrvEngine
    {
        Options::setOptions((array) static::getOptions());
        $options = Options::getOptions();
        static::setOptions($options);
        if (static::getCharset()) {
            static::setCharset((static::getCharset() == 'utf8') ? 'UTF-8' : static::getCharset());
        }
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return SQLSrvEngine
     * @throws CustomException
     */
    private function postConnect(): SQLSrvEngine
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the SQLSrvEngine connection.
     *
     * @param mixed $host The host of the database
     * @param mixed $user The user of the database
     * @param mixed $password The password of the database
     * @param mixed $database The name of the database
     * @param mixed $port The port of the database
     * @return SQLSrvEngine
     * @throws Exception
     */
    private function realConnect(
        mixed $host,
        mixed $user,
        #[SensitiveParameter] mixed $password,
        mixed $database,
        mixed $port
    ): SQLSrvEngine {
        $serverName = vsprintf('%s,%s', [$host, $port]);
        $connectionInfo = ["Database" => $database, "UID" => $user, "PWD" => $password];
        if (static::getCharset()) {
            $connectionInfo['CharacterSet'] = static::getCharset();
        }
        if (Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)) {
            $connectionInfo['LoginTimeout'] = Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT);
        }
        $this->setConnection(sqlsrv_connect($serverName, $connectionInfo));
        return $this;
    }

    /**
     * This method is used to establish a database connection and set the connection instance
     *
     * @return SQLSrvEngine
     * @throws Exception
     */
    public function connect(): SQLSrvEngine
    {
        try {
            $this
                ->preConnect()
                ->setInstance($this)
                ->setDsn((string)$this->parseDsn())
                ->realConnect(
                    static::getHost(),
                    static::getUser(),
                    static::getPassword(),
                    static::getDatabase(),
                    static::getPort()
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
            static::setConnected(false);
            if (!Options::getOptions(SQLSrv::ATTR_PERSISTENT)) {
                if (Compare::connection($this->getConnection()) === 'sqlsrv') {
                    sqlsrv_close($this->getConnection());
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
        return (Compare::connection($this->getConnection()) === 'sqlsrv') && $this->getInstance()->getConnected();
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
     * Binds variables to a prepared statement with specified types.
     * This method binds variables to a prepared statement based on their types,
     * allowing for more precise parameter binding.
     *
     * @param mixed $data The prepared statement to bind variables to.
     * @return resource|false The prepared statement with bound variables.
     */
    private function internalBindVariable(mixed $data)
    {
        return self::$statement = sqlsrv_prepare(
            $this->getConnection(),
            $this->queryString,
            $data,
            ['Scrollable' => Validations::isSelect($this->queryString) ? SQLSRV_CURSOR_STATIC : SQLSRV_CURSOR_FORWARD]
        );
    }

    /**
     * Binds an array multiple parameter to a variable in the SQL statement.
     *
     * @param mixed $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArrayMulti(mixed ...$params): void
    {
        $referenceParams = [];
        $preparedParams = [];
        for ($i = 0; $i < count($params['sqlArgs'][0]); $i++) {
            if (!array_key_exists($i, $referenceParams)) {
                $referenceParams[$i] = null;
            }
            $preparedParams[] = [&$referenceParams[$i], SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8')];
        }
        self::$statement = $this->internalBindVariable($preparedParams);
        foreach (Arrays::arrayValuesRecursive($params['sqlArgs']) as $row) {
            for ($i = 0; $i < count($params['sqlArgs'][0]); $i++) {
                $referenceParams[$i] = $row[$i];
            }
            $this->exec(self::$statement);
            $this->affectedRows += (int) (sqlsrv_rows_affected(self::$statement) === -1
                ? 0
                : sqlsrv_rows_affected(self::$statement));
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
        $referenceParams = [];
        $preparedParams = [];
        for ($i = 0; $i < count($params['sqlArgs']); $i++) {
            $referenceParams[$i] = array_values($params['sqlArgs'])[$i];
            $preparedParams[] = [&$referenceParams[$i], SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8')];
        }
        self::$statement = $this->internalBindVariable($preparedParams);
        $this->exec(self::$statement);
        $this->affectedRows += (int) (sqlsrv_rows_affected(self::$statement) === -1
            ? 0
            : sqlsrv_rows_affected(self::$statement));
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
            Translater::escape($params[0], Translater::SQL_DIALECT_DQUOTE)
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
            self::$statement = sqlsrv_query(
                $this->getConnection(),
                $this->parse(...$params),
                [],
                ['Scrollable' => Validations::isSelect($this->queryString)
                    ? SQLSRV_CURSOR_STATIC
                    : SQLSRV_CURSOR_FORWARD]
            );
            $this->queryRows = (int) sqlsrv_num_rows(self::$statement);
            $this->queryColumns = (int) sqlsrv_num_fields(self::$statement);
            $this->affectedRows += (int) (sqlsrv_rows_affected(self::$statement) === -1
                ? 0
                : sqlsrv_rows_affected(self::$statement));
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
            $bindParams = $this->makeArgs($driver, ...$params);
            $this->parse(...$params);
            (array_key_exists(1, $params)) ? $this->bindParam(...$bindParams) : $this->query(...$params);
            $this->queryRows = (int) sqlsrv_num_rows(self::$statement);
            $this->queryColumns = (int) sqlsrv_num_fields(self::$statement);
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
        $statement = $params[0] ?? self::$statement;
        sqlsrv_execute($statement);
        return self::$statement = $statement;
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
        $fetch = is_null($fetchStyle) ? Options::getOptions(SQLSrv::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            SQLSrv::FETCH_OBJ,
            SQLSrv::FETCH_INTO,
            SQLSrv::FETCH_CLASS => Statements::internalFetchClassOrObject(self::$statement, $fetchArgument, $optArgs),
            SQLSrv::FETCH_COLUMN => Statements::internalFetchColumn(self::$statement, $fetchArgument),
            SQLSrv::FETCH_ASSOC => Statements::internalFetchAssoc(self::$statement),
            SQLSrv::FETCH_NUM => Statements::internalFetchNum(self::$statement),
            SQLSrv::FETCH_BOTH => Statements::internalFetchBoth(self::$statement),
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
        $fetch = is_null($fetchStyle) ? Options::getOptions(SQLSrv::ATTR_DEFAULT_FETCH_MODE) : $fetchStyle;
        return match ($fetch) {
            SQLSrv::FETCH_OBJ,
            SQLSrv::FETCH_CLASS =>
            Statements::internalFetchAllClassOrObjects(self::$statement, $fetchArgument, $optArgs),
            SQLSrv::FETCH_COLUMN => Statements::internalFetchAllColumn(self::$statement, $fetchArgument),
            SQLSrv::FETCH_ASSOC => Statements::internalFetchAllAssoc(self::$statement),
            SQLSrv::FETCH_NUM => Statements::internalFetchAllNum(self::$statement),
            SQLSrv::FETCH_BOTH => Statements::internalFetchAllBoth(self::$statement),
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
        return SQLSrv::getAttribute($name);
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
        SQLSrv::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return array|null
     */
    public function errorCode(mixed $inst = null): ?array
    {
        return sqlsrv_errors($inst);
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return array|null
     */
    public function errorInfo(mixed $inst = null): ?array
    {
        return sqlsrv_errors($inst);
    }
}
