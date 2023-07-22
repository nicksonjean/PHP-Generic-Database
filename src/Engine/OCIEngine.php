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
use GenericDatabase\Helpers\Types;
use GenericDatabase\Traits\Setter;
use GenericDatabase\Traits\Getter;
use GenericDatabase\Traits\Cleaner;
use GenericDatabase\Traits\Singleton;

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
    public function bindValue($param, $value)
    {
        return $this->bindParam($param, $value);
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param mixed $param The name of the parameter or an associative array of parameters and values.
     * @param mixed $value A variable that will be bound to the parameter.
     * @return mixed The value of the variable bound to the parameter.
     */
    public function bindParam($param, &$value)
    {
        Errors::turnOff();
        if (is_array($param)) {
            $this->params = [];
            foreach ($param as $key => $val) {
                oci_bind_by_name($this->statement, $key, $val);
                $this->params[$key] = $val;
            }
        } else {
            $ociType = SQLT_CHR;
            if (is_float($value)) {
                $ociType = SQLT_FLT;
            } elseif (is_int($value)) {
                $ociType = SQLT_INT;
            } elseif (is_bool($value)) {
                $ociType = SQLT_BOL;
            }
            oci_bind_by_name($this->statement, $param, $value, -1, $ociType);
            $this->params = [$param => $value];
        }
        Errors::turnOn();
        $this->rows = Types::false2Null($this->rowCount($this->statement, false));
        return $value;
    }

    /**
     * Returns the number of rows returned by an OCI statement.
     *
     * @param mixed $stmt The OCI statement (optional).
     * @param bool|null $useQuery Defines whether to use the internal class statement (optional).
     * @return int|false The number of rows returned by the statement or false in case of an error.
     */
    public function rowCount(mixed $stmt = null, ?bool $useQuery = true): int|false
    {
        Errors::turnOff();
        if ($useQuery) {
            $stmt = $this->parse($this->query);
        }
        oci_execute($stmt);
        $output = [];
        $result = oci_fetch_all($stmt, $output);
        Errors::turnOn();
        return $result;
    }

    /**
     * Returns the number of columns in an OCI statement result.
     *
     * @return int|false The number of columns in the result or false in case of an error.
     */
    public function columnCount(): int|false
    {
        return oci_num_fields($this->statement);
    }

    /**
     * Parses an SQL statement and returns an OCI statement.
     *
     * @param mixed ...$params The parameters for the oci_parse() function.
     * @return mixed The OCI statement resulting from the SQL statement.
     */
    private function parse(mixed ...$params): mixed
    {
        $this->query = $params[0];
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
        $this->rows = null;
        $this->statement = $this->parse(...$params);
        return $this->run(...$params);
    }

    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return static|null
     */
    public function prepare(mixed ...$params): static|null
    {
        $this->query = $params[0];
        $this->rows = null;
        $this->statement = $this->parse($this->query);
        if (count($params) > 1) {
            $param = $params[1];
            $value = null;
            if (count($params) > 2) {
                $value = $params[2];
            }
            $this->bindValue($param, $value);
        }
        return $this->run(...$params);
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return static|null
     * @throws GenericException
     */
    private function run(mixed ...$params): static|null
    {
        if ($this->statement) {
            $this->exec(...$params);
            $err = $this->errorInfo($this->statement);
            if ($err) {
                throw new GenericException();
            } elseif (is_resource($this->statement)) {
                $count = $this->rowCount();
                if ($count > 0) {
                    $this->rows = Types::false2Null($count);
                }
                $this->columnCount() ? $this->statement : null;
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
     * @return bool
     */
    public function exec(mixed ...$params): bool
    {
        $resultMode = isset($params[0]) ? (int) $params[0] : OCI_DEFAULT;
        return oci_execute($this->statement, $resultMode);
    }

    /**
     * Fetches the next row from the OCI statement and returns it as an array.
     *
     * @param int $fetchStyle The fetch style (optional). Default is OCI_BOTH.
     * @return array|false The next row from the statement as an array, or false if there are no more rows.
     */
    public function fetch($fetchStyle = OCI_BOTH)
    {
        return oci_fetch_array($this->statement, $fetchStyle | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
    }

    /**
     * Fetches all rows from the OCI statement and returns them as an array.
     *
     * @param int $fetchStyle The fetch style (optional). Default is OCI_FETCHSTATEMENT_BY_ROW.
     * @return array An array containing all rows from the statement.
     */
    public function fetchAll($fetchStyle = OCI_FETCHSTATEMENT_BY_ROW)
    {
        $result = [];
        oci_fetch_all(
            $this->statement,
            $result,
            0,
            -1,
            $fetchStyle | OCI_RETURN_NULLS | OCI_RETURN_LOBS
        );
        return $result;
    }

    /**
     * Fetches a single column from the next row of the OCI statement.
     *
     * @param int $columnIndex The index of the column to fetch (optional). Default is 0.
     * @return mixed The value of the specified column from the next row, or false if there are no more rows.
     */
    public function fetchColumn($columnIndex = 0)
    {
        $row = oci_fetch_array($this->statement, OCI_NUM | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
        return $row[$columnIndex] ?? null;
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
