<?php

namespace GenericDatabase\Helpers\Migrators\Writers\ODBC;

use GenericDatabase\Helpers\Migrators\Writers\BaseODBCWriter;

/**
 * Native ODBC target writer for MySQL / MariaDB.
 *
 * Uses ext-odbc (odbc_connect / odbc_exec / odbc_prepare / odbc_execute) directly.
 * No PDO dependency.
 *
 * Database is auto-created when it does not exist by connecting without a
 * database name and running CREATE DATABASE IF NOT EXISTS.
 *
 * Tables created with ENGINE=InnoDB DEFAULT CHARSET=utf8mb4.
 *
 * Required $params keys:
 *   host, port, user, password
 *   odbc_driver (optional) — default: 'MySQL ODBC 8.0 Unicode Driver'
 */
class MySQLODBCWriter extends BaseODBCWriter
{
    public function getTargetEngine(): string
    {
        return 'mysql';
    }

    public function escapeIdentifier(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    protected function convertType(string $canonical, string $rawType): string
    {
        return match ($canonical) {
            'bigint'              => 'BIGINT',
            'smallint'            => 'SMALLINT',
            'integer'             => 'INT',
            'boolean'             => 'TINYINT(1)',
            'float'               => 'FLOAT',
            'double'              => 'DOUBLE',
            'decimal'             => 'DECIMAL(' . $this->extractSize($rawType, '10,2') . ')',
            'date'                => 'DATE',
            'time'                => 'TIME',
            'datetime', 'timestamp' => 'DATETIME',
            'blob'                => 'BLOB',
            'clob', 'text'        => 'LONGTEXT',
            'char'                => 'CHAR(' . $this->extractSize($rawType, '1') . ')',
            'varchar'             => 'VARCHAR(' . $this->extractSize($rawType, '255') . ')',
            default               => 'TEXT',
        };
    }

    protected function createDatabaseIfNotExists(string $dbName, array $params): void
    {
        $driver   = $params['odbc_driver'] ?? 'MySQL ODBC 8.0 Unicode Driver';
        $host     = $params['host'];
        $port     = $params['port'];
        $user     = $params['user'];
        $password = $params['password'];

        // Connect without specifying a database
        $dsn  = "Driver={$driver};Server={$host};Port={$port};CHARSET=utf8mb4;";
        $conn = @odbc_connect($dsn, $user, $password);
        if ($conn === false) {
            return; // best-effort; will fail later in createODBCConnection if DB absent
        }

        @odbc_exec(
            $conn,
            "CREATE DATABASE IF NOT EXISTS `$dbName` "
                . "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        odbc_close($conn);
    }

    protected function createODBCConnection(string $dbName, array $params): mixed
    {
        $driver   = $params['odbc_driver'] ?? 'MySQL ODBC 8.0 Unicode Driver';
        $host     = $params['host'];
        $port     = $params['port'];
        $user     = $params['user'];
        $password = $params['password'];

        $dsn = "Driver={$driver};Server={$host};Port={$port};"
            . "Database={$dbName};Charset=utf8mb4;";

        return odbc_connect($dsn, $user, $password);
    }
}
