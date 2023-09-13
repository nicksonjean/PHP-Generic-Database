<?php

namespace GenericDatabase\Core;

/**
 * The `GenericDatabase\Core\Entity` enum provides defines several constants
 * that represent different database engines and a database connection class.
 *
 * @package GenericDatabase\Core
 * @enum string
 */
enum Entity: string
{
    /**
     * The fully qualified class name of the database connection class.
     */
    case CLASS_CONNECTION = 'GenericDatabase\Connection';

    /**
     * The fully qualified class name of the PDO database engine class.
     */
    case CLASS_PDO_ENGINE = 'GenericDatabase\Engine\PDOEngine';

    /**
     * The fully qualified class name of the MySQLi database engine class.
     */
    case CLASS_MYSQLI_ENGINE = 'GenericDatabase\Engine\MySQLiEngine';

    /**
     * The fully qualified class name of the PgSQL database engine class.
     */
    case CLASS_PGSQL_ENGINE = 'GenericDatabase\Engine\PgSQLEngine';

    /**
     * The fully qualified class name of the Firebird database engine class.
     */
    case CLASS_FBIRD_ENGINE = 'GenericDatabase\Engine\FBirdEngine';

    /**
     * The fully qualified class name of the Oracle database engine class.
     */
    case CLASS_OCI_ENGINE = 'GenericDatabase\Engine\OCIEngine';

    /**
     * The fully qualified class name of the SQL Server database engine class.
     */
    case CLASS_SQLSRV_ENGINE = 'GenericDatabase\Engine\SQLSrvEngine';

    /**
     * The fully qualified class name of the SQLite database engine class.
     */
    case CLASS_SQLITE_ENGINE = 'GenericDatabase\Engine\SQLiteEngine';

    /**
     * The fully qualified class name of the internal classes databases.
     */
    case CASE_INTERNAL_CLASS = 'GenericDatabase\Engine\%s\%s::%s';
}
