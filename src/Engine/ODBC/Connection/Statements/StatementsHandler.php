<?php

namespace GenericDatabase\Engine\ODBC\Connection\Statements;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IStatements;
use GenericDatabase\Abstract\AbstractStatements;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Schemas;
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
        $filter = "WHERE TABLE_NAME = ? AND COLUMN_KEY = ? AND EXTRA = ?";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = odbc_prepare($this->getInstance()->getConnection(), $query);
        odbc_execute($stmt, [$name, 'PRI', 'auto_increment']);
        if ($stmt && odbc_fetch_row($stmt)) {
            $identityColumn = odbc_result($stmt, "COLUMN_NAME");
            $query = "SELECT MAX($identityColumn) AS LastInsertedID FROM $name";
            $stmt = odbc_exec($this->getInstance()->getConnection(), $query);
            if ($stmt && odbc_fetch_row($stmt)) {
                return (int) odbc_result($stmt, "LastInsertedID");
            }
        }
        return false;
    }

    private function lastInsertIdPgSQL(?string $name = null): int|false
    {
        if (!$name) {
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
        $stmt = odbc_prepare($this->getInstance()->getConnection(), $query);
        Run::call('odbc_execute', $stmt, [$name, $this->get('database')]);
        if ($stmt && odbc_fetch_row($stmt)) {
            $sequenceName = odbc_result($stmt, "NAME");
            $query = "SELECT currval('$sequenceName') AS last_value";
            $stmt = odbc_exec($this->getInstance()->getConnection(), $query);
            if ($stmt && odbc_fetch_row($stmt)) {
                return (int) odbc_result($stmt, "last_value");
            }
        }
        return false;
    }

    private function lastInsertIdSQLSrv(?string $name = null): int|false
    {
        if (!$name) {
            $query = "SELECT CAST(@@IDENTITY AS BIGINT) AS LastInsertedID";
            $stmt = odbc_exec($this->getInstance()->getConnection(), $query);
            if ($stmt && odbc_fetch_row($stmt)) {
                return (int) odbc_result($stmt, "LastInsertedID");
            }
            return false;
        }
        $filter = "WHERE TABLE_NAME = ? AND TABLE_SCHEMA = SCHEMA_NAME() AND COLUMNPROPERTY(OBJECT_ID(TABLE_SCHEMA + '.' + TABLE_NAME), COLUMN_NAME, 'IsIdentity') = 1";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = odbc_prepare($this->getInstance()->getConnection(), $query);
        odbc_execute($stmt, [$name]);
        if ($stmt && odbc_fetch_row($stmt)) {
            $identityColumn = odbc_result($stmt, "COLUMN_NAME");
            $query = "SELECT MAX($identityColumn) AS LastInsertedID FROM $name";
            $stmt = odbc_exec($this->getInstance()->getConnection(), $query);
            if ($stmt && odbc_fetch_row($stmt)) {
                return (int) odbc_result($stmt, "LastInsertedID");
            }
        }
        return false;
    }

    private function lastInsertIdOCI(?string $name = null): int|false
    {
        if ($name !== null) {
            $filter = "WHERE OWNER = USER AND identity_column = 'YES' AND TABLE_NAME = ?";
            $query = sprintf("SELECT data_default AS sequence_val, table_name, column_name FROM all_tab_columns %s", $filter);
            $stmt = odbc_prepare($this->getInstance()->getConnection(), $query);
            odbc_execute($stmt, [$name]);
            if ($stmt && odbc_fetch_row($stmt)) {
                $sequenceVal = odbc_result($stmt, "sequence_val");
                $sequenceVal = str_replace('.nextval', '.currval', $sequenceVal);
                $sequenceVal = str_replace('"', '', $sequenceVal);
                $query = "SELECT $sequenceVal FROM DUAL";
                $stmt = odbc_exec($this->getInstance()->getConnection(), $query);
                if ($stmt && odbc_fetch_row($stmt)) {
                    return (int) odbc_result($stmt, 1);
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
        $filter = 'WHERE RDB$RELATION_NAME=? AND RDB$IDENTITY_TYPE=1';
        $query = sprintf('SELECT RDB$FIELD_NAME, RDB$GENERATOR_NAME FROM RDB$RELATION_FIELDS %s', $filter);
        $stmt = odbc_prepare($this->getInstance()->getConnection(), $query);
        odbc_execute($stmt, [$name]);
        if ($stmt && odbc_fetch_row($stmt)) {
            $identityColumn = odbc_result($stmt, 'RDB$GENERATOR_NAME');
            $query = sprintf('SELECT GEN_ID(%s,0) AS LASTINSERTEDID FROM RDB$DATABASE', $identityColumn);
            $stmt = odbc_exec($this->getInstance()->getConnection(), $query);
            if ($stmt && odbc_fetch_row($stmt)) {
                return (int) odbc_result($stmt, "LastInsertedID");
            }
        }
        return false;
    }

    private function lastInsertIdSQLite(?string $name): int|false
    {
        if (!$name) {
            return false;
        }
        $query = "SELECT seq FROM sqlite_sequence WHERE name = ?";
        $stmt = odbc_prepare($this->getInstance()->getConnection(), $query);
        if (!$stmt) {
            return false;
        }
        if (!odbc_execute($stmt, [$name])) {
            return false;
        }
        if (odbc_fetch_row($stmt)) {
            return (int) odbc_result($stmt, "seq");
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
     * Binds a parameter to a variable in the SQL statement.
     *
     * @param object $params The name of the parameter or an args of parameters and values.
     * @return void
     */
    private function internalBindParamArgs(object $params): void
    {
        $this->internalBindVariable($params->query->arguments);
        if ($this->exec($params->statement->object, array_values($params->query->arguments))) {
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
            'pgsql', 'sqlsrv', 'oci', 'firebird' => SQL::SQL_DIALECT_DOUBLE_QUOTE,
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
            $statement = odbc_prepare($this->getInstance()->getConnection(), $this->parse(...$params));
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
        if (!empty($params) && ($statement = $this->prepareStatement(...$params)) && $this->exec($statement)) {
            $colCount = is_resource($statement) ? odbc_num_fields($statement) : 0;
            if ($colCount > 0) {
                $this->setQueryColumns($colCount);
                $this->setQueryRows(
                    (function (mixed $stmt): int {
                        $results = [];
                        $rows = 0;
                        while ($row = odbc_fetch_array($stmt, 0)) {
                            $results[] = $row;
                            $rows++;
                        }
                        $this->setStatement(['results' => $results, 'statement' => $stmt]);
                        return $rows;
                    })($statement)
                );
            } else {
                if (is_resource($statement)) {
                    $this->setAffectedRows(odbc_num_rows($statement));
                }
            }
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
        if (!empty($params) && ($this->prepareStatement(...$params))) {
            $bindParams = Schemas::makeArgs([$this->getStatement(), ...$params]);
            $this->bindParam($bindParams);
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
