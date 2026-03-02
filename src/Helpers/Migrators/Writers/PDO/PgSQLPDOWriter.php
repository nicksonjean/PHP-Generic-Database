<?php

namespace GenericDatabase\Helpers\Migrators\Writers\PDO;

use GenericDatabase\Helpers\Migrators\Writers\BasePDOWriter;
use PDO;
use PDOException;

/**
 * PDO target writer for PostgreSQL.
 *
 * PostgreSQL does not support UNSIGNED types; BOOLEAN is a native type;
 * DATETIME maps to TIMESTAMP.
 */
class PgSQLPDOWriter extends BasePDOWriter
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
        try {
            $conn = new PDO(
                "pgsql:host={$params['host']};port={$params['port']}",
                $params['user'],
                $params['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $exists = $conn->query(
                "SELECT 1 FROM pg_database WHERE datname = " . $conn->quote($dbName)
            )->fetchColumn();

            if (!$exists) {
                $conn->exec("CREATE DATABASE \"$dbName\"");
            }
        } catch (PDOException $e) {
            if (!str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        return new PDO(
            "pgsql:host={$params['host']};port={$params['port']};dbname={$dbName}",
            $params['user'],
            $params['password'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
}
