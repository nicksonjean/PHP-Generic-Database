<?php

namespace GenericDatabase\Helpers;

use PDO;
use MySQLi;
use SQLite3;
use GenericDatabase\Connection as CNX;
use PgSQL\Connection as PgCNX;
use GenericDatabase\Core\Types as An;

/**
 * The `GenericDatabase\Helpers\Compare` class provides methods for determining the type of given
 * database connection this class can identify the type of database connection whether it is a
 * resource connection or an object connection, and it can also determine the type of database
 * connection whether it is a resource connection or an object connection.
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
 * $pdoConnection = new PDO("mysql:host=localhost;dbname=database", "username", "password");
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
 * - `connection($cnx): string`: Determines the type of the given connection. It accepts either a resource or an object connection and returns a string representing the connection type.
 * - `getResourceConnectionType($cnx): string`: Determines the type of a resource-based database connection.
 * - `getObjectConnectionType($cnx): string`: Determines the type of an object-based database connection.
 *
 * @package GenericDatabase\Helpers
 * @subpackage Compare
 */
class Compare
{
    /**
     * Determines the type of the given connection. It accepts either a resource or an object connection and returns a string representing the connection type.
     *
     * @param resource|object $cnx The database connection.
     * @return string The type of the database connection.
     */
    public static function connection(mixed $cnx): string
    {
        if (is_resource($cnx)) {
            return self::getResourceConnectionType($cnx);
        } elseif (is_object($cnx)) {
            if (PHP_VERSION_ID >= 80400 && get_class($cnx) === 'Odbc\Connection') {
                return 'odbc';
            }
            if ($cnx instanceof PDO || $cnx instanceof SQLite3 || $cnx instanceof MySQLi || $cnx instanceof CNX || $cnx instanceof PgCNX) {
                return self::getObjectConnectionType($cnx);
            }
        }
        return 'Unidentified or invalid connection type.';
    }

    /**
     * Determines the type of resource connection.
     *
     * @param resource $cnx The resource connection.
     * @return string The type of the resource connection.
     */
    private static function getResourceConnectionType($cnx): string
    {
        $getClassName = function ($name) {
            return is_object($name) && property_exists($name, 'value') ? $name->value : (string) $name;
        };

        return match (true) {
            is_resource($cnx) && get_resource_type($cnx) === $getClassName(An::NAT_ODBC) => 'odbc',
            is_resource($cnx) && get_resource_type($cnx) === $getClassName(An::NAT_ODBC_PERSISTENT) => 'odbc',
            is_resource($cnx) && get_resource_type($cnx) === $getClassName(An::NAT_OCI) => 'oci',
            is_resource($cnx) && get_resource_type($cnx) === $getClassName(An::NAT_OCI_PERSISTENT) => 'oci',
            is_resource($cnx) && get_resource_type($cnx) === $getClassName(An::NAT_SQLSRV_PERSISTENT) => 'sqlsrv',
            is_resource($cnx) && get_resource_type($cnx) === $getClassName(An::NAT_MYSQL) => 'mysql',
            is_resource($cnx) && get_resource_type($cnx) === $getClassName(An::NAT_MSSQL) => 'mssql',
            is_resource($cnx) && get_resource_type($cnx) === $getClassName(An::NAT_SYBASE) => 'sybase',
            is_resource($cnx) && get_resource_type($cnx) === $getClassName(An::NAT_FIREBIRD) => 'firebird',
            is_resource($cnx) && get_resource_type($cnx) === $getClassName(An::NAT_INTERBASE) => 'ibase',
            is_resource($cnx) && (get_resource_type($cnx) === $getClassName(An::NAT_FIREBIRD_PERSISTENT) || get_resource_type($cnx) === $getClassName(An::NAT_INTERBASE_PERSISTENT)) => 'firebird/ibase',
            is_resource($cnx) && (get_resource_type($cnx) === $getClassName(An::NAT_FIREBIRD_INTERBASE) || get_resource_type($cnx) === $getClassName(An::NAT_FIREBIRD_INTERBASE_PERSISTENT)) => 'firebird/ibase',
            is_resource($cnx) && get_resource_type($cnx) === $getClassName(An::NAT_PGSQL_LINK) => 'pgsql',
            is_resource($cnx) && get_resource_type($cnx) === $getClassName(An::NAT_PGSQL_LINK_PERSISTENT) => 'pgsql',
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
        $getClassName = function ($name) {
            return is_object($name) && property_exists($name, 'value') ? $name->value : (string) $name;
        };

        $getPdoDriver = function ($cnx) {
            return $cnx instanceof PDO ? $cnx->getAttribute(PDO::ATTR_DRIVER_NAME) : null;
        };

        return match (true) {
            is_a($cnx, $getClassName(An::NAT_MYSQLI)) && get_class($cnx) === $getClassName(An::NAT_MYSQLI) => 'mysqli',
            is_a($cnx, $getClassName(An::NAT_SQLITE)) && get_class($cnx) === $getClassName(An::NAT_SQLITE) => 'sqlite',
            is_a($cnx, $getClassName(An::NAT_PGSQL)) && get_class($cnx) === $getClassName(An::NAT_PGSQL) => 'pgsql',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $getPdoDriver($cnx) === $getClassName(An::PDO_MYSQL) => 'PDO mysql',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $getPdoDriver($cnx) === $getClassName(An::PDO_PGSQL) => 'PDO pgsql',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $getPdoDriver($cnx) === $getClassName(An::PDO_SQLITE) => 'PDO sqlite',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $getPdoDriver($cnx) === $getClassName(An::PDO_SQLSRV) => 'PDO sqlsrv',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $getPdoDriver($cnx) === $getClassName(An::PDO_OCI) => 'PDO oci',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $getPdoDriver($cnx) === $getClassName(An::PDO_FIREBIRD) => 'PDO firebird',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $getPdoDriver($cnx) === $getClassName(An::PDO_IBM) => 'PDO ibm',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $getPdoDriver($cnx) === $getClassName(An::PDO_INFORMIX) => 'PDO informix',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $getPdoDriver($cnx) === $getClassName(An::PDO_MSSQL) => 'PDO mssql',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $getPdoDriver($cnx) === $getClassName(An::PDO_DBLIB) => 'PDO dblib',
            is_a($cnx, 'PDO') && get_class($cnx) === 'PDO' && $getPdoDriver($cnx) === $getClassName(An::PDO_ODBC) => 'PDO odbc',
            default => 'Unidentified or invalid object connection type.',
        };
    }
}
