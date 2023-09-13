<?php

namespace GenericDatabase\Helpers;

use PDO;
use MySQLi;
use SQLite3;
use GenericDatabase\Connection as CNX;
use PgSQL\Connection as PGCNX;
use GenericDatabase\Core\Types as An;

/**
 * The `GenericDatabase\Helpers\Compare` class provides methods
 * for determining the type of given database connection.
 * This class can identify the type of database connection
 * whether it is a resource connection or an object connection.
 *
 * The code snippet is a part of the Compare class, and it contains
 * two private methods: getResourceConnectionType and getObjectConnectionType.
 * These methods are used to determine the type of given database connection,
 * whether it is a resource connection or an object connection.
 *
 * Example Usage:
 *
 * <code>
 * //Using a resource-based database connection (e.g., OCI)
 * $ociConnection = oci_connect("username", "password", "localhost/xe");
 * $type = Compare::connection($ociConnection);
 * echo "Database type: $type";
 * </code>
 * `Output: Database type: oci`
 *
 * <code>
 * //Using an object-based database connection (e.g., PDO for MySQL)
 * $pdoConnection = new PDO("mysql:host=localhost;dbname=mydatabase", "username", "password");
 * $type = Compare::connection($pdoConnection);
 * echo "Database type: $type";
 * </code>
 * `Output: Database type: PDO mysql`
 *
 * <code>
 * //Using an unknown or invalid connection
 * $invalidConnection = "invalid_connection_string";
 * $type = Compare::connection($invalidConnection);
 * echo "Database type: $type";
 * </code>
 * `Output: Database type: Unidentified or invalid connection type.`
 *
 * Main functionalities:
 * - Determines the type of database connection, whether it is a resource or an object connection.
 * - Handles various types of database connections, including PDO, MySQLi, SQLite3, and custom connection classes.
 *
 * Methods:
 * - `connection($cnx): string`:
 * Determines the type of the given connection.
 * It accepts either a resource or an object connection and returns a string representing the connection type.
 *
 * @package GenericDatabase\Helpers
 */
class Compare
{
    /**
     * Determines the type of given database connection.
     *
     * @param resource|object $cnx The database connection.
     * @return string The type of the database connection.
     */
    public static function connection(mixed $cnx): string
    {
        if (is_resource($cnx)) {
            return self::getResourceConnectionType($cnx);
        } elseif (
            $cnx instanceof PDO ||
            $cnx instanceof SQLite3 ||
            $cnx instanceof MySQLi ||
            $cnx instanceof CNX ||
            $cnx instanceof PGCNX
        ) {
            return self::getObjectConnectionType($cnx);
        } else {
            return 'Unidentified or invalid connection type.';
        }
    }

    /**
     * Determines the type of resource connection.
     *
     * @param resource $cnx The resource connection.
     * @return string The type of the resource connection.
     */
    private static function getResourceConnectionType($cnx): string
    {
        $its = fn ($name) => $name->value;
        return match (true) {
            is_resource($cnx) && get_resource_type($cnx) === $its(An::NAT_OCI) => 'oci',
            is_resource($cnx) && get_resource_type($cnx) === $its(An::NAT_OCI_PERSISTENT) => 'oci',
            is_resource($cnx) && get_resource_type($cnx) === $its(An::NAT_SQLSRV_PERSISTENT) => 'sqlsrv',
            is_resource($cnx) && get_resource_type($cnx) === $its(An::NAT_MYSQL) => 'mysql',
            is_resource($cnx) && get_resource_type($cnx) === $its(An::NAT_MSSQL) => 'mssql',
            is_resource($cnx) && get_resource_type($cnx) === $its(An::NAT_SYBASE) => 'sybase',
            is_resource($cnx) && get_resource_type($cnx) === $its(An::NAT_FBIRD) => 'fbird',
            is_resource($cnx) && get_resource_type($cnx) === $its(An::NAT_IBASE) => 'ibase',
            is_resource($cnx) && get_resource_type($cnx) === $its(An::NAT_FBIRD_PERSISTENT)
                || $its(An::NAT_IBASE_PERSISTENT) => 'fbird/ibase',
            is_resource($cnx) && get_resource_type($cnx) === $its(An::NAT_FBIRD_IBASE)
                || $its(An::NAT_FBIRD_IBASE_PERSISTENT) => 'fbird/ibase',
            default => 'Unidentified or invalid connection type, instance or resource.',
        };
    }

    /**
     * Determines the type of object connection.
     *
     * @param object $cnx The object connection.
     * @return string The type of the object connection.
     */
    private static function getObjectConnectionType(object $cnx): string
    {
        $its = fn ($name) => $name->value;
        $attr = fn ($cnx) => $cnx->getAttribute(PDO::ATTR_DRIVER_NAME);
        return match (true) {
            is_a($cnx, $its(An::NAT_MYSQLI)) && get_class($cnx) === $its(An::NAT_MYSQLI) => 'mysqli',
            is_a($cnx, $its(An::NAT_SQLITE)) && get_class($cnx) === $its(An::NAT_SQLITE) => 'sqlite',
            is_a($cnx, $its(An::NAT_PGSQL)) && get_class($cnx) === $its(An::NAT_PGSQL) => 'pgsql',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $attr($cnx) === $its(An::PDO_MYSQL) => 'PDO mysql',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $attr($cnx) === $its(An::PDO_PGSQL) => 'PDO pgsql',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $attr($cnx) === $its(An::PDO_SQLITE) => 'PDO sqlite',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $attr($cnx) === $its(An::PDO_SQLSRV) => 'PDO sqlsrv',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $attr($cnx) === $its(An::PDO_OCI) => 'PDO oci',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $attr($cnx) === $its(An::PDO_FBIRD) => 'PDO firebird',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $attr($cnx) === $its(An::PDO_IBM) => 'PDO ibm',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $attr($cnx) === $its(An::PDO_INFORMIX) => 'PDO informix',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $attr($cnx) === $its(An::PDO_MSSQL) => 'PDO mssql',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $attr($cnx) === $its(An::PDO_DBLIB) => 'PDO dblib',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $attr($cnx) === $its(An::PDO_ODBC) => 'PDO odbc',
            default => 'Unidentified or invalid object connection type.',
        };
    }
}
