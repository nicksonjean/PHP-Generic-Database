<?php

namespace GenericDatabase\Helpers\Migrators\Writers\ODBCPDO;

use GenericDatabase\Helpers\Migrators\Writers\PDO\OCIPDOWriter;
use PDO;

/**
 * PDO over ODBC target writer for Oracle.
 *
 * Extends OCIWriter to reuse type conversion, DDL generation, Oracle-specific
 * INSERT placeholders, and date handling, but connects through PDO over ODBC.
 *
 * Oracle has no automatic database creation; the target schema/user must pre-exist.
 *
 * Required $params keys:
 *   host, port, user, password     — same as OCIWriter
 *   service (optional)             — Oracle service name / SID (default: $dbName)
 *   odbc_driver (optional)         — ODBC driver name (default: 'Oracle in OraClient21Home1')
 */
class OCIODBCPDOWriter extends OCIPDOWriter
{
    public function getTargetEngine(): string
    {
        return 'oracle';
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        $driver   = $params['odbc_driver'] ?? 'Oracle in OraClient21Home1';
        $host     = $params['host'];
        $port     = $params['port'];
        $service  = $params['service'] ?? $dbName;
        $user     = $params['user'];
        $password = $params['password'];

        $dsn = "odbc:DRIVER={$driver};DBQ=//{$host}:{$port}/{$service};";

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
