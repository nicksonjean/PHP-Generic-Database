<?php

namespace GenericDatabase\Helpers;

/**
 * The ConnectionType enum represents different types of database connections and their corresponding names.
 *
 * This enum provides a set of predefined constants that represent various database connection types
 * and their associated human-readable names. It is designed to simplify the identification of database
 * connection types within the GenericDatabase library.
 *
 * @package GenericDatabase\Helpers
 * @enum string
 */
enum ConnectionType: string
{
    /**
     * Oracle OCI8 connection
     *
     * @var string
     */
    case NAT_OCI = 'oci8 connection';

    /**
     * Persistent Oracle OCI8 connection
     *
     * @var string
     */
    case NAT_OCI_PERSISTENT = 'oci8 persistent connection';

    /**
     * Persistent SQL Server connection
     *
     * @var string
     */
    case NAT_SQLSRV_PERSISTENT = 'SQL Server Connection';

    /**
     * MySQL link
     *
     * @var string
     */
    case NAT_MYSQL = 'mysql link';

    /**
     * PgSql Connection
     *
     * @var string
     */
    case NAT_PGSQL = 'PgSql\Connection';

    /**
     * Microsoft SQL Server link
     *
     * @var string
     */
    case NAT_MSSQL = 'mssql link';

    /**
     * Firebird link
     *
     * @var string
     */
    case NAT_FBIRD = 'Firebird link';

    /**
     * InterBase link
     *
     * @var string
     */
    case NAT_IBASE = 'InterBase link';

    /**
     * Persistent Firebird link
     *
     * @var string
     */
    case NAT_FBIRD_PERSISTENT = 'Firebird persistent link';

    /**
     * Persistent InterBase link
     *
     * @var string
     */
    case NAT_IBASE_PERSISTENT = 'InterBase persistent link';

    /**
     * Firebird/InterBase link
     *
     * @var string
     */
    case NAT_FBIRD_IBASE = 'Firebird/InterBase link';

    /**
     * Persistent Firebird/InterBase link
     *
     * @var string
     */
    case NAT_FBIRD_IBASE_PERSISTENT = 'Firebird/InterBase persistent link';

    /**
     * Sybase link
     *
     * @var string
     */
    case NAT_SYBASE = 'sybase link';

    /**
     * MySQLi extension
     *
     * @var string
     */
    case NAT_MYSQLI = 'mysqli';

    /**
     * SQLite3 extension
     *
     * @var string
     */
    case NAT_SQLITE = 'SQLite3';

    /**
     * PDO MySQL driver
     *
     * @var string
     */
    case PDO_MYSQL = 'mysql';

    /**
     * PDO PDO PgSQL driver
     *
     * @var string
     */
    case PDO_PGSQL = 'pgsql';

    /**
     * PDO SQLite driver
     *
     * @var string
     */
    case PDO_SQLITE = 'sqlite';

    /**
     * PDO SQL Server driver
     *
     * @var string
     */
    case PDO_SQLSRV = 'sqlsrv';

    /**
     * PDO Oracle driver
     *
     * @var string
     */
    case PDO_OCI = 'oci';

    /**
     * PDO Firebird driver
     *
     * @var string
     */
    case PDO_FBIRD = 'firebird';

    /**
     * PDO IBM driver
     *
     * @var string
     */
    case PDO_IBM = 'ibm';

    /**
     * PDO Informix driver
     *
     * @var string
     */
    case PDO_INFORMIX = 'informix';

    /**
     * PDO Microsoft SQL Server driver
     *
     * @var string
     */
    case PDO_MSSQL = 'mssql';

    /**
     * PDO DBLIB driver
     *
     * @var string
     */
    case PDO_DBLIB = 'dblib';

    /**
     * PDO ODBC driver
     *
     * @var string
     */
    case PDO_ODBC = 'odbc';
}
