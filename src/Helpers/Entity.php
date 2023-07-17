<?php

namespace GenericDatabase\Helpers;

class Entity
{
    public const CLASS_CONNECTION = 'GenericDatabase\Connection';
    public const CLASS_PDO_ENGINE = 'GenericDatabase\Engine\PDOEngine';
    public const CLASS_MYSQLI_ENGINE = 'GenericDatabase\Engine\MySQLiEngine';
    public const CLASS_PGSQL_ENGINE = 'GenericDatabase\Engine\PgSQLEngine';
    public const CLASS_FBIRD_ENGINE = 'GenericDatabase\Engine\FBirdEngine';
    public const CLASS_OCI_ENGINE = 'GenericDatabase\Engine\OCIEngine';
    public const CLASS_SQLSRV_ENGINE = 'GenericDatabase\Engine\SQLSrvEngine';
    public const CLASS_SQLITE_ENGINE = 'GenericDatabase\Engine\SQLiteEngine';
}
