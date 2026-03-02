<?php

namespace GenericDatabase\Helpers\Migrators\Writers\ODBCPDO;

use GenericDatabase\Helpers\Migrators\Writers\PDO\MySQLPDOWriter;
use PDO;

/**
 * PDO over ODBC target writer for MySQL / MariaDB.
 *
 * Extends MySQLWriter to reuse type conversion, DDL generation, and database
 * creation (via native PDO), but connects to the target database through PDO over ODBC.
 *
 * Required $params keys:
 *   host, port, user, password     — same as MySQLWriter
 *   odbc_driver (optional)         — ODBC driver name (default: 'MySQL ODBC 8.0 Unicode Driver')
 */
class MySQLODBCPDOWriter extends MySQLPDOWriter
{
    public function getTargetEngine(): string
    {
        return 'mysql';
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        $driver   = $params['odbc_driver'] ?? 'MySQL ODBC 8.0 Unicode Driver';
        $host     = $params['host'];
        $port     = $params['port'];
        $user     = $params['user'];
        $password = $params['password'];

        $dsn = "odbc:DRIVER={$driver};SERVER={$host};PORT={$port};"
            . "DATABASE={$dbName};CHARSET=utf8mb4;";

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
