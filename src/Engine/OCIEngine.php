<?php

declare(strict_types=1);

namespace GenericDatabase\Engine;

use SensitiveParameter;
use AllowDynamicProperties;
use Exception;
use GenericDatabase\IConnection;
use GenericDatabase\Engine\OCI\OCI;
use GenericDatabase\Engine\OCI\Arguments;
use GenericDatabase\Engine\OCI\Options;
use GenericDatabase\Engine\OCI\Attributes;
use GenericDatabase\Engine\OCI\DSN;
use GenericDatabase\Engine\OCI\Dump;
use GenericDatabase\Engine\OCI\Transaction;
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

if (!defined('OCI_FETCH_NUM')) {
    define('OCI_FETCH_NUM', 8);
}
if (!defined('OCI_FETCH_OBJ')) {
    define('OCI_FETCH_OBJ', 9);
}
if (!defined('OCI_FETCH_BOTH')) {
    define('OCI_FETCH_BOTH', 10);
}
if (!defined('OCI_FETCH_INTO')) {
    define('OCI_FETCH_INTO', 11);
}
if (!defined('OCI_FETCH_CLASS')) {
    define('OCI_FETCH_CLASS', 12);
}
if (!defined('OCI_FETCH_ASSOC')) {
    define('OCI_FETCH_ASSOC', 13);
}
if (!defined('OCI_FETCH_COLUMN')) {
    define('OCI_FETCH_COLUMN', 14);
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
 * Dynamic and Static container class for OCIEngine connections.
 *
 * @method static OCIEngine|static setDriver(mixed $value): void
 * @method static OCIEngine|static getDriver($value = null): mixed
 * @method static OCIEngine|static setHost(mixed $value): void
 * @method static OCIEngine|static getHost($value = null): mixed
 * @method static OCIEngine|static setPort(mixed $value): void
 * @method static OCIEngine|static getPort($value = null): mixed
 * @method static OCIEngine|static setUser(mixed $value): void
 * @method static OCIEngine|static getUser($value = null): mixed
 * @method static OCIEngine|static setPassword(mixed $value): void
 * @method static OCIEngine|static getPassword($value = null): mixed
 * @method static OCIEngine|static setDatabase(mixed $value): void
 * @method static OCIEngine|static getDatabase($value = null): mixed
 * @method static OCIEngine|static setOptions(mixed $value): void
 * @method static OCIEngine|static getOptions($value = null): mixed
 * @method static OCIEngine|static setConnected(mixed $value): void
 * @method static OCIEngine|static getConnected($value = null): mixed
 * @method static OCIEngine|static setDsn(mixed $value): void
 * @method static OCIEngine|static getDsn($value = null): mixed
 * @method static OCIEngine|static setAttributes(mixed $value): void
 * @method static OCIEngine|static getAttributes($value = null): mixed
 * @method static OCIEngine|static setCharset(mixed $value): void
 * @method static OCIEngine|static getCharset($value = null): mixed
 * @method static OCIEngine|static setException(mixed $value): void
 * @method static OCIEngine|static getException($value = null): mixed
 */
#[AllowDynamicProperties]
class OCIEngine implements IConnection
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
     * @return OCIEngine|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): OCIEngine|string|int|bool|array|null
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
     * @return OCIEngine
     */
    public static function __callStatic(string $name, array $arguments): OCIEngine
    {
        return Arguments::call($name, $arguments);
    }

    /**
     * This method is responsible for prepare the connection options before connect.
     *
     * @return OCIEngine
     */
    private function preConnect(): OCIEngine
    {
        Options::setOptions((array) $this->getOptions());
        $options = Options::getOptions();
        $this->setOptions($options);
        return $this;
    }

    /**
     * This method is responsible for update in date late binding the connection.
     *
     * @return OCIEngine
     * @throws GenericException
     */
    private function postConnect(): OCIEngine
    {
        Options::define();
        Attributes::define();
        return $this;
    }

    /**
     * This method is responsible for creating a new instance of the OCIEngine connection.
     *
     * @param string $host The host of the database
     * @param string $user The user of the database
     * @param string $password The password of the database
     * @param string $database The name of the database
     * @param mixed $port The port of the database
     * @param string $charset The charset of the database
     * @return OCIEngine
     * @throws Exception
     */
    private function realConnect(
        string $host,
        string $user,
        #[SensitiveParameter] string $password,
        string $database,
        mixed $port,
        string $charset
    ): OCIEngine {
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
     * @return OCIEngine
     * @throws Exception
     */
    public function connect(): OCIEngine
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
                    $this->getPort(),
                    (string) $this->getCharset()
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
        $result = $this->query('SELECT 1 FROM DUAL');
        return $this->exec($result) !== false;
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
        return (Compare::connection($this->getConnection()) === 'oci') && $this->getConnected();
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
        if (Regex::isSelect($this->query)) {
            $this->bindParam(...$GLOBALS['rowCount']);
            return count($this->internalFetchAllAssoc($GLOBALS['rowCount']['sqlStatement']));
        }
        return 0;
    }

    /**
     * Returns the number of rows affected by an operation.
     *
     * @return int The number of affected rows
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
        return oci_num_fields($this->statement);
    }

    /**
     * Binds variables to a prepared statement with specified types.
     * This method binds variables to a prepared statement based on their types,
     * allowing for more precise parameter binding.
     *
     * @param mixed $statement An array containing the parameters to bind.
     * @param mixed $param The prepared statement to bind variables to.
     * @param mixed $value The prepared statement to bind variables to.
     * @return mixed The prepared statement with bound variables.
     */
    private function internalBindVariable(mixed $statement, mixed $param, mixed $value): void
    {
        if (is_numeric($value) && is_string($value) && str_contains($value, '.')) {
            $floatValue = (float) $value;
            oci_bind_by_name($statement, $param, $floatValue, 8, SQLT_FLT);
        } elseif (is_string($value)) {
            $stringValue = (string) $value;
            oci_bind_by_name($statement, $param, $stringValue, -1);
        } elseif (is_bool($value)) {
            $boolValue = (bool) $value;
            oci_bind_by_name($statement, $param, $boolValue, -1, SQLT_BOL);
        } elseif (is_int($value)) {
            $intValue = (int) $value;
            oci_bind_by_name($statement, $param, $intValue, -1, SQLT_INT);
        } elseif (is_array($value)) {
            foreach ($param as $key) {
                oci_bind_by_name($statement, $key, $value[$key]);
            }
        }
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
            foreach ($this->params as $key => $param) {
                for ($index = 0; $index < count($param); $index++) {
                    $this->internalBindVariable(
                        $params['sqlStatement'],
                        array_keys($param)[$index],
                        array_values($param)[$index]
                    );
                }
                $this->exec($params['sqlStatement']);
                $this->affectedRows += oci_num_rows($params['sqlStatement']);
            }
        } else {
            foreach ($params['sqlArgs'] as $key => $param) {
                $this->internalBindVariable($params['sqlStatement'], $key, $this->params[$key]);
            }
            $this->exec($params['sqlStatement']);
            $this->affectedRows += oci_num_rows($params['sqlStatement']);
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
        for ($i = 0; $i < count($this->params); $i++) {
            $key = key($this->params);
            $this->internalBindVariable($params['sqlStatement'], $key, $this->params[$key]);
            next($this->params);
        }
        $this->exec($params['sqlStatement']);
        $this->affectedRows += oci_num_rows($params['sqlStatement']);
    }

    /**
     * This function makes an arguments list
     *
     * @param mixed $params Arguments list
     * @return array
     */
    private function makeArgs(mixed ...$params): array
    {
        if (array_key_exists(2, $params)) {
            if (is_array($params[2])) {
                $isArgs = false;
                $isArray = true;
                $isMulti = Arrays::isMultidimensional($params[2]) ? true : false;
                $sqlArgs = $params[2];
            } else {
                $isArgs = true;
                $isArray = false;
                $isMulti = false;
                $sqlArgs = Translater::parameters($params[1], array_slice($params, 2));
            }
        }
        return [
            'sqlStatement' => $params[0],
            'sqlQuery' => $params[1],
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
     * @return int
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
        $this->query = Translater::escape($params[0], Translater::SQL_DIALECT_DQUOTE);
        return oci_parse($this->getConnection(), $this->query);
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
            $this->statement = $this->parse(...$params);
            $rowCount = $params;
            array_unshift($rowCount, $this->parse(...$params));
            array_unshift($params, $this->statement);
            $GLOBALS['rowCount'] = array_merge($this->makeArgs(...$rowCount), ['rowCount' => true]);
            $this->exec($this->statement);
            $this->queriedRows = $this->queriedRows();
            $this->affectedRows += oci_num_rows($this->statement);
        }
        return $this;
    }

    public function prepare(mixed ...$params): static|null
    {
        $this->affectedRows = 0;
        $this->queriedRows = 0;
        if (!empty($params)) {
            $this->statement = $this->parse(...$params);
            $rowCount = $params;
            array_unshift($rowCount, $this->parse(...$params));
            array_unshift($params, $this->statement);
            $bindParams = array_merge($this->makeArgs(...$params), ['rowCount' => false]);
            $GLOBALS['rowCount'] = array_merge($this->makeArgs(...$rowCount), ['rowCount' => true]);
            $this->bindParam(...$bindParams);
            $this->queriedRows = $this->queriedRows();
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
        $statement = $params[0] ?? $this->statement;
        $resultMode = $params[1] ?? OCI_COMMIT_ON_SUCCESS;
        return oci_execute($statement, $resultMode);
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
        int $fetchStyle = OCI_FETCH_BOTH,
        mixed $fetchArgument = null,
        mixed $optArgs = null
    ): mixed {
        switch ($fetchStyle) {
            case OCI_FETCH_OBJ:
            case OCI_FETCH_CLASS:
            case FETCH_OBJ:
            case FETCH_CLASS:
                return $this->internalFetchClassOrObject(
                    isset($optArgs) ? $optArgs : '\stdClass',
                    [],
                    $this->statement,
                );
            case OCI_FETCH_INTO:
            case FETCH_INTO:
                return $this->internalFetchClassOrObject(
                    isset($optArgs) ? $optArgs : null,
                    [],
                    $this->statement,
                );
            case OCI_FETCH_COLUMN:
            case FETCH_COLUMN:
                return $this->internalFetchColumn($this->statement, $fetchArgument == null ? 0 : $fetchArgument);
            case OCI_FETCH_ASSOC:
            case FETCH_ASSOC:
                return $this->internalFetchAssoc($this->statement);
            case OCI_FETCH_NUM:
            case FETCH_NUM:
                return $this->internalFetchNum($this->statement);
            case OCI_FETCH_BOTH:
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
        int $fetchStyle = OCI_FETCH_ASSOC,
        mixed $fetchArgument = null,
        mixed $optArgs = null
    ): mixed {
        switch ($fetchStyle) {
            case OCI_FETCH_OBJ:
            case OCI_FETCH_CLASS:
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
            case OCI_FETCH_COLUMN:
            case FETCH_COLUMN:
                return $this->internalFetchAllColumn($this->statement, $fetchArgument == null ? 0 : $fetchArgument);
            case OCI_FETCH_ASSOC:
            case FETCH_ASSOC:
                return $this->internalFetchAllAssoc($this->statement);
            case OCI_FETCH_NUM:
            case FETCH_NUM:
                return $this->internalFetchAllNum($this->statement);
            case OCI_FETCH_BOTH:
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
        return oci_fetch_array($statement, OCI_BOTH | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
    }

    protected function internalFetchAssoc($statement = null)
    {
        return oci_fetch_assoc($statement);
    }

    protected function internalFetchNum($statement = null)
    {
        return oci_fetch_row($statement);
    }

    protected function internalFetchColumn($statement = null, $columnIndex = 0)
    {
        $row = oci_fetch_array($statement, OCI_NUM | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
        return $row[$columnIndex] ?? null;
    }

    protected function internalFetchAllAssoc($statement = null)
    {
        $result = [];
        oci_fetch_all(
            $statement,
            $result,
            0,
            -1,
            OCI_FETCHSTATEMENT_BY_ROW | OCI_RETURN_NULLS | OCI_RETURN_LOBS
        );
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
        return OCI::getAttribute($name);
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
        OCI::setAttribute($name, $value);
    }

    /**
     * This function returns an SQLSTATE code for the last operation executed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorCode(mixed $inst = null): mixed
    {
        $error = oci_error($inst);
        return @$error['code'];
    }

    /**
     * This function returns an array containing error information about the last operation performed by the database.
     *
     * @param mixed $inst = null Resource name, table or view
     * @return mixed
     */
    public function errorInfo(mixed $inst = null): mixed
    {
        $error = oci_error($inst);
        return @$error['message'];
    }
}
