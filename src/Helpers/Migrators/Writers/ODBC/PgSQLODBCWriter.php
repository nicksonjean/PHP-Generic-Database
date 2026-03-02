<?php

namespace GenericDatabase\Helpers\Migrators\Writers\ODBC;

use GenericDatabase\Helpers\Migrators\Writers\BaseODBCWriter;

/**
 * Native ODBC target writer for PostgreSQL.
 *
 * Uses ext-odbc (odbc_connect / odbc_exec / odbc_prepare / odbc_execute) directly.
 * No PDO dependency.
 *
 * Database is auto-created when it does not exist by connecting to the 'postgres'
 * maintenance database in autocommit mode.
 *
 * Required $params keys:
 *   host, port, user, password
 *   odbc_driver (optional) — default: 'PostgreSQL Unicode'
 */
class PgSQLODBCWriter extends BaseODBCWriter
{
    public function getTargetEngine(): string
    {
        return 'pgsql';
    }

    public function escapeIdentifier(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    protected function convertType(string $canonical, string $rawType): string
    {
        return match ($canonical) {
            'bigint'              => 'BIGINT',
            'smallint'            => 'SMALLINT',
            'integer'             => 'INTEGER',
            'boolean'             => 'BOOLEAN',
            'float'               => 'REAL',
            'double'              => 'DOUBLE PRECISION',
            'decimal'             => 'NUMERIC(' . $this->extractSize($rawType, '10,2') . ')',
            'date'                => 'DATE',
            'time'                => 'TIME',
            'datetime', 'timestamp' => 'TIMESTAMP',
            'blob'                => 'BYTEA',
            'clob', 'text'        => 'TEXT',
            'char'                => 'CHAR(' . $this->extractSize($rawType, '1') . ')',
            'varchar'             => 'VARCHAR(' . $this->extractSize($rawType, '255') . ')',
            default               => 'TEXT',
        };
    }

    protected function createDatabaseIfNotExists(string $dbName, array $params): void
    {
        $driver   = $params['odbc_driver'] ?? 'PostgreSQL Unicode';
        $host     = $params['host'];
        $port     = $params['port'];
        $user     = $params['user'];
        $password = $params['password'];

        // Connect to the 'postgres' maintenance database
        $dsn  = "Driver={$driver};Server={$host};Port={$port};Database=postgres;";
        $conn = @odbc_connect($dsn, $user, $password);
        if ($conn === false) {
            return;
        }

        // CREATE DATABASE cannot run inside a transaction — ensure autocommit
        @odbc_autocommit($conn, true);

        // Check existence before attempting to create
        $exists = false;
        $stmt   = @odbc_exec($conn, "SELECT 1 FROM pg_database WHERE datname = '$dbName'");
        if ($stmt !== false) {
            $exists = odbc_fetch_row($stmt) !== false;
            odbc_free_result($stmt);
        }

        if (!$exists) {
            @odbc_exec($conn, "CREATE DATABASE \"$dbName\"");
        }

        odbc_close($conn);
    }

    protected function createODBCConnection(string $dbName, array $params): mixed
    {
        $driver   = $params['odbc_driver'] ?? 'PostgreSQL Unicode';
        $host     = $params['host'];
        $port     = $params['port'];
        $user     = $params['user'];
        $password = $params['password'];

        $dsn = "Driver={$driver};Server={$host};Port={$port};Database={$dbName};";

        return odbc_connect($dsn, $user, $password);
    }
}
