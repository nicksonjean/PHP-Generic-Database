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
     * @throws GenericException
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
        return 0;
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
     * @return int|false The number of affected rows
     */
    public function queriedRows(): int|false
    {
        return (int) sqlsrv_num_rows($this->statement);
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
     * Returns the number of columns in an statement result.
     *
     * @return int|false The number of columns in the result or false in case of an error.
     */
    public function columnCount(): int|false
    {
        return sqlsrv_num_fields($this->statement);
    }

    /**
     * Binds variables to a prepared statement with specified types.
     * This method binds variables to a prepared statement based on their types,
     * allowing for more precise parameter binding.
     *
     * @param mixed $data The prepared statement to bind variables to.
     * @return mixed The prepared statement with bound variables.
     */
    private function internalBindVariable(mixed $data)
    {
        return $this->statement = sqlsrv_prepare(
            $this->getConnection(),
            $this->query,
            $data,
            ['Scrollable' => Regex::isSelect($this->query) ? SQLSRV_CURSOR_STATIC : SQLSRV_CURSOR_FORWARD]
        );
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
            $referenceParams = [];
            $preparedParams = [];
            for ($i = 0; $i < count($this->params[0]); $i++) {
                if (!array_key_exists($i, $referenceParams)) {
                    $referenceParams[$i] = null;
                }
                $preparedParams[] = [&$referenceParams[$i], SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8')];
            }
            $this->statement = $this->internalBindVariable($preparedParams);
            foreach (Arrays::arrayValuesRecursive($this->params) as $row) {
                for ($i = 0; $i < count($this->params[0]); $i++) {
                    $referenceParams[$i] = $row[$i];
                }
                $this->exec($this->statement);
                $this->affectedRows += (int) (sqlsrv_rows_affected($this->statement) === -1
                    ? 0
                    : sqlsrv_rows_affected($this->statement));
                $this->queriedRows += $this->queriedRows();
            }
        } else {
            $referenceParams = [];
            $preparedParams = [];
            for ($i = 0; $i < count($this->params); $i++) {
                $referenceParams[$i] = array_values($this->params)[$i];
                $preparedParams[] = [&$referenceParams[$i], SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8')];
            }
            $this->statement = $this->internalBindVariable($preparedParams);
            $this->exec($this->statement);
            $this->affectedRows += (int) (sqlsrv_rows_affected($this->statement) === -1
                ? 0
                : sqlsrv_rows_affected($this->statement));
            $this->queriedRows += $this->queriedRows();
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
        $referenceParams = [];
        $preparedParams = [];
        for ($i = 0; $i < count($this->params); $i++) {
            $referenceParams[$i] = array_values($this->params)[$i];
            $preparedParams[] = [&$referenceParams[$i], SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8')];
        }
        $this->statement = $this->internalBindVariable($preparedParams);
        $this->exec($this->statement);
        $this->affectedRows += (int) (sqlsrv_rows_affected($this->statement) === -1
            ? 0
            : sqlsrv_rows_affected($this->statement));
        $this->queriedRows += $this->queriedRows();
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
                $isMulti = Arrays::isMultidimensional($params[1]) ? true : false;
                $sqlArgs = $params[1];
            } else {
                $isArgs = true;
                $isArray = false;
                $isMulti = false;
                $sqlArgs = Translater::parameters($params[0], array_slice($params, 1));
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
        $this->query = Translater::binding(Translater::escape($params[0], Translater::SQL_DIALECT_DQUOTE));
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
            $this->statement = sqlsrv_query(
                $this->getConnection(),
                $this->parse(...$params),
                [],
                ['Scrollable' => Regex::isSelect($this->query) ? SQLSRV_CURSOR_STATIC : SQLSRV_CURSOR_FORWARD]
            );
            $this->affectedRows += (int) (sqlsrv_rows_affected($this->statement) === -1
                ? 0
                : sqlsrv_rows_affected($this->statement));
            $this->queriedRows += $this->queriedRows();
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
            $bindParams = $this->makeArgs(...$params);
            $this->parse(...$params);
            (array_key_exists(1, $params)) ? $this->bindParam(...$bindParams) : $this->query(...$params);
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
        $statement = $params[0] ?? $this->statement;
        sqlsrv_execute($statement);
        return $this->statement = $statement;
    }

    /**
     * Fetches the next row from the statement and returns it as an array.
     *
     * @param int $fetchStyle The fetch style (optional). Default is SQLSRV_FETCH_BOTH.
     * @return array|false The next row from the statement as an array, or false if there are no more rows.
     */
    public function fetch(
        int $fetchStyle = SQLSRV_FETCH_BOTH,
        mixed $fetchArgument = null,
        mixed $optArgs = null
    ): mixed {
        switch ($fetchStyle) {
            case SQLSRV_FETCH_OBJ:
            case SQLSRV_FETCH_CLASS:
            case FETCH_OBJ:
            case FETCH_CLASS:
                return $this->internalFetchClassOrObject(
                    isset($optArgs) ? $optArgs : '\stdClass',
                    [],
                    $this->statement,
                );
            case SQLSRV_FETCH_INTO:
            case FETCH_INTO:
                return $this->internalFetchClassOrObject(
                    isset($optArgs) ? $optArgs : null,
                    [],
                    $this->statement,
                );
            case SQLSRV_FETCH_COLUMN:
            case FETCH_COLUMN:
                return $this->internalFetchColumn($this->statement, $fetchArgument == null ? 0 : $fetchArgument);
            case SQLSRV_FETCH_ASSOC:
            case FETCH_ASSOC:
                return $this->internalFetchAssoc($this->statement);
            case SQLSRV_FETCH_NUM:
            case FETCH_NUM:
                return $this->internalFetchNum($this->statement);
            case SQLSRV_FETCH_BOTH:
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
        int $fetchStyle = SQLSRV_FETCH_ASSOC,
        mixed $fetchArgument = null,
        mixed $optArgs = null
    ): mixed {
        switch ($fetchStyle) {
            case SQLSRV_FETCH_OBJ:
            case SQLSRV_FETCH_CLASS:
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
            case SQLSRV_FETCH_COLUMN:
            case FETCH_COLUMN:
                return $this->internalFetchAllColumn($this->statement, $fetchArgument == null ? 0 : $fetchArgument);
            case SQLSRV_FETCH_ASSOC:
            case FETCH_ASSOC:
                return $this->internalFetchAllAssoc($this->statement);
            case SQLSRV_FETCH_NUM:
            case FETCH_NUM:
                return $this->internalFetchAllNum($this->statement);
            case SQLSRV_FETCH_BOTH:
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
        return sqlsrv_fetch_array($statement, SQLSRV_FETCH_BOTH);
    }

    protected function internalFetchAssoc($statement = null)
    {
        return sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC);
    }

    protected function internalFetchNum($statement = null)
    {
        return sqlsrv_fetch_array($statement, SQLSRV_FETCH_NUMERIC);
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
