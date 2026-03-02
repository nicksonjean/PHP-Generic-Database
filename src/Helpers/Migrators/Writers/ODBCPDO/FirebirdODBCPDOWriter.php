<?php

namespace GenericDatabase\Helpers\Migrators\Writers\ODBCPDO;

use GenericDatabase\Helpers\Migrators\Writers\PDO\FirebirdPDOWriter;
use PDO;

/**
 * PDO over ODBC target writer for Firebird.
 *
 * Extends FirebirdWriter to reuse type conversion, DDL generation (no IF NOT EXISTS),
 * and RDB$RELATIONS-based tableExists check, but connects through PDO over ODBC.
 *
 * Firebird database files must pre-exist (.fdb created by gbak/isql/FlameRobin).
 *
 * Required $params keys:
 *   host, port, user, password     — same as FirebirdWriter
 *   database                       — path to the .fdb file or alias
 *   charset (optional)             — character set (default: 'UTF8')
 *   odbc_driver (optional)         — ODBC driver name (default: 'Firebird/InterBase(r) driver')
 */
class FirebirdODBCPDOWriter extends FirebirdPDOWriter
{
    public function getTargetEngine(): string
    {
        return 'firebird';
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        $driver   = $params['odbc_driver'] ?? 'Firebird/InterBase(r) driver';
        $host     = $params['host'];
        $port     = $params['port'] ?? 3050;
        $dbPath   = $params['database'] ?? $dbName;
        $charset  = $params['charset'] ?? 'UTF8';
        $user     = $params['user'];
        $password = $params['password'];

        $dsn = "odbc:DRIVER={$driver};DBNAME={$host}/{$port}:{$dbPath};CHARSET={$charset};";

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
