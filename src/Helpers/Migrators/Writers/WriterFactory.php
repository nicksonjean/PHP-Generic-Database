<?php

namespace GenericDatabase\Helpers\Migrators\Writers;

use GenericDatabase\Helpers\Migrators\Writers\PDO\MySQLPDOWriter;
use GenericDatabase\Helpers\Migrators\Writers\PDO\PgSQLPDOWriter;
use GenericDatabase\Helpers\Migrators\Writers\PDO\OCIPDOWriter;
use GenericDatabase\Helpers\Migrators\Writers\PDO\SQLSrvPDOWriter;
use GenericDatabase\Helpers\Migrators\Writers\PDO\FirebirdPDOWriter;
use GenericDatabase\Helpers\Migrators\Writers\PDO\SQLitePDOWriter;
use GenericDatabase\Helpers\Migrators\Writers\PDO\PDOWriter;
use GenericDatabase\Helpers\Migrators\Writers\ODBCPDO\MySQLODBCPDOWriter;
use GenericDatabase\Helpers\Migrators\Writers\ODBCPDO\PgSQLODBCPDOWriter;
use GenericDatabase\Helpers\Migrators\Writers\ODBCPDO\OCIODBCPDOWriter;
use GenericDatabase\Helpers\Migrators\Writers\ODBCPDO\SQLSrvODBCPDOWriter;
use GenericDatabase\Helpers\Migrators\Writers\ODBCPDO\FirebirdODBCPDOWriter;
use GenericDatabase\Helpers\Migrators\Writers\ODBCPDO\SQLiteODBCPDOWriter;
use GenericDatabase\Helpers\Migrators\Writers\ODBCPDO\ODBCPDOWriter;
use GenericDatabase\Helpers\Migrators\Writers\ODBC\MySQLODBCWriter;
use GenericDatabase\Helpers\Migrators\Writers\ODBC\PgSQLODBCWriter;
use GenericDatabase\Helpers\Migrators\Writers\ODBC\OCIODBCWriter;
use GenericDatabase\Helpers\Migrators\Writers\ODBC\SQLSrvODBCWriter;
use GenericDatabase\Helpers\Migrators\Writers\ODBC\FirebirdODBCWriter;
use GenericDatabase\Helpers\Migrators\Writers\ODBC\SQLiteODBCWriter;
use GenericDatabase\Helpers\Migrators\Writers\ODBC\ODBCWriter;

/**
 * WriterFactory — Strategy selector for target database writers.
 *
 * Three connection strategies are available:
 *
 *   WriterFactory::PDO      (default) — native PDO drivers (pdo_mysql, pdo_pgsql, …)
 *   WriterFactory::ODBC               — native ODBC via ext-odbc (odbc_connect / odbc_exec)
 *   WriterFactory::ODBCPDO            — ODBC via PDO over ODBC (pdo_odbc) (PDO with an ODBC DSN string)
 *
 * Usage:
 *
 * ```php
 * // PDO — default, no need to pass the second argument
 * $writer = WriterFactory::create('mysql');
 *
 * // Native ODBC (ext-odbc)
 * $writer = WriterFactory::create('mysql', WriterFactory::ODBC);
 * $writer->migrate($reader, 'demodev', [
 *     'host' => 'localhost', 'port' => 3306,
 *     'user' => 'root', 'password' => 'secret',
 *     'odbc_driver' => 'MySQL ODBC 8.0 Unicode Driver',
 * ]);
 *
 * // ODBC via PDO over ODBC (PDO with an ODBC DSN string)
 * $writer = WriterFactory::create('mysql', WriterFactory::ODBCPDO);
 * ```
 */
class WriterFactory
{
    /** Native PDO drivers: pdo_mysql, pdo_pgsql, pdo_sqlsrv, … (default) */
    public const PDO     = 'pdo';

    /** Native ODBC: odbc_connect / odbc_exec / odbc_prepare / odbc_execute (ext-odbc) */
    public const ODBC    = 'odbc';

    /** ODBC via PDO over ODBC: PDO with an ODBC DSN string (ext-pdo + ODBC driver) */
    public const ODBCPDO = 'odbcpdo';

    /**
     * Create the appropriate writer for the given engine and connection strategy.
     *
     * Supported engine keys:
     *   'mysql' | 'mariadb' | 'pgsql' | 'postgres' | 'oracle' | 'oci' |
     *   'sqlsrv' | 'mssql'  | 'firebird' | 'sqlite'
     *
     * For unlisted engines:
     *   PDO      → PDOWriter($engine, $dsnTpl)    (requires DSN template)
     *   ODBC     → ODBCWriter($engine)            (native ext-odbc)
     *   ODBCPDO  → ODBCPDOWriter($engine)         (PDO over ODBC with ODBC DSN string)
     *
     * @param string $engine  Target engine identifier (lowercase)
     * @param string $via     Connection strategy constant (default: WriterFactory::PDO)
     * @param string $dsnTpl  DSN template with {dbname} placeholder — used only when
     *                        engine is unrecognised and $via === PDO.
     * @return BasePDOWriter|BaseODBCWriter
     */
    public static function create(
        string $engine,
        string $via = self::PDO,
        string $dsnTpl = ''
    ): BasePDOWriter|BaseODBCWriter {
        $engine = strtolower($engine);

        // ── Native ODBC (ext-odbc) ──────────────────────────────────────────
        if ($via === self::ODBC) {
            return match ($engine) {
                'mysql', 'mariadb'  => new MySQLODBCWriter(),
                'pgsql', 'postgres' => new PgSQLODBCWriter(),
                'oracle', 'oci'     => new OCIODBCWriter(),
                'sqlsrv', 'mssql'   => new SQLSrvODBCWriter(),
                'firebird'          => new FirebirdODBCWriter(),
                'sqlite'            => new SQLiteODBCWriter(),
                default             => new ODBCWriter($engine),
            };
        }

        // ── ODBC via PDO over ODBC ───────────────────────────────────────────────
        if ($via === self::ODBCPDO) {
            return match ($engine) {
                'mysql', 'mariadb'  => new MySQLODBCPDOWriter(),
                'pgsql', 'postgres' => new PgSQLODBCPDOWriter(),
                'oracle', 'oci'     => new OCIODBCPDOWriter(),
                'sqlsrv', 'mssql'   => new SQLSrvODBCPDOWriter(),
                'firebird'          => new FirebirdODBCPDOWriter(),
                'sqlite'            => new SQLiteODBCPDOWriter(),
                default             => new ODBCPDOWriter($engine),
            };
        }

        // ── Native PDO (default) ────────────────────────────────────────────
        return match ($engine) {
            'mysql', 'mariadb'  => new MySQLPDOWriter(),
            'pgsql', 'postgres' => new PgSQLPDOWriter(),
            'oracle', 'oci'     => new OCIPDOWriter(),
            'sqlsrv', 'mssql'   => new SQLSrvPDOWriter(),
            'firebird'          => new FirebirdPDOWriter(),
            'sqlite'            => new SQLitePDOWriter(),
            default             => new PDOWriter($engine, $dsnTpl),
        };
    }
}
