<?php

namespace GenericDatabase\Helpers\Migrators\Writers\ODBC;

use GenericDatabase\Helpers\Migrators\Writers\BaseODBCWriter;

/**
 * Native ODBC target writer for SQLite.
 *
 * Uses ext-odbc (odbc_connect / odbc_exec / odbc_prepare / odbc_execute) directly.
 * No PDO dependency.
 *
 * SQLite uses dynamic typing; all columns map to INTEGER, REAL, BLOB, or TEXT.
 * The database file is created automatically by the SQLite ODBC driver.
 *
 * Required $params keys:
 *   database                   — absolute path to the SQLite file
 *   odbc_driver (optional)     — default: 'SQLite3 ODBC Driver'
 */
class SQLiteODBCWriter extends BaseODBCWriter
{
    public function getTargetEngine(): string
    {
        return 'sqlite';
    }

    public function escapeIdentifier(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    protected function convertType(string $canonical, string $rawType): string
    {
        return match ($canonical) {
            'bigint', 'integer', 'smallint', 'boolean' => 'INTEGER',
            'float', 'double', 'decimal'               => 'REAL',
            'blob'                                     => 'BLOB',
            default                                    => 'TEXT',
        };
    }

    /**
     * Ensure the parent directory of the SQLite file exists.
     * The ODBC driver creates the file on first connection.
     */
    protected function createDatabaseIfNotExists(string $dbName, array $params): void
    {
        $path = $params['database'] ?? $dbName;
        $dir  = dirname($path);
        if ($dir !== '' && !is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    protected function createODBCConnection(string $dbName, array $params): mixed
    {
        $driver = $params['odbc_driver'] ?? 'SQLite3 ODBC Driver';
        $dbPath = $params['database'] ?? $dbName;

        $dsn = "Driver={$driver};DATABASE={$dbPath};";

        return odbc_connect($dsn, '', '');
    }

    /**
     * Override: use sqlite_master for reliable table existence check.
     */
    public function tableExists(mixed $conn, string $table): bool
    {
        $stmt = @odbc_exec(
            $conn,
            "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='$table'"
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
