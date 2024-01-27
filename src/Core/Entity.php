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
    case CLASS_CONNECTION = \GenericDatabase\Connection::class;

    /**
     * The fully qualified class name of the PDO database engine class.
     */
    case CLASS_PDO_ENGINE = \GenericDatabase\Engine\PDOEngine::class;

    /**
     * The fully qualified class name of the MySQLi database engine class.
     */
    case CLASS_MYSQLI_ENGINE = \GenericDatabase\Engine\MySQLiEngine::class;

    /**
     * The fully qualified class name of the PgSQL database engine class.
     */
    case CLASS_PGSQL_ENGINE = \GenericDatabase\Engine\PgSQLEngine::class;

    /**
     * The fully qualified class name of the Firebird database engine class.
     */
    case CLASS_FBIRD_ENGINE = \GenericDatabase\Engine\FBirdEngine::class;

    /**
     * The fully qualified class name of the Oracle database engine class.
     */
    case CLASS_OCI_ENGINE = \GenericDatabase\Engine\OCIEngine::class;

    /**
     * The fully qualified class name of the SQL Server database engine class.
     */
    case CLASS_SQLSRV_ENGINE = \GenericDatabase\Engine\SQLSrvEngine::class;

    /**
     * The fully qualified class name of the SQLite database engine class.
     */
    case CLASS_SQLITE_ENGINE = \GenericDatabase\Engine\SQLiteEngine::class;

    /**
     * The fully qualified class name of the internal classes databases.
     */
    case CASE_INTERNAL_CLASS = 'GenericDatabase\Engine\%s\%s::%s';

    /**
     * The fully qualified arguments class name of the internal classes databases.
     */
    case CASE_ARGUMENT_CLASS = 'GenericDatabase\Engine\%s\Arguments';
}
