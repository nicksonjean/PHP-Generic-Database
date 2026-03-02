<?php

namespace GenericDatabase\Helpers\Migrators\Writers\ODBCPDO;

use GenericDatabase\Helpers\Migrators\Writers\PDO\SQLitePDOWriter;
use PDO;

/**
 * PDO over ODBC target writer for SQLite.
 *
 * Extends SQLiteWriter to reuse type conversion and DDL generation,
 * but connects through PDO over ODBC.
 *
 * Required $params keys:
 *   database                       — absolute path to the SQLite database file
 *   odbc_driver (optional)         — ODBC driver name (default: 'SQLite3 ODBC Driver')
 */
class SQLiteODBCPDOWriter extends SQLitePDOWriter
{
    public function getTargetEngine(): string
    {
        return 'sqlite';
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        $driver = $params['odbc_driver'] ?? 'SQLite3 ODBC Driver';
        $dbPath = $params['database'] ?? $dbName;

        $dsn = "odbc:DRIVER={$driver};DATABASE={$dbPath};";

        return new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
