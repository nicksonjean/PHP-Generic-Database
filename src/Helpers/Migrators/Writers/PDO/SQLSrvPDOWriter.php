<?php

namespace GenericDatabase\Helpers\Migrators\Writers\PDO;

use GenericDatabase\Helpers\Migrators\Writers\BasePDOWriter;
use PDO;
use PDOException;

/**
 * PDO target writer for Microsoft SQL Server (via pdo_sqlsrv driver).
 *
 * SQL Server specifics:
 *  - Identifiers are wrapped in square brackets [ ].
 *  - Database creation uses sys.databases check.
 *  - TEXT maps to NVARCHAR(MAX); BLOB maps to VARBINARY(MAX).
 *  - BOOLEAN maps to BIT.
 */
class SQLSrvPDOWriter extends BasePDOWriter
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
        try {
            $conn = new PDO(
                "sqlsrv:Server={$params['host']},{$params['port']}",
                $params['user'],
                $params['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $conn->exec(
                "IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = N'$dbName') "
                    . "CREATE DATABASE [$dbName]"
            );
        } catch (PDOException $e) {
            if (!str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        return new PDO(
            "sqlsrv:Server={$params['host']},{$params['port']};Database={$dbName}",
            $params['user'],
            $params['password'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
}
