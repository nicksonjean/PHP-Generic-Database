<?php

namespace GenericDatabase\Helpers\Migrators\Writers\ODBCPDO;

use GenericDatabase\Helpers\Migrators\Writers\PDO\PgSQLPDOWriter;
use PDO;

/**
 * PDO over ODBC target writer for PostgreSQL.
 *
 * Extends PgSQLWriter to reuse type conversion, DDL generation, and database
 * creation (via native PDO), but connects to the target database through PDO over ODBC.
 *
 * Required $params keys:
 *   host, port, user, password     — same as PgSQLWriter
 *   odbc_driver (optional)         — ODBC driver name (default: 'PostgreSQL Unicode')
 */
class PgSQLODBCPDOWriter extends PgSQLPDOWriter
{
    public function getTargetEngine(): string
    {
        return 'pgsql';
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        $driver   = $params['odbc_driver'] ?? 'PostgreSQL Unicode';
        $host     = $params['host'];
        $port     = $params['port'];
        $user     = $params['user'];
        $password = $params['password'];

        $dsn = "odbc:DRIVER={$driver};SERVER={$host};PORT={$port};DATABASE={$dbName};";

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
