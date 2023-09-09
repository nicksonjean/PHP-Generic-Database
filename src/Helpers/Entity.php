<?php

namespace GenericDatabase\Helpers;

/**
 * The `GenericDatabase\Helpers\Entity` class provides defines several constants
 * that represent different database engines and a database connection class.
 *
 * @package GenericDatabase\Helpers
 */
class Entity
{
    /**
     * The fully qualified class name of the database connection class.
     *
     * @var string
     */
    public const CLASS_CONNECTION = 'GenericDatabase\Connection';

    /**
     * The fully qualified class name of the PDO database engine class.
     *
     * @var string
     */
    public const CLASS_PDO_ENGINE = 'GenericDatabase\Engine\PDOEngine';

    /**
     * The fully qualified class name of the MySQLi database engine class.
     *
     * @var string
     */
    public const CLASS_MYSQLI_ENGINE = 'GenericDatabase\Engine\MySQLiEngine';

    /**
     * The fully qualified class name of the PgSQL database engine class.
     *
     * @var string
     */
    public const CLASS_PGSQL_ENGINE = 'GenericDatabase\Engine\PgSQLEngine';

    /**
     * The fully qualified class name of the Firebird database engine class.
     *
     * @var string
     */
    public const CLASS_FBIRD_ENGINE = 'GenericDatabase\Engine\FBirdEngine';

    /**
     * The fully qualified class name of the Oracle database engine class.
     *
     * @var string
     */
    public const CLASS_OCI_ENGINE = 'GenericDatabase\Engine\OCIEngine';

    /**
     * The fully qualified class name of the SQL Server database engine class.
     *
     * @var string
     */
    public const CLASS_SQLSRV_ENGINE = 'GenericDatabase\Engine\SQLSrvEngine';

    /**
     * The fully qualified class name of the SQLite database engine class.
     *
     * @var string
     */
    public const CLASS_SQLITE_ENGINE = 'GenericDatabase\Engine\SQLiteEngine';
}
