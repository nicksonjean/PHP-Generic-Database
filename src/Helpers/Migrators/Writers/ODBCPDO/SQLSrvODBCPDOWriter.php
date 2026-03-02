<?php

namespace GenericDatabase\Helpers\Migrators\Writers\ODBCPDO;

use GenericDatabase\Helpers\Migrators\Writers\PDO\SQLSrvPDOWriter;
use PDO;

/**
 * PDO over ODBC target writer for Microsoft SQL Server.
 *
 * Extends SQLSrvWriter to reuse type conversion, DDL generation, and database
 * creation (via native PDO sqlsrv), but connects through PDO over ODBC.
 *
 * Required $params keys:
 *   host, port, user, password     — same as SQLSrvWriter
 *   odbc_driver (optional)         — ODBC driver name (default: 'ODBC Driver 18 for SQL Server')
 */
class SQLSrvODBCPDOWriter extends SQLSrvPDOWriter
{
    public function getTargetEngine(): string
    {
        return 'sqlsrv';
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        $driver   = $params['odbc_driver'] ?? 'ODBC Driver 18 for SQL Server';
        $host     = $params['host'];
        $port     = $params['port'];
        $user     = $params['user'];
        $password = $params['password'];

        $dsn = "odbc:DRIVER={$driver};SERVER={$host},{$port};"
            . "DATABASE={$dbName};TrustServerCertificate=yes;";

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
