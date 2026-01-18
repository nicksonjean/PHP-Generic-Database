<?php

namespace GenericDatabase\Core\Emulated;

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
    public const NAT_OCI = 'oci8 connection';

    /**
     * Persistent Oracle OCI8 connection
     */
    public const NAT_OCI_PERSISTENT = 'oci8 persistent connection';

    /**
     * Persistent SQL Server connection
     */
    public const NAT_SQLSRV_PERSISTENT = 'SQL Server Connection';

    /**
     * MySQL link
     */
    public const NAT_MYSQL = 'mysql link';

    /**
     * PgSql Connection
     */
    public const NAT_PGSQL = \PgSql\Connection::class;

    /**
     * Microsoft SQL Server link
     */
    public const NAT_MSSQL = 'mssql link';

    /**
     * Firebird link
     */
    public const NAT_FIREBIRD = 'Firebird link';

    /**
     * InterBase link
     */
    public const NAT_INTERBASE = 'InterBase link';

    /**
     * Persistent Firebird link
     */
    public const NAT_FIREBIRD_PERSISTENT = 'Firebird persistent link';

    /**
     * Persistent InterBase link
     */
    public const NAT_INTERBASE_PERSISTENT = 'InterBase persistent link';

    /**
     * Firebird/InterBase link
     */
    public const NAT_FIREBIRD_INTERBASE = 'Firebird/InterBase link';

    /**
     * Persistent Firebird/InterBase link
     */
    public const NAT_FIREBIRD_INTERBASE_PERSISTENT = 'Firebird/InterBase persistent link';

    /**
     * MySQL link
     */
    public const NAT_ODBC = 'odbc link';

    /**
     * ODBC extension
     */
    public const NAT_ODBC_PERSISTENT = 'odbc link persistent';

    /**
     * Sybase link
     */
    public const NAT_SYBASE = 'sybase link';

    /**
     * MySQLi extension
     */
    public const NAT_MYSQLI = 'mysqli';

    /**
     * SQLite3 extension
     */
    public const NAT_SQLITE = 'SQLite3';

    /**
     * PDO MySQL driver
     */
    public const PDO_MYSQL = 'mysql';

    /**
     * PDO PDO PgSQL driver
     */
    public const PDO_PGSQL = 'pgsql';

    /**
     * PDO SQLite driver
     */
    public const PDO_SQLITE = 'sqlite';

    /**
     * PDO SQL Server driver
     */
    public const PDO_SQLSRV = 'sqlsrv';

    /**
     * PDO Oracle driver
     */
    public const PDO_OCI = 'oci';

    /**
     * PDO Firebird driver
     */
    public const PDO_FIREBIRD = 'firebird';

    /**
     * PDO IBM driver
     */
    public const PDO_IBM = 'ibm';

    /**
     * PDO Informix driver
     */
    public const PDO_INFORMIX = 'informix';

    /**
     * PDO Microsoft SQL Server driver
     */
    public const PDO_MSSQL = 'mssql';

    /**
     * PDO DBLIB driver
     */
    public const PDO_DBLIB = 'dblib';

    /**
     * PDO ODBC driver
     */
    public const PDO_ODBC = 'odbc';

    /**
     * PgSql link
     */
    public const NAT_PGSQL_LINK = 'pgsql link';

    /**
     * PgSql link persistent
     */
    public const NAT_PGSQL_LINK_PERSISTENT = 'pgsql link persistent';
}
