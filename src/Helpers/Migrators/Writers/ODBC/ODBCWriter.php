<?php

namespace GenericDatabase\Helpers\Migrators\Writers\ODBC;

use GenericDatabase\Helpers\Migrators\Writers\BaseODBCWriter;

/**
 * Generic native ODBC target writer.
 *
 * Uses ext-odbc (odbc_connect / odbc_exec / odbc_prepare / odbc_execute) directly.
 * No PDO dependency.
 *
 * Use this when the target engine is not covered by the dedicated ODBC writers,
 * or when the caller wants full control over the native ODBC connection string.
 *
 * The connection string can be provided in two ways:
 *
 *   1. $params['dsn']  — full DSN string passed directly to odbc_connect().
 *      Example: "Driver={Custom Driver};SERVER=host;DATABASE=mydb;"
 *
 *   2. $params['odbc_driver'] + $params['host'] + $params['port'] + database name
 *      — a minimal DSN is assembled automatically.
 *
 * Target database / schema must be pre-configured and accessible before migration.
 * Type conversion defaults to generic ANSI-SQL types (INTEGER, REAL, TEXT, BLOB).
 * Identifier escaping uses ANSI double-quotes.
 *
 * @param string $engine Logical engine name used as result key (e.g. 'access', 'excel')
 */
class ODBCWriter extends BaseODBCWriter
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

    /**
     * No-op: target must be configured and accessible via ODBC before migration.
     */
    protected function createDatabaseIfNotExists(string $dbName, array $params): void
    {
        // No-op.
    }

    protected function createODBCConnection(string $dbName, array $params): mixed
    {
        if (!empty($params['dsn'])) {
            $dsn = $params['dsn'];
        } else {
            $driver = $params['odbc_driver'] ?? '';
            $host   = $params['host'] ?? '';
            $port   = $params['port'] ?? '';

            $dsn = "Driver={$driver}";
            if ($host !== '') {
                $dsn .= ";Server={$host}";
            }
            if ($port !== '') {
                $dsn .= ";Port={$port}";
            }
            $dsn .= ";Database={$dbName};";
        }

        return odbc_connect($dsn, $params['user'] ?? '', $params['password'] ?? '');
    }
}
