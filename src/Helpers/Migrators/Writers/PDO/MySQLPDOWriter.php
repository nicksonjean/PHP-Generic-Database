<?php

namespace GenericDatabase\Helpers\Migrators\Writers\PDO;

use GenericDatabase\Helpers\Migrators\Writers\BasePDOWriter;
use PDO;
use PDOException;

/**
 * PDO target writer for MySQL / MariaDB.
 *
 * Tables created with ENGINE=InnoDB DEFAULT CHARSET=utf8mb4.
 * Database is created automatically with utf8mb4 charset when it does not exist.
 */
class MySQLPDOWriter extends BasePDOWriter
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
        try {
            $conn = new PDO(
                "mysql:host={$params['host']};port={$params['port']};charset=utf8mb4",
                $params['user'],
                $params['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $conn->exec(
                "CREATE DATABASE IF NOT EXISTS `$dbName` "
                    . "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
        } catch (PDOException $e) {
            if (!str_contains($e->getMessage(), 'database exists')) {
                throw $e;
            }
        }
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        return new PDO(
            "mysql:host={$params['host']};port={$params['port']};dbname={$dbName};charset=utf8mb4",
            $params['user'],
            $params['password'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
}
