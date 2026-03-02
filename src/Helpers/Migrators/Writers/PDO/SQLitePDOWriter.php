<?php

namespace GenericDatabase\Helpers\Migrators\Writers\PDO;

use GenericDatabase\Helpers\Migrators\Writers\BasePDOWriter;
use PDO;

/**
 * PDO target writer for SQLite.
 *
 * SQLite specifics:
 *  - Uses dynamic typing: INTEGER, REAL, BLOB, TEXT cover all cases.
 *  - Database file is created automatically on first connection.
 *  - The target path is taken from $params['database']; $dbName is used as fallback.
 *  - No schema/user concept; createDatabaseIfNotExists only ensures the directory exists.
 */
class SQLitePDOWriter extends BasePDOWriter
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
     * SQLite creates the database file automatically when the PDO connection is opened.
     * We only need to ensure the parent directory exists.
     */
    protected function createDatabaseIfNotExists(string $dbName, array $params): void
    {
        $path = $params['database'] ?? $dbName;
        $dir  = dirname($path);
        if ($dir !== '' && !is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        $path = $params['database'] ?? $dbName;

        return new PDO(
            "sqlite:$path",
            null,
            null,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
}
