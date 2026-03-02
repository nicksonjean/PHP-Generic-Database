<?php

namespace GenericDatabase\Helpers\Migrators\Writers\ODBC;

use GenericDatabase\Helpers\Migrators\Writers\BaseODBCWriter;

/**
 * Native ODBC target writer for Firebird.
 *
 * Uses ext-odbc (odbc_connect / odbc_exec / odbc_prepare / odbc_execute) directly.
 * No PDO dependency.
 *
 * Firebird specifics:
 *  - Database files must pre-exist (.fdb created by gbak/isql/FlameRobin).
 *  - No IF NOT EXISTS in DDL; table existence is checked via RDB$RELATIONS.
 *  - Identifiers are double-quoted and uppercased.
 *  - TEXT maps to BLOB SUB_TYPE TEXT; BOOLEAN maps to SMALLINT (< v3).
 *
 * Required $params keys:
 *   host, port, user, password, database  — path to .fdb file
 *   charset (optional)                    — default: 'UTF8'
 *   odbc_driver (optional)                — default: 'Firebird/InterBase(r) driver'
 */
class FirebirdODBCWriter extends BaseODBCWriter
{
    public function getTargetEngine(): string
    {
        return 'firebird';
    }

    public function escapeIdentifier(string $name): string
    {
        return '"' . str_replace('"', '""', strtoupper($name)) . '"';
    }

    protected function convertType(string $canonical, string $rawType): string
    {
        return match ($canonical) {
            'bigint'              => 'BIGINT',
            'smallint'            => 'SMALLINT',
            'integer'             => 'INTEGER',
            'boolean'             => 'SMALLINT',
            'float'               => 'FLOAT',
            'double'              => 'DOUBLE PRECISION',
            'decimal'             => 'DECIMAL(' . $this->extractSize($rawType, '10,2') . ')',
            'date'                => 'DATE',
            'time'                => 'TIME',
            'datetime', 'timestamp' => 'TIMESTAMP',
            'blob'                => 'BLOB',
            'clob', 'text'        => 'BLOB SUB_TYPE TEXT',
            'char'                => 'CHAR(' . $this->extractSize($rawType, '1') . ')',
            'varchar'             => 'VARCHAR(' . $this->extractSize($rawType, '255') . ')',
            default               => 'VARCHAR(255)',
        };
    }

    /**
     * Firebird database files must be pre-created by a DBA tool.
     */
    protected function createDatabaseIfNotExists(string $dbName, array $params): void
    {
        // No-op.
    }

    protected function createODBCConnection(string $dbName, array $params): mixed
    {
        $driver   = $params['odbc_driver'] ?? 'Firebird/InterBase(r) driver';
        $host     = $params['host'];
        $port     = $params['port'] ?? 3050;
        $dbPath   = $params['database'] ?? $dbName;
        $charset  = $params['charset'] ?? 'UTF8';
        $user     = $params['user'];
        $password = $params['password'];

        $dsn = "Driver={$driver};DBNAME={$host}/{$port}:{$dbPath};CHARSET={$charset};";

        return odbc_connect($dsn, $user, $password);
    }

    // -----------------------------------------------------------------------
    // Firebird: no IF NOT EXISTS in DDL
    // -----------------------------------------------------------------------

    public function generateCreateTableSQL(string $table, array $schema): string
    {
        $tableEsc    = $this->escapeIdentifier($table);
        $colDefs     = [];
        $primaryKeys = [];

        foreach ($schema['columns'] as $col) {
            $colEsc  = $this->escapeIdentifier($col['name']);
            $colType = $this->convertType($col['canonical'], $col['raw_type']);
            $def     = "  $colEsc $colType";

            if ($col['notnull']) {
                $def .= ' NOT NULL';
            }

            if ($col['dflt_value'] !== null) {
                $defaultExpr = $this->formatDefaultValue($col['dflt_value'], $col['canonical']);
                if ($defaultExpr !== null) {
                    $def .= " DEFAULT $defaultExpr";
                }
            }

            $colDefs[] = $def;

            if ($col['pk']) {
                $primaryKeys[] = $col['name'];
            }
        }

        if (!empty($primaryKeys)) {
            $pkEsc     = array_map([$this, 'escapeIdentifier'], $primaryKeys);
            $colDefs[] = '  PRIMARY KEY (' . implode(', ', $pkEsc) . ')';
        }

        return "CREATE TABLE $tableEsc (\n" . implode(",\n", $colDefs) . "\n)";
    }

    /**
     * Override: use RDB$RELATIONS for reliable Firebird table check.
     */
    public function tableExists(mixed $conn, string $table): bool
    {
        $stmt = @odbc_exec(
            $conn,
            "SELECT COUNT(*) FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = UPPER('$table')"
        );
        if ($stmt === false) {
            return false;
        }
        $row   = odbc_fetch_array($stmt);
        $count = $row ? (int)current($row) : 0;
        odbc_free_result($stmt);
        return $count > 0;
    }
}
