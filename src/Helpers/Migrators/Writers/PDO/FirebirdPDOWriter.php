<?php

namespace GenericDatabase\Helpers\Migrators\Writers\PDO;

use GenericDatabase\Helpers\Migrators\Writers\BasePDOWriter;
use PDO;

/**
 * PDO target writer for Firebird (via PDO firebird driver).
 *
 * Firebird specifics:
 *  - Does not support IF NOT EXISTS in DDL; table existence is checked via
 *    RDB$RELATIONS before attempting CREATE TABLE.
 *  - TEXT maps to BLOB SUB_TYPE TEXT (CLOB equivalent).
 *  - Database files must pre-exist; this writer does NOT create the .fdb file.
 *  - Identifiers are quoted with double-quotes and uppercased.
 */
class FirebirdPDOWriter extends BasePDOWriter
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
            'boolean'             => 'SMALLINT',              // Firebird <v3 has no BOOLEAN
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
     * Firebird does not support runtime CREATE DATABASE via SQL.
     * The target .fdb file must already exist (created by gbak, isql, or FlameRobin).
     */
    protected function createDatabaseIfNotExists(string $dbName, array $params): void
    {
        // No-op: Firebird database files must be pre-created by a DBA tool.
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        $host    = $params['host'];
        $port    = $params['port'] ?? 3050;
        $charset = $params['charset'] ?? 'UTF8';
        $dbPath  = $params['database'] ?? $dbName;

        return new PDO(
            "firebird:dbname={$host}/{$port}:{$dbPath};charset={$charset}",
            $params['user'],
            $params['password'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    // -----------------------------------------------------------------------
    // Firebird-specific DDL: no IF NOT EXISTS support
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

    public function tableExists(PDO $conn, string $table): bool
    {
        try {
            $stmt = $conn->prepare(
                "SELECT COUNT(*) FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = UPPER(?)"
            );
            $stmt->execute([$table]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (\Exception) {
            return false;
        }
    }
}
