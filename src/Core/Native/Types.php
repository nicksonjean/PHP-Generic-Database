<?php

namespace GenericDatabase\Core\Native;

use Mabe\Enum\Cl\EnumBc;

/**
 * The `GenericDatabase\Core\Types` enum represents different types of database connections and their
 * corresponding names. This enum provides a set of predefined constants that represent various database
 * connection types  and their associated human-readable names. It is designed to simplify the identification
 * of database connection types within the GenericDatabase library.
 *
 * @package GenericDatabase\Core
 * @enum string
 */
enum Types: string
{
    use EnumBc;

    /**
     * Oracle OCI8 connection
     */
    case NAT_OCI = 'oci8 connection';

    /**
     * Persistent Oracle OCI8 connection
     */
    case NAT_OCI_PERSISTENT = 'oci8 persistent connection';

    /**
     * Persistent SQL Server connection
     */
    case NAT_SQLSRV_PERSISTENT = 'SQL Server Connection';

    /**
     * MySQL link
     */
    case NAT_MYSQL = 'mysql link';

    /**
     * PgSql Connection
     */
    case NAT_PGSQL = \PgSql\Connection::class;

    /**
     * Microsoft SQL Server link
     */
    case NAT_MSSQL = 'mssql link';

    /**
     * Firebird link
     */
    case NAT_FIREBIRD = 'Firebird link';

    /**
     * InterBase link
     */
    case NAT_INTERBASE = 'InterBase link';

    /**
     * Persistent Firebird link
     */
    case NAT_FIREBIRD_PERSISTENT = 'Firebird persistent link';

    /**
     * Persistent InterBase link
     */
    case NAT_INTERBASE_PERSISTENT = 'InterBase persistent link';

    /**
     * Firebird/InterBase link
     */
    case NAT_FIREBIRD_INTERBASE = 'Firebird/InterBase link';

    /**
     * Persistent Firebird/InterBase link
     */
    case NAT_FIREBIRD_INTERBASE_PERSISTENT = 'Firebird/InterBase persistent link';

    /**
     * MySQL link
     */
    case NAT_ODBC = 'odbc link';

    /**
     * ODBC extension
     */
    case NAT_ODBC_PERSISTENT = 'odbc link persistent';

    /**
     * Sybase link
     */
    case NAT_SYBASE = 'sybase link';

    /**
     * MySQLi extension
     */
    case NAT_MYSQLI = 'mysqli';

    /**
     * SQLite3 extension
     */
    case NAT_SQLITE = 'SQLite3';

    /**
     * PDO MySQL driver
     */
    case PDO_MYSQL = 'mysql';

    /**
     * PDO PDO PgSQL driver
     */
    case PDO_PGSQL = 'pgsql';

    /**
     * PDO SQLite driver
     */
    case PDO_SQLITE = 'sqlite';

    /**
     * PDO SQL Server driver
     */
    case PDO_SQLSRV = 'sqlsrv';

    /**
     * PDO Oracle driver
     */
    case PDO_OCI = 'oci';

    /**
     * PDO Firebird driver
     */
    case PDO_FIREBIRD = 'firebird';

    /**
     * PDO IBM driver
     */
    case PDO_IBM = 'ibm';

    /**
     * PDO Informix driver
     */
    case PDO_INFORMIX = 'informix';

    /**
     * PDO Microsoft SQL Server driver
     */
    case PDO_MSSQL = 'mssql';

    /**
     * PDO DBLIB driver
     */
    case PDO_DBLIB = 'dblib';

    /**
     * PDO ODBC driver
     */
    case PDO_ODBC = 'odbc';

    /**
     * PgSql link
     */
    case NAT_PGSQL_LINK = 'pgsql link';

    /**
     * PgSql link persistent
     */
    case NAT_PGSQL_LINK_PERSISTENT = 'pgsql link persistent';
}
