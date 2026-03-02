<?php

namespace GenericDatabase\Helpers\Migrators\Writers\ODBCPDO;

use GenericDatabase\Helpers\Migrators\Writers\BasePDOWriter;
use PDO;

/**
 * Generic PDO over ODBC target writer.
 *
 * Use this when the target engine is not covered by the dedicated PDO over ODBC writers,
 * or when the caller wants full control over the ODBC connection string via PDO.
 *
 * The connection string can be provided in two ways:
 *
 *   1. $params['dsn']  — full DSN string passed directly to PDO.
 *      Example: "odbc:DRIVER={Custom Driver};SERVER=host;DATABASE=mydb;"
 *
 *   2. $params['odbc_driver'] + $params['host'] + $params['port'] + database name
 *      — a minimal DSN is assembled automatically.
 *
 * Type conversion defaults to generic ANSI-SQL types (INTEGER, REAL, TEXT, BLOB).
 * Identifier escaping uses ANSI double-quotes.
 *
 * @param string $engine Logical engine name used as result key (e.g. 'access', 'excel')
 */
class ODBCPDOWriter extends BasePDOWriter
{
    private string $engine;

    public function __construct(string $engine)
    {
        $this->engine = strtolower($engine);
    }

    public function getTargetEngine(): string
    {
        return $this->engine;
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

    protected function createDatabaseIfNotExists(string $dbName, array $params): void
    {
        // No-op: target must be configured and accessible via ODBC before migration.
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        if (!empty($params['dsn'])) {
            $dsn = $params['dsn'];
        } else {
            $driver = $params['odbc_driver'] ?? '';
            $host   = $params['host'] ?? '';
            $port   = $params['port'] ?? '';

            $dsn = "odbc:DRIVER={$driver}";
            if ($host !== '') {
                $dsn .= ";SERVER={$host}";
            }
            if ($port !== '') {
                $dsn .= ";PORT={$port}";
            }
            $dsn .= ";DATABASE={$dbName};";
        }

        return new PDO(
            $dsn,
            $params['user'] ?? '',
            $params['password'] ?? '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ] + ($params['options'] ?? [])
        );
    }
}
