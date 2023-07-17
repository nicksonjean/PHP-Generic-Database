<?php

namespace GenericDatabase\Helpers;

use PDO;
use MySQLi;
use PgSql\Connection;
use SQLite3;
use GenericDatabase\Helpers\ConnectionType as An;

class Compare
{
    public static function connection($cnx)
    {
        if (is_resource($cnx)) {
            return self::getResourceConnectionType($cnx);
        } elseif (
            $cnx instanceof PDO ||
            $cnx instanceof SQLite3 ||
            $cnx instanceof MySQLi ||
            $cnx instanceof Connection
        ) {
            return self::getObjectConnectionType($cnx);
        } else {
            return 'Tipo de conexão não identificado ou inválido.';
        }
    }

    private static function getResourceConnectionType($cnx)
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
            default => 'Tipo de conexão resource não identificado ou inválido.',
        };
    }

    private static function getObjectConnectionType($cnx)
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
            default => 'Tipo de conexão de objeto não identificado ou inválido.',
        };
    }
}
