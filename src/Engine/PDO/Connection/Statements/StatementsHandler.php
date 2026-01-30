<?php

namespace GenericDatabase\Engine\PDO\Connection\Statements;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IStatements;
use GenericDatabase\Abstract\AbstractStatements;
use GenericDatabase\Generic\Statements\Statement;
use GenericDatabase\Helpers\Parsers\SQL;
use GenericDatabase\Engine\PDO\Connection\XPDO;
use PDOStatement;
use PDO;

/**
 * Concrete implementation for PDO database
 */
class StatementsHandler extends AbstractStatements implements IStatements
{
    private function lastInsertIdMySQL(?string $name = null): int
    {
        if (!$name) {
            return (int) $this->getInstance()->getConnection()->lastInsertId();
        }
        $filter = "WHERE TABLE_NAME = :tableName AND COLUMN_KEY = :columnKey AND EXTRA = :extra";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = $this->getInstance()->getConnection()->prepare($query);
        $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
        $stmt->bindValue(':columnKey', 'PRI', PDO::PARAM_STR);
        $stmt->bindValue(':extra', 'auto_increment', PDO::PARAM_STR);
        $stmt->execute();
        $autoKey = $stmt->fetch(PDO::FETCH_ASSOC);
        if (isset($autoKey['COLUMN_NAME'])) {
            $query = sprintf("SELECT MAX(%s) AS value FROM %s", $autoKey['COLUMN_NAME'], $name);
            $stmt = $this->getInstance()->getConnection()->prepare($query);
            $stmt->execute();
            $maxIndex = $stmt->fetch(PDO::FETCH_ASSOC)['value'];
            if ($maxIndex !== null) {
                return (int) $maxIndex;
            }
        }
        return ($autoKey['COLUMN_NAME'] ? (int) $autoKey['COLUMN_NAME'] : 0);
    }

    private function lastInsertIdPgSQL(?string $name = null): int|false
    {
        if (!$name) {
            return (int) $this->getInstance()->getConnection()->lastInsertId();
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
        WHERE table_identities.TABLE_NAME = :tableName
        AND (SELECT current_database()) = :databaseName";
        $stmt = $this->getInstance()->getConnection()->prepare($query);
        $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
        $stmt->bindValue(':databaseName', $this->get('database'), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['last_value']) && is_null($row['last_value']) && isset($row['name'])) {
            $seqName = $row['name'];
            $seqQuery = "SELECT currval(:seqName)";
            $seqStmt = $this->getInstance()->getConnection()->prepare($seqQuery);
            $seqStmt->bindValue(':seqName', $seqName);
            $seqStmt->execute();
            $seqResult = $seqStmt->fetch(PDO::FETCH_NUM);
            return $seqResult ? (int) $seqResult[0] : false;
        } elseif ($row && isset($row['last_value']) && !is_null($row['last_value'])) {
            return (int) $row['last_value'];
        }
        return false;
    }

    private function lastInsertIdSQLSrv(?string $name = null): int|false
    {
        if (!$name) {
            $query = "SELECT CAST(@@IDENTITY AS BIGINT) AS LastInsertedID";
            $stmt = $this->getInstance()->getConnection()->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int) $result['LastInsertedID'] : 0;
        }
        $filter = "WHERE TABLE_NAME = :tableName AND TABLE_SCHEMA = SCHEMA_NAME() AND COLUMNPROPERTY(OBJECT_ID(TABLE_SCHEMA + '.' + TABLE_NAME), COLUMN_NAME, 'IsIdentity') = 1";
        $query = sprintf("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS %s", $filter);
        $stmt = $this->getInstance()->getConnection()->prepare($query);
        $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (isset($row['COLUMN_NAME'])) {
            $identityColumn = $row['COLUMN_NAME'];
            $query = sprintf("SELECT MAX(%s) AS LastInsertedID FROM %s", $identityColumn, $name);
            $stmt = $this->getInstance()->getConnection()->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int) $result['LastInsertedID'] : 0;
        }
        return false;
    }

    private function lastInsertIdOCI(?string $name = null): int|false
    {
        if ($name !== null) {
            $filter = "WHERE OWNER = USER AND identity_column = 'YES' AND TABLE_NAME = :tableName";
            $seqQuery = sprintf("SELECT data_default AS sequence_val, table_name, column_name FROM all_tab_columns %s", $filter);
            $stmt = $this->getInstance()->getConnection()->prepare($seqQuery);
            $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
            if ($stmt->execute()) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['SEQUENCE_VAL'])) {
                    $sequenceVal = $row['SEQUENCE_VAL'];
                    $sequenceVal = str_replace('.nextval', '.currval', $sequenceVal);
                    $sequenceVal = str_replace('"', '', $sequenceVal);
                    $query = "SELECT $sequenceVal FROM DUAL";
                    $statement = $this->getInstance()->getConnection()->prepare($query);
                    if ($statement->execute()) {
                        $row = $statement->fetch(PDO::FETCH_NUM);
                        return $row ? (int) $row[0] : false;
                    }
                }
            }
        }
        return false;
    }

    private function lastInsertIdFirebird(?string $name = null): int|false
    {
        if (!$name) {
            return 0;
        }
        $filter = 'WHERE RDB$RELATION_NAME=:tableName AND RDB$IDENTITY_TYPE=1';
        $query = sprintf('SELECT RDB$FIELD_NAME, RDB$GENERATOR_NAME FROM RDB$RELATION_FIELDS %s', $filter);
        $stmt = $this->getInstance()->getConnection()->prepare($query);
        $stmt->bindValue(':tableName', $name, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (isset($row['RDB$GENERATOR_NAME'])) {
            $identityColumn = $row['RDB$GENERATOR_NAME'];
            $query = sprintf('SELECT GEN_ID(%s,0) AS LASTINSERTEDID FROM RDB$DATABASE', $identityColumn);
            $stmt = $this->getInstance()->getConnection()->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int) $result['LASTINSERTEDID'] : 0;
        }
        return false;
    }

    private function lastInsertIdSQLite(?string $name): int|false
    {
        if (!$name) {
            return $this->getInstance()->getConnection()->lastInsertId();
        }
        $query = "SELECT seq FROM sqlite_sequence WHERE name = :name";
        $stmt = $this->getInstance()->getConnection()->prepare($query);
        if (!$stmt) {
            return false;
        }
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return false;
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int) $result['seq'] : false;
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
            default => (int) $this->getInstance()->getConnection()->lastInsertId(),
        };
    }

    /**
     * This function quotes a string for use in an SQL statement and escapes special characters (such as quotes).
     *
     * @param mixed $params Content to be quoted
     * @return mixed
     */
    public function quote(mixed ...$params): mixed
    {
        $string = reset($params);
        $type = (empty($params) || !isset($params[1])) ? PDO::PARAM_STR : $params[1];
        return $this->getInstance()->getConnection()->quote($string, $type);
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
        $this->setQueryColumns((int) $this->getStatement()->columnCount());
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
            $this->setStatement($this->internalBindVariable($argument, $params->statement->object));
            if ($this->exec($this->getStatement())) {
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
        $this->setStatement(@$this->internalBindVariable($params->query->arguments, $params->statement->object));
        if ($this->exec($this->getStatement())) {
            if ($this->getStatement()->columnCount() > 0) {
                $this->setQueryRows((int) count($this->getStatement()->fetchAll(PDO::FETCH_ASSOC)));
            } else {
                $this->setAffectedRows((int) $this->getStatement()->rowCount());
            }
        }
    }

    /**
     * Binds variables to a prepared statement with specified types.
     * This method binds variables to a prepared statement based on their types,
     * allowing for more precise parameter binding.
     *
     * @param array &$preparedParams An array containing the parameters to bind.
     * @param PDOStatement $statement The prepared statement to bind variables to.
     * @return PDOStatement The prepared statement with bound variables.
     */
    private static function internalBindVariable(array &$preparedParams, PDOStatement $statement): PDOStatement
    {
        $index = 0;
        foreach ($preparedParams as &$arg) {
            if (is_bool($arg)) {
                $types = PDO::PARAM_BOOL;
            } elseif (is_integer($arg)) {
                $types = PDO::PARAM_INT;
            } elseif (is_float($arg)) {
                $types = PDO::PARAM_STR;
            } elseif (is_string($arg)) {
                $types = PDO::PARAM_STR;
            } elseif (is_null($arg)) {
                $types = PDO::PARAM_NULL;
            } else {
                $types = PDO::PARAM_LOB;
            }
            call_user_func_array([$statement, 'bindParam'], [array_keys($preparedParams)[$index], &$arg, $types]);
            $index++;
        }
        return $statement;
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
        $this->setQueryString(SQL::escape(reset($params), $dialectQuote));
        return $this->getQueryString();
    }

    /**
     * This function binds the parameters to a prepared statement.
     *
     * @param mixed ...$params
     * @return PDOStatement|false
     */
    private function prepareStatement(mixed ...$params): PDOStatement|false
    {
        $report = $this->getOptionsHandler()->getOptions(XPDO::ATTR_REPORT);
        if (!empty($report) || !is_null($report)) {
            $reportHandler = $this->getReportHandler();
            $reportHandler->setReportMode($report);
        }

        $this->setAllMetadata();
        if (!empty($params)) {
            $cursor = match ($this->get('driver')) {
                'oci', 'mysql', 'pgsql' => [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY],
                'firebird', 'sqlsrv' => [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL],
                'sqlite' => [],
                default => [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY],
            };
            $statement = $this->getInstance()->getConnection()->prepare($this->parse(...$params), $cursor);
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
            $colCount = $statement->columnCount();
            if ($colCount > 0) {
                $this->setQueryColumns($colCount);
                $this->setQueryRows(in_array($this->get('driver'), ['oci', 'firebird', 'sqlite'])
                    ? (function (PDOStatement $stmt): int {
                        $rows = 0;
                        while ($stmt->fetch(PDO::FETCH_ASSOC)) {
                            $rows++;
                        }
                        $this->exec($stmt);
                        return $rows;
                    })($statement)
                    : $statement->rowCount());
            } else {
                $this->setAffectedRows($statement->rowCount());
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
            $bindParams = Statement::bind([$this->getStatement(), ...$params]);
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
        $stmt = reset($params);
        return $stmt->execute();
    }
}
