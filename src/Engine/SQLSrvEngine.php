<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

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

if (!defined('SQLSRV_FETCH_NUM')) {
    define('SQLSRV_FETCH_NUM', 8);
}
if (!defined('SQLSRV_FETCH_OBJ')) {
    define('SQLSRV_FETCH_OBJ', 9);
}
if (!defined('SQLSRV_FETCH_BOTH')) {
    define('SQLSRV_FETCH_BOTH', 10);
}
if (!defined('SQLSRV_FETCH_INTO')) {
    define('SQLSRV_FETCH_INTO', 11);
}
if (!defined('SQLSRV_FETCH_CLASS')) {
    define('SQLSRV_FETCH_CLASS', 12);
}
if (!defined('SQLSRV_FETCH_ASSOC')) {
    define('SQLSRV_FETCH_ASSOC', 13);
}
if (!defined('SQLSRV_FETCH_COLUMN')) {
    define('SQLSRV_FETCH_COLUMN', 14);
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
 * Dynamic and Static container class for SQLSrvEngine connections.
 *
 * @method static SQLSrvEngine|static setDriver(mixed $value): void
 * @method static SQLSrvEngine|static getDriver($value = null): mixed
 * @method static SQLSrvEngine|static setHost(mixed $value): void
 * @method static SQLSrvEngine|static getHost($value = null): mixed
 * @method static SQLSrvEngine|static setPort(mixed $value): void
 * @method static SQLSrvEngine|static getPort($value = null): mixed
 * @method static SQLSrvEngine|static setUser(mixed $value): void
 * @method static SQLSrvEngine|static getUser($value = null): mixed
 * @method static SQLSrvEngine|static setPassword(mixed $value): void
 * @method static SQLSrvEngine|static getPassword($value = null): mixed
 * @method static SQLSrvEngine|static setDatabase(mixed $value): void
 * @method static SQLSrvEngine|static getDatabase($value = null): mixed
 * @method static SQLSrvEngine|static setOptions(mixed $value): void
 * @method static SQLSrvEngine|static getOptions($value = null): mixed
 * @method static SQLSrvEngine|static setConnected(mixed $value): void
 * @method static SQLSrvEngine|static getConnected($value = null): mixed
 * @method static SQLSrvEngine|static setDsn(mixed $value): void
 * @method static SQLSrvEngine|static getDsn($value = null): mixed
 * @method static SQLSrvEngine|static setAttributes(mixed $value): void
 * @method static SQLSrvEngine|static getAttributes($value = null): mixed
 * @method static SQLSrvEngine|static setCharset(mixed $value): void
 * @method static SQLSrvEngine|static getCharset($value = null): mixed
 * @method static SQLSrvEngine|static setException(mixed $value): void
 * @method static SQLSrvEngine|static getException($value = null): mixed
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
     * @return SQLSrvEngine
     */
    public static function __callStatic(string $name, array $arguments): SQLSrvEngine
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return SQLSrvEngine
     */
    private function preConnect(): SQLSrvEngine
    {
        Options::setOptions((array) $this->getOptions());
        $options = Options::getOptions();
        $this->setOptions($options);
        if ($this->getCharset()) {
            $this->setCharset(((string) $this->getCharset() === 'utf8') ? 'UTF-8' : $this->getCharset());
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
     * @param string $host The host of the database
     * @param string $user The user of the database
     * @param string $password The password of the database
     * @param string $database The name of the database
     * @param mixed $port The port of the database
     * @return SQLSrvEngine
     * @throws Exception
     */
    private function realConnect(
        string $host,
        string $user,
        #[SensitiveParameter] string $password,
        string $database,
        mixed $port
    ): SQLSrvEngine {
        $serverName = vsprintf('%s,%s', [$host, $port]);
        $connectionInfo = ["Database" => $database, "UID" => $user, "PWD" => $password];
        if ($this->getCharset()) {
            $connectionInfo['CharacterSet'] = $this->getCharset();
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
        return (Compare::connection($this->getConnection()) === 'sqlsrv') && $this->getConnected();
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
     * @return array
     */
    private function makeArgs(mixed ...$params): array
    {
        if (array_key_exists(1, $params)) {
            if (is_array($params[1])) {
                $isArgs = false;
                $isArray = true;
                $isMulti = Arrays::isMultidimensional($params[1]);
                $sqlArgs = $params[1];
            } else {
                $isArgs = true;
                $isArray = false;
                $isMulti = false;
                $sqlArgs = Translater::arguments($params[0], array_slice($params, 1));
            }
        }
        return [
            'sqlQuery' => $params[0],
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
        $this->resetMetadata();
        if (!empty($params)) {
            $bindParams = $this->makeArgs(...$params);
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
        return SQLSrv::getAttribute($name);
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
