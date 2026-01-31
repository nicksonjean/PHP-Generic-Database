<?php

namespace GenericDatabase\Core\Native;

use Mabe\Enum\Cl\EnumBc;

/**
 * The `GenericDatabase\Core\Entity` enum provides defines several constants
 * that represent different database engines and a database connection class.
 *
 * @package GenericDatabase\Core
 * @enum string
 */
enum Entity: string
{
    use EnumBc;

    /**
     * The fully qualified class name of the database connection class.
     */
    case CLASS_CONNECTION = \GenericDatabase\Connection::class;

    /**
     * The fully qualified class name of the PDO database engine class.
     */
    case CLASS_PDO_ENGINE = \GenericDatabase\Engine\PDOConnection::class;

    /**
     * The fully qualified class name of the ODBC database engine class.
     */
    case CLASS_ODBC_ENGINE = \GenericDatabase\Engine\ODBCConnection::class;

    /**
     * The fully qualified class name of the MySQLi database engine class.
     */
    case CLASS_MYSQLI_ENGINE = \GenericDatabase\Engine\MySQLiConnection::class;

    /**
     * The fully qualified class name of the PgSQL database engine class.
     */
    case CLASS_PGSQL_ENGINE = \GenericDatabase\Engine\PgSQLConnection::class;

    /**
     * The fully qualified class name of the Firebird database engine class.
     */
    case CLASS_FIREBIRD_ENGINE = \GenericDatabase\Engine\FirebirdConnection::class;

    /**
     * The fully qualified class name of the Oracle database engine class.
     */
    case CLASS_OCI_ENGINE = \GenericDatabase\Engine\OCIConnection::class;

    /**
     * The fully qualified class name of the SQL Server database engine class.
     */
    case CLASS_SQLSRV_ENGINE = \GenericDatabase\Engine\SQLSrvConnection::class;

    /**
     * The fully qualified class name of the SQLite database engine class.
     */
    case CLASS_SQLITE_ENGINE = \GenericDatabase\Engine\SQLiteConnection::class;

    /**
     * The fully qualified class name of the JSON database engine class.
     */
    case CLASS_JSON_ENGINE = \GenericDatabase\Engine\JSONConnection::class;

    /**
     * The fully qualified class name of the INI database engine class.
     */
    case CLASS_INI_ENGINE = \GenericDatabase\Engine\INIConnection::class;

    /**
     * The fully qualified class name of the YAML database engine class.
     */
    case CLASS_YAML_ENGINE = \GenericDatabase\Engine\YAMLConnection::class;

    /**
     * The fully qualified class name of the XML database engine class.
     */
    case CLASS_XML_ENGINE = \GenericDatabase\Engine\XMLConnection::class;

    /**
     * The fully qualified class name of the NEON database engine class.
     */
    case CLASS_NEON_ENGINE = \GenericDatabase\Engine\NEONConnection::class;

    /**
     * The fully qualified class name of the CSV database engine class.
     */
    case CLASS_CSV_ENGINE = \GenericDatabase\Engine\CSVConnection::class;

    /**
     * The fully qualified class name of the internal classes databases.
     */
    case CASE_INTERNAL_CLASS = 'GenericDatabase\Engine\%s\Connection\%s::%s';

    /**
     * The fully qualified arguments class name of the internal classes databases.
     */
    case CASE_ARGUMENT_CLASS = 'GenericDatabase\Engine\%s\Connection\Arguments\ArgumentsHandler';
}
