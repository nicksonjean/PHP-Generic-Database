<?php

namespace GenericDatabase\Core;

use Mabe\Enum\Cl\EmulatedStringEnum;

/**
 * The `GenericDatabase\Core\Types` enum represents different types of database connections and their
 * corresponding names. This enum provides a set of predefined constants that represent various database
 * connection types  and their associated human-readable names. It is designed to simplify the identification
 * of database connection types within the GenericDatabase library.
 *
 * @package GenericDatabase\Core
 * @enum string
 */

final class Types extends EmulatedStringEnum
{
    /**
     * Oracle OCI8 connection
     */
    protected const NAT_OCI = 'oci8 connection';

    /**
     * Persistent Oracle OCI8 connection
     */
    protected const NAT_OCI_PERSISTENT = 'oci8 persistent connection';

    /**
     * Persistent SQL Server connection
     */
    protected const NAT_SQLSRV_PERSISTENT = 'SQL Server Connection';

    /**
     * MySQL link
     */
    protected const NAT_MYSQL = 'mysql link';

    /**
     * PgSql Connection
     */
    protected const NAT_PGSQL = \PgSql\Connection::class;

    /**
     * Microsoft SQL Server link
     */
    protected const NAT_MSSQL = 'mssql link';

    /**
     * Firebird link
     */
    protected const NAT_FIREBIRD = 'Firebird link';

    /**
     * InterBase link
     */
    protected const NAT_INTERBASE = 'InterBase link';

    /**
     * Persistent Firebird link
     */
    protected const NAT_FIREBIRD_PERSISTENT = 'Firebird persistent link';

    /**
     * Persistent InterBase link
     */
    protected const NAT_INTERBASE_PERSISTENT = 'InterBase persistent link';

    /**
     * Firebird/InterBase link
     */
    protected const NAT_FIREBIRD_INTERBASE = 'Firebird/InterBase link';

    /**
     * Persistent Firebird/InterBase link
     */
    protected const NAT_FIREBIRD_INTERBASE_PERSISTENT = 'Firebird/InterBase persistent link';

    /**
     * MySQL link
     */
    protected const NAT_ODBC = 'odbc link';

    /**
     * ODBC extension
     */
    protected const NAT_ODBC_PERSISTENT = 'odbc link persistent';

    /**
     * Sybase link
     */
    protected const NAT_SYBASE = 'sybase link';

    /**
     * MySQLi extension
     */
    protected const NAT_MYSQLI = 'mysqli';

    /**
     * SQLite3 extension
     */
    protected const NAT_SQLITE = 'SQLite3';

    /**
     * PDO MySQL driver
     */
    protected const PDO_MYSQL = 'mysql';

    /**
     * PDO PDO PgSQL driver
     */
    protected const PDO_PGSQL = 'pgsql';

    /**
     * PDO SQLite driver
     */
    protected const PDO_SQLITE = 'sqlite';

    /**
     * PDO SQL Server driver
     */
    protected const PDO_SQLSRV = 'sqlsrv';

    /**
     * PDO Oracle driver
     */
    protected const PDO_OCI = 'oci';

    /**
     * PDO Firebird driver
     */
    protected const PDO_FIREBIRD = 'firebird';

    /**
     * PDO IBM driver
     */
    protected const PDO_IBM = 'ibm';

    /**
     * PDO Informix driver
     */
    protected const PDO_INFORMIX = 'informix';

    /**
     * PDO Microsoft SQL Server driver
     */
    protected const PDO_MSSQL = 'mssql';

    /**
     * PDO DBLIB driver
     */
    protected const PDO_DBLIB = 'dblib';

    /**
     * PDO ODBC driver
     */
    protected const PDO_ODBC = 'odbc';
}
