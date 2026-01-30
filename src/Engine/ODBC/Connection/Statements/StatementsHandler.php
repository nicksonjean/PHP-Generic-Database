<?php

namespace GenericDatabase\Engine\ODBC\Connection\Statements;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IStatements;
use GenericDatabase\Abstract\AbstractStatements;
use GenericDatabase\Shared\Run;
use GenericDatabase\Generic\Statements\Statement;
use GenericDatabase\Helpers\Parsers\SQL;
use GenericDatabase\Helpers\Validations;
use GenericDatabase\Engine\ODBC\Connection\ODBC;

/**
 * Concrete implementation for PDO database
 */
class StatementsHandler extends AbstractStatements implements IStatements
{
    private function lastInsertIdMySQL(?string $name = null): int|false
    {
        if (!$name) {
            return false;
        }
        $connection = $this->getInstance()->getConnection();
        if (!$connection) {
            return false;
        }
        $filter = "WHERE TABLE_NAME = ? AND COLUMN_KEY = ? AND EXTRA = ?";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = @odbc_prepare($connection, $query);
        if (!$stmt) {
            return false;
        }
        @odbc_execute($stmt, [$name, 'PRI', 'auto_increment']);
        if ($stmt && @odbc_fetch_row($stmt)) {
            $identityColumn = @odbc_result($stmt, "COLUMN_NAME");
            $query = "SELECT MAX($identityColumn) AS LastInsertedID FROM $name";
            $stmt = @odbc_exec($connection, $query);
            if ($stmt && @odbc_fetch_row($stmt)) {
                return (int) @odbc_result($stmt, "LastInsertedID");
            }
        }
        return false;
    }

    private function lastInsertIdPgSQL(?string $name = null): int|false
    {
        if (!$name) {
            return false;
        }
        $connection = $this->getInstance()->getConnection();
        if (!$connection) {
            return false;
        }
        $query = "SELECT
            current_database() as DATABASE_NAME,
            seq.schemaname AS SCHEMA_NAME,
            seq.sequencename AS NAME,
            table_identities.*,
            seq.last_value
        FROM pg_sequences seq
             INNER JOIN pg_namespace nspc ON nspc.nspname = seq.schemaname
             INNER JOIN pg_class s ON s.relname = seq.sequencename AND s.relnamespace = nspc.oid
             LEFT OUTER JOIN (
                 SELECT
                     t.relname AS TABLE_NAME,
                     a.attname AS COLUMN_NAME,
                     d.objid AS OBJID
                 FROM pg_namespace tns
                          JOIN pg_class t ON tns.oid = t.relnamespace AND t.relkind IN ('p', 'r')
                          JOIN pg_index i ON t.oid = i.indrelid AND i.indisprimary
                          JOIN pg_attribute a ON i.indrelid = a.attrelid AND a.attnum = ANY (i.indkey)
                          JOIN pg_depend d ON t.oid = d.refobjid AND d.refobjsubid = a.attnum
             ) table_identities ON table_identities.OBJID = s.oid
        WHERE table_identities.TABLE_NAME = ?
        AND (SELECT current_database()) = ?";
        $stmt = @odbc_prepare($connection, $query);
        if (!$stmt) {
            return false;
        }
        @Run::call('odbc_execute', $stmt, [$name, $this->get('database')]);
        if ($stmt && @odbc_fetch_row($stmt)) {
            $sequenceName = @odbc_result($stmt, "NAME");
            $query = "SELECT currval('$sequenceName') AS last_value";
            $stmt = @odbc_exec($connection, $query);
            if ($stmt && @odbc_fetch_row($stmt)) {
                return (int) @odbc_result($stmt, "last_value");
            }
        }
        return false;
    }

    private function lastInsertIdSQLSrv(?string $name = null): int|false
    {
        $connection = $this->getInstance()->getConnection();
        if (!$connection) {
            return false;
        }
        if (!$name) {
            $query = "SELECT CAST(@@IDENTITY AS BIGINT) AS LastInsertedID";
            $stmt = @odbc_exec($connection, $query);
            if ($stmt && @odbc_fetch_row($stmt)) {
                return (int) @odbc_result($stmt, "LastInsertedID");
            }
            return false;
        }
        $filter = "WHERE TABLE_NAME = ? AND TABLE_SCHEMA = SCHEMA_NAME() AND COLUMNPROPERTY(OBJECT_ID(TABLE_SCHEMA + '.' + TABLE_NAME), COLUMN_NAME, 'IsIdentity') = 1";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = @odbc_prepare($connection, $query);
        if (!$stmt) {
            return false;
        }
        @odbc_execute($stmt, [$name]);
        if ($stmt && @odbc_fetch_row($stmt)) {
            $identityColumn = @odbc_result($stmt, "COLUMN_NAME");
            $query = "SELECT MAX($identityColumn) AS LastInsertedID FROM $name";
            $stmt = @odbc_exec($connection, $query);
            if ($stmt && @odbc_fetch_row($stmt)) {
                return (int) @odbc_result($stmt, "LastInsertedID");
            }
        }
        return false;
    }

    private function lastInsertIdOCI(?string $name = null): int|false
    {
        if ($name !== null) {
            $connection = $this->getInstance()->getConnection();
            if (!$connection) {
                return false;
            }
            $filter = "WHERE OWNER = USER AND identity_column = 'YES' AND TABLE_NAME = ?";
            $query = sprintf("SELECT data_default AS sequence_val, table_name, column_name FROM all_tab_columns %s", $filter);
            $stmt = @odbc_prepare($connection, $query);
            if (!$stmt) {
                return false;
            }
            @odbc_execute($stmt, [$name]);
            if ($stmt && @odbc_fetch_row($stmt)) {
                $sequenceVal = @odbc_result($stmt, "sequence_val");
                $sequenceVal = str_replace('.nextval', '.currval', $sequenceVal);
                $sequenceVal = str_replace('"', '', $sequenceVal);
                $query = "SELECT $sequenceVal FROM DUAL";
                $stmt = @odbc_exec($connection, $query);
                if ($stmt && @odbc_fetch_row($stmt)) {
                    return (int) @odbc_result($stmt, 1);
                }
            }
        }
        return false;
    }

    private function lastInsertIdFirebird(?string $name = null): int|false
    {
        if (!$name) {
            return false;
        }

        $isPhp80 = (PHP_VERSION_ID >= 80000 && PHP_VERSION_ID < 80100);
        if ($isPhp80) {
            return false;
        }

        $connection = $this->getInstance()->getConnection();
        if (!$connection) {
            return false;
        }
        $filter = 'WHERE RDB$RELATION_NAME=? AND RDB$IDENTITY_TYPE=1';
        $query = sprintf('SELECT RDB$FIELD_NAME, RDB$GENERATOR_NAME FROM RDB$RELATION_FIELDS %s', $filter);
        $stmt = @odbc_prepare($connection, $query);
        if (!$stmt) {
            return false;
        }
        @odbc_execute($stmt, [$name]);

        if (!@odbc_fetch_row($stmt)) {
            return false;
        }
        $identityColumn = @odbc_result($stmt, 'RDB$GENERATOR_NAME');

        if (!$identityColumn) {
            return false;
        }

        $query = sprintf('SELECT GEN_ID(%s,0) AS LASTINSERTEDID FROM RDB$DATABASE', $identityColumn);
        $stmt = @odbc_exec($connection, $query);
        if (!$stmt) {
            return false;
        }

        if (!@odbc_fetch_row($stmt)) {
            return false;
        }
        return (int) @odbc_result($stmt, "LastInsertedID");
    }

    private function lastInsertIdSQLite(?string $name): int|false
    {
        if (!$name) {
            return false;
        }
        $connection = $this->getInstance()->getConnection();
        if (!$connection) {
            return false;
        }
        $query = "SELECT seq FROM sqlite_sequence WHERE name = ?";
        $stmt = @odbc_prepare($connection, $query);
        if (!$stmt) {
            return false;
        }
        if (!@odbc_execute($stmt, [$name])) {
            return false;
        }
        if (@odbc_fetch_row($stmt)) {
            return (int) @odbc_result($stmt, "seq");
        }
        return false;
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
        return match ($this->get('driver')) {
            'mysql' => $this->lastInsertIdMySQL($name),
            'pgsql' => $this->lastInsertIdPgSQL($name),
            'sqlsrv' => $this->lastInsertIdSQLSrv($name),
            'oci' => $this->lastInsertIdOCI($name),
            'firebird' => $this->lastInsertIdFirebird($name),
            'sqlite' => $this->lastInsertIdSQLite($name),
            default => false,
        };
    }

    /**
     * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
     *
     * @param mixed $params Content to be quoted
     * @return string|int
     */
    public function quote(mixed ...$params): string|int
    {
        $string = reset($params);
        return match (true) {
            is_int($string) => $string,
            is_float($string) => "'" . str_replace(',', '.', strval($string)) . "'",
            is_bool($string) => $string ? '1' : '0',
            is_null($string) => 'NULL',
            default => "'" . str_replace("'", "''", (string) $string) . "'",
        };
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param object $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    public function bindParam(object $params): void
    {
        $this->setQueryParameters($params->query->arguments);
        if ($params->by->array) {
            $this->internalBindParamArray($params);
        } else {
            $this->internalBindParamArgs($params);
        }
        $this->setQueryColumns(odbc_num_fields($params->statement->object));
    }

    /**
     * Binds an array parameter to a variable in the SQL statement.
     *
     * @param object $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArray(object $params): void
    {
        if ($params->is->array->multi) {
            $this->internalBindParamArrayMulti($params);
        } else {
            $this->internalBindParamArraySingle($params);
        }
    }

    /**
     * Binds an array multiple parameter to a variable in the SQL statement.
     *
     * @param object $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArrayMulti(object $params): void
    {
        $affectedRows = 0;
        foreach ($params->query->arguments as $argument) {
            $this->internalBindVariable($argument);
            if ($this->exec($params->statement->object, array_values($argument))) {
                if ($this->getQueryColumns() === 0) {
                    $affectedRows++;
                    $this->setAffectedRows($affectedRows);
                }
            }
        }
    }

    /**
     * Binds an array single parameter to a variable in the SQL statement.
     *
     * @param object $params The name of the parameter or an array of parameters and values.
     * @return void
     */
    private function internalBindParamArraySingle(object $params): void
    {
        $this->internalBindParamArgs($params);
    }

    /**
     * Builds the ordered array of values for odbc_execute from named or positional parameters.
     * When arguments are named (associative array with :placeholder keys), values are ordered
     * by the order of placeholders in the original SQL so they match the ? positions.
     *
     * @param string $queryString Original SQL with :placeholders
     * @param array $arguments Named [':key' => value] or positional [value, ...] arguments
     * @return array Values in the order required by odbc_execute
     */
    private function getOrderedExecuteParams(string $queryString, array $arguments): array
    {
        $placeholderOrder = SQL::arguments($queryString, null);
        if (empty($placeholderOrder)) {
            return array_values($arguments);
        }
        $isNamed = !empty($arguments) && is_string(array_key_first($arguments));
        if (!$isNamed) {
            return array_values($arguments);
        }
        $ordered = [];
        foreach ($placeholderOrder as $key) {
            $name = (str_starts_with((string) $key, ':')) ? $key : (':' . $key);
            $ordered[] = $arguments[$name] ?? $arguments[$key] ?? null;
        }
        return $ordered;
    }

    /**
     * Quotes a value for SQL substitution (used when replacing ? in the parsed query).
     */
    private function quoteValueForSubstitution(mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_bool($value) => $value ? '1' : '0',
            is_int($value), is_float($value) => (string) $value,
            is_string($value) => "'" . str_replace("'", "''", $value) . "'",
            default => "'" . str_replace("'", "''", (string) $value) . "'",
        };
    }

    /**
     * Executes a raw query (no params) via odbc_exec - same path as ComplexPrepare substitution.
     * Parse + odbc_exec + fetch + setStatement so fetchAll() returns results.
     *
     * @param string $query Raw SQL string
     * @return bool True if execution succeeded and results were set
     */
    private function executeRawQuery(string $query): bool
    {
        $connection = $this->getInstance()->getConnection();
        if (!$connection) {
            return false;
        }

        $this->setAllMetadata();
        $parsedQuery = $this->parse($query);
        $this->setQueryString($parsedQuery);

        $statement = @odbc_exec($connection, $parsedQuery);
        if (!$statement) {
            return false;
        }

        $numFields = odbc_num_fields($statement);
        $this->setQueryColumns($numFields);

        if ($numFields > 0) {
            $results = [];
            while ($row = odbc_fetch_array($statement, 0)) {
                $results[] = $row;
            }
            $this->setStatement(['results' => $results]);
            $this->setQueryRows(count($results));
            if (function_exists('odbc_free_result')) {
                odbc_free_result($statement);
            }
        } else {
            $this->setAffectedRows(odbc_num_rows($statement));
            if (function_exists('odbc_free_result')) {
                odbc_free_result($statement);
            }
        }
        return true;
    }

    /**
     * Substitutes named placeholders in the original SQL with their values and runs odbc_exec.
     * Uses parsed query (quoted identifiers + ?) then substitutes ? with values so metadata stores the final query.
     *
     * @param string $queryString Original SQL with :placeholders
     * @param array $arguments Named [':key' => value] arguments
     * @return bool True if execution succeeded and results were set
     */
    private function executeWithSubstitutedParams(string $queryString, array $arguments): bool
    {
        $connection = $this->getInstance()->getConnection();
        if (!$connection) {
            return false;
        }

        $parsedQuery = $this->parse($queryString);
        $orderedValues = $this->getOrderedExecuteParams($queryString, $arguments);

        $processedQuery = $parsedQuery;
        foreach ($orderedValues as $value) {
            $processedValue = $this->quoteValueForSubstitution($value);
            $processedQuery = preg_replace('/\?/', $processedValue, $processedQuery, 1);
        }

        $this->setQueryString($processedQuery);

        $statement = @odbc_exec($connection, $processedQuery);
        if (!$statement) {
            return false;
        }

        $numFields = odbc_num_fields($statement);
        $this->setQueryColumns($numFields);

        if ($numFields > 0) {
            $results = [];
            while ($row = odbc_fetch_array($statement, 0)) {
                $results[] = $row;
            }
            $this->setStatement(['results' => $results]);
            $this->setQueryRows(count($results));
            if (function_exists('odbc_free_result')) {
                odbc_free_result($statement);
            }
        } else {
            $this->setAffectedRows(odbc_num_rows($statement));
            if (function_exists('odbc_free_result')) {
                odbc_free_result($statement);
            }
        }
        return true;
    }

    /**
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param object $params The name of the parameter or an args of parameters and values.
     * @return void
     */
    private function internalBindParamArgs(object $params): void
    {
        $this->internalBindVariable($params->query->arguments);
        $executeParams = $this->getOrderedExecuteParams(
            $params->query->string ?? '',
            $params->query->arguments
        );

        $execSucceeded = $this->exec($params->statement->object, $executeParams);

        if ($execSucceeded) {
            if (odbc_num_fields($params->statement->object) > 0) {
                $results = [];
                while ($row = odbc_fetch_array($params->statement->object, 0)) {
                    $results[] = $row;
                }
                $this->setStatement(['results' => $results]);
                $this->setQueryRows(count($results));
            } else {
                $this->setAffectedRows(odbc_num_rows($params->statement->object));
            }
            return;
        }

        // odbc_execute failed (common with SQLite ODBC and multiple params): run query with substituted values
        $args = $params->query->arguments ?? [];
        $isNamedParams = !empty($args) && is_string(array_key_first($args));
        if ($isNamedParams && ($params->query->string ?? '') !== '') {
            if ($this->executeWithSubstitutedParams($params->query->string, $args)) {
                return;
            }
        }
    }

    /**
     * Binds variables to a prepared statement with specified types.
     * This method binds variables to a prepared statement based on their types,
     * allowing for more precise parameter binding.
     *
     * @param mixed $preparedParams The prepared statement to bind variables to.
     * @return void The prepared statement with bound variables.
     */
    private static function internalBindVariable(mixed $preparedParams): void
    {
        Validations::detectTypes($preparedParams);
    }

    /**
     * Parses an SQL statement and returns an statement.
     *
     * @param mixed ...$params The parameters for the query function.
     * @return string The statement resulting from the SQL statement.
     */
    public function parse(mixed ...$params): string
    {
        $dialectQuote = match ($this->get('driver')) {
            'mysql' => SQL::SQL_DIALECT_BACKTICK,
            'pgsql', 'sqlsrv', 'oci', 'firebird', 'sqlite' => SQL::SQL_DIALECT_DOUBLE_QUOTE,
            default => SQL::SQL_DIALECT_NONE,
        };
        $this->setQueryString(SQL::binding(SQL::escape(reset($params), $dialectQuote)));
        return $this->getQueryString();
    }

    /**
     * This function binds the parameters to a prepared statement.
     *
     * @param mixed ...$params
     * @return mixed
     */
    private function prepareStatement(mixed ...$params): mixed
    {
        $report = $this->getOptionsHandler()->getOptions(ODBC::ATTR_REPORT);
        if (!empty($report) || !is_null($report)) {
            $reportHandler = $this->getReportHandler();
            $reportHandler->setReportMode($report);
        }

        $this->setAllMetadata();
        if (!empty($params)) {
            $connection = $this->getInstance()->getConnection();
            if ($connection === null) {
                return false;
            }

            $isValidConnection = is_resource($connection) || (PHP_VERSION_ID >= 80400 && is_object($connection) && get_class($connection) === 'Odbc\Connection');

            if (!$isValidConnection) {
                return false;
            }

            $query = reset($params);
            if (!is_string($query) || empty($query)) {
                return false;
            }

            $query = $this->parse($query);
            if (!is_string($query) || empty($query)) {
                return false;
            }

            $hasPlaceholders = str_contains($query, '?');

            if (PHP_VERSION_ID >= 80400) {
                if (strlen($query) > 65535) {
                    return false;
                }

                if (preg_match('/[\x00-\x08\x0B-\x0C\x0E-\x1F]/', $query)) {
                    return false;
                }
            }

            $statement = false;

            if (PHP_VERSION_ID >= 80400) {
                ob_start();
                $errorOccurred = false;

                $errorHandler = set_error_handler(function (int $severity, string $message) use (&$errorOccurred): bool {
                    unset($severity);
                    if (
                        str_contains($message, 'Out of memory') ||
                        str_contains($message, 'memory') ||
                        str_contains($message, 'tried to allocate')
                    ) {
                        $errorOccurred = true;
                        return true;
                    }
                    return false;
                });

                try {
                    $statement = @odbc_prepare($connection, $query);

                    if ($errorOccurred || $statement === false) {
                        $statement = false;
                    }
                } catch (\Throwable $e) {
                    $statement = false;
                } finally {
                    ob_end_clean();

                    if ($errorHandler !== null) {
                        set_error_handler($errorHandler);
                    } else {
                        restore_error_handler();
                    }
                }
            } else {
                $statement = @odbc_prepare($connection, $query);
            }

            if (!$statement && PHP_VERSION_ID >= 80400 && !$hasPlaceholders) {
                $statement = @odbc_exec($connection, $query);
            }

            if ($statement) {
                $this->setStatement($statement);
            }
            return $statement;
        }
        return false;
    }

    /**
     * This function executes an SQL statement and returns the result set as a statement object.
     *
     * @param mixed $params Statement to be queried
     * @return IConnection
     */
    public function query(mixed ...$params): IConnection
    {
        if (empty($params)) {
            return $this->getInstance();
        }

        // Raw query (single string): same path as ComplexPrepare - odbc_exec + fetch + setStatement
        if (count($params) === 1 && is_string($params[0]) && $params[0] !== '') {
            if ($this->executeRawQuery($params[0])) {
                return $this->getInstance();
            }
        }

        $statement = $this->prepareStatement(...$params);
        if (!$statement) {
            return $this->getInstance();
        }

        $isValidStatement = is_resource($statement) || (PHP_VERSION_ID >= 80400 && is_object($statement) && get_class($statement) === 'Odbc\Result');
        if (!$isValidStatement) {
            return $this->getInstance();
        }

        $execSucceeded = $this->exec($statement);

        if ($execSucceeded) {
            $colCount = odbc_num_fields($statement);
            if ($colCount > 0) {
                $this->setQueryColumns($colCount);
                $results = [];
                while ($row = odbc_fetch_array($statement, 0)) {
                    $results[] = $row;
                }
                $this->setStatement(['results' => $results, 'statement' => $statement]);
                $this->setQueryRows(count($results));
            } else {
                $this->setAffectedRows(odbc_num_rows($statement));
            }
            return $this->getInstance();
        }

        // exec() failed: statement may be a result set from odbc_exec (prepareStatement fallback), fetch directly
        $colCount = odbc_num_fields($statement);
        if ($colCount > 0) {
            $this->setQueryColumns($colCount);
            $results = [];
            while ($row = odbc_fetch_array($statement, 0)) {
                $results[] = $row;
            }
            $this->setStatement(['results' => $results, 'statement' => $statement]);
            $this->setQueryRows(count($results));
        } else {
            $this->setAffectedRows(odbc_num_rows($statement));
        }
        return $this->getInstance();
    }

    /**
     * This function binds the parameters to a prepared query.
     *
     * @param mixed ...$params
     * @return IConnection
     */
    public function prepare(mixed ...$params): IConnection
    {
        if (empty($params)) {
            return $this->getInstance();
        }

        // Named params (associative array): use substitution + odbc_exec to avoid driver issues with multiple params
        if (count($params) === 2 && is_array($params[1]) && !empty($params[1])) {
            $firstKey = array_key_first($params[1]);
                if (is_string($firstKey) && str_starts_with($firstKey, ':')) {
                $this->setAllMetadata();
                $this->setQueryParameters($params[1]);
                if ($this->executeWithSubstitutedParams($params[0], $params[1])) {
                    return $this->getInstance();
                }
            }
        }

        $statement = $this->prepareStatement(...$params);

        if ($statement) {
            $bindParams = Statement::bind([$this->getStatement(), ...$params]);
            $this->bindParam($bindParams);
        } elseif (PHP_VERSION_ID >= 80400 && count($params) > 1) {
            $query = reset($params);
            $values = array_slice($params, 1);

            if (is_string($query) && !empty($values)) {
                $processedQuery = $query;
                foreach ($values as $value) {
                    $processedValue = is_string($value) ? "'" . str_replace("'", "''", $value) . "'"
                        : (is_null($value) ? 'NULL' : (is_bool($value) ? ($value ? '1' : '0') : $value));
                    $processedQuery = preg_replace('/\?/', (string)$processedValue, $processedQuery, 1);
                }

                $connection = $this->getInstance()->getConnection();
                if ($connection) {
                    $statement = @odbc_exec($connection, $processedQuery);
                    if ($statement) {
                        $this->setStatement($statement);
                        $this->query($processedQuery);
                    }
                }
            }
        }

        return $this->getInstance();
    }

    /**
     * This function runs an SQL statement and returns the number of affected rows.
     *
     * @param mixed $params Statement to be executed
     * @return mixed
     */
    public function exec(mixed ...$params): mixed
    {
        $statement = reset($params);
        $data = $params[1] ?? false;
        if (!is_array($data)) {
            $data = [];
        }
        $processedData = array_values($data);
        return @call_user_func_array('odbc_execute', [$statement, $processedData]);
    }
}
