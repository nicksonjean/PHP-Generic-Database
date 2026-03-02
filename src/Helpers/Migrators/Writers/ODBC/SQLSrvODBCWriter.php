<?php

namespace GenericDatabase\Helpers\Migrators\Writers\ODBC;

use GenericDatabase\Helpers\Migrators\Writers\BaseODBCWriter;

/**
 * Native ODBC target writer for Microsoft SQL Server.
 *
 * Uses ext-odbc (odbc_connect / odbc_exec / odbc_prepare / odbc_execute) directly.
 * No PDO dependency.
 *
 * Database is auto-created when it does not exist by connecting to the 'master'
 * system database first.
 *
 * Required $params keys:
 *   host, port, user, password
 *   odbc_driver (optional) — default: 'ODBC Driver 18 for SQL Server'
 */
class SQLSrvODBCWriter extends BaseODBCWriter
{
    public function getTargetEngine(): string
    {
        return 'sqlsrv';
    }

    public function escapeIdentifier(string $name): string
    {
        return '[' . str_replace(']', ']]', $name) . ']';
    }

    protected function convertType(string $canonical, string $rawType): string
    {
        return match ($canonical) {
            'bigint'              => 'BIGINT',
            'smallint'            => 'SMALLINT',
            'integer'             => 'INT',
            'boolean'             => 'BIT',
            'float'               => 'FLOAT',
            'double'              => 'FLOAT',
            'decimal'             => 'DECIMAL(' . $this->extractSize($rawType, '10,2') . ')',
            'date'                => 'DATE',
            'time'                => 'TIME',
            'datetime'            => 'DATETIME2',
            'timestamp'           => 'DATETIME2',
            'blob'                => 'VARBINARY(MAX)',
            'clob', 'text'        => 'NVARCHAR(MAX)',
            'char'                => 'NCHAR(' . $this->extractSize($rawType, '1') . ')',
            'varchar'             => 'NVARCHAR(' . $this->extractSize($rawType, '255') . ')',
            default               => 'NVARCHAR(MAX)',
        };
    }

    protected function createDatabaseIfNotExists(string $dbName, array $params): void
    {
        $driver   = $params['odbc_driver'] ?? 'ODBC Driver 18 for SQL Server';
        $host     = $params['host'];
        $port     = $params['port'];
        $user     = $params['user'];
        $password = $params['password'];

        // Connect to the master system database
        $dsn  = "Driver={$driver};Server={$host},{$port};"
            . "Database=master;TrustServerCertificate=yes;";
        $conn = @odbc_connect($dsn, $user, $password);
        if ($conn === false) {
            return;
        }

        @odbc_exec(
            $conn,
            "IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = N'$dbName') "
                . "CREATE DATABASE [$dbName]"
        );

        odbc_close($conn);
    }

    protected function createODBCConnection(string $dbName, array $params): mixed
    {
        $driver   = $params['odbc_driver'] ?? 'ODBC Driver 18 for SQL Server';
        $host     = $params['host'];
        $port     = $params['port'];
        $user     = $params['user'];
        $password = $params['password'];

        $dsn = "Driver={$driver};Server={$host},{$port};"
            . "Database={$dbName};TrustServerCertificate=yes;";

        return odbc_connect($dsn, $user, $password);
    }
}
