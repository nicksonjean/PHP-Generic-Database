<?php

declare(strict_types=1);

namespace GenericDatabase\Runner;

use GenericDatabase\Connection;
use GenericDatabase\Engine\MySQLiEngine;
use GenericDatabase\Engine\PgSQLEngine;
use GenericDatabase\Engine\SQLSrvEngine;
use GenericDatabase\Engine\OCIEngine;
use GenericDatabase\Engine\FBirdEngine;
use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Engine\MySQLi\MySQL;
use GenericDatabase\Engine\PgSQL\PgSQL;
use GenericDatabase\Engine\SQLSrv\SQLSrv;
use GenericDatabase\Engine\OCI\OCI;
use GenericDatabase\Engine\FBird\FBird;
use GenericDatabase\Engine\SQLite\SQLite;
use GenericDatabase\Engine\PDOEngine;
use PDO;
use GenericDatabase\Helpers\Entity;

class Fluent
{
    public static function nativeMySQLi(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|MySQLiEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_MYSQLI_ENGINE;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'mysqli');
        }
        $instance = $className::setHost($env['MYSQL_HOST']);
        $instance::setPort((int)$env['MYSQL_PORT'])
            ::setDatabase($env['MYSQL_DATABASE'])
            ::setUser($env['MYSQL_USER'])
            ::setPassword($env['MYSQL_PASSWORD'])
            ::setCharset('utf8')
            ::setOptions([
                MySQL::ATTR_PERSISTENT => $persistent,
                MySQL::ATTR_AUTOCOMMIT => true,
                MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
                MySQL::ATTR_SET_CHARSET_NAME => "utf8",
                MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
                MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
                MySQL::ATTR_OPT_READ_TIMEOUT => 30,
                MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
            ])
            ::setException(true);

        return $instance;
    }

    public static function nativePgSQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PgSQLEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PGSQL_ENGINE;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pgsql');
        }
        $instance = $className::setHost($env['PGSQL_HOST']);
        $instance::setPort((int)$env['PGSQL_PORT'])
            ::setDatabase($env['PGSQL_DATABASE'])
            ::setUser($env['PGSQL_USER'])
            ::setPassword($env['PGSQL_PASSWORD'])
            ::setCharset('utf8')
            ::setOptions([
                PgSQL::ATTR_PERSISTENT => $persistent,
                PgSQL::ATTR_CONNECT_ASYNC => true,
                PgSQL::ATTR_CONNECT_FORCE_NEW => true,
                PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
            ])
            ::setException(true);

        return $instance;
    }

    public static function nativeSQLSrv(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|SQLSrvEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_SQLSRV_ENGINE;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'sqlsrv');
        }
        $instance = $className::setHost($env['SQLSRV_HOST']);
        $instance::setPort((int)$env['SQLSRV_PORT'])
            ::setPort((int)$env['SQLSRV_PORT'])
            ::setDatabase($env['SQLSRV_DATABASE'])
            ::setUser($env['SQLSRV_USER'])
            ::setPassword($env['SQLSRV_PASSWORD'])
            ::setCharset('utf8')
            ::setOptions([
                SQLSrv::ATTR_PERSISTENT => $persistent,
                SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
            ])
            ::setException(true);

        return $instance;
    }

    public static function nativeOCI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|OCIEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_OCI_ENGINE;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'oci');
        }
        $instance = $className::setHost($env['OCI_HOST']);
        $instance::setPort((int)$env['OCI_PORT'])
            ::setDatabase($env['OCI_DATABASE'])
            ::setUser($env['OCI_USER'])
            ::setPassword($env['OCI_PASSWORD'])
            ::setCharset('utf8')
            ::setOptions([
                OCI::ATTR_PERSISTENT => $persistent,
                OCI::ATTR_CONNECT_TIMEOUT => 28800,
            ])
            ::setException(true);

        return $instance;
    }

    public static function nativeFBird(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|FBirdEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_FBIRD_ENGINE;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'fbird');
        }
        $instance = $className::setHost($env['FBIRD_HOST']);
        $instance::setPort((int)$env['FBIRD_PORT'])
            ::setDatabase($env['FBIRD_DATABASE'])
            ::setUser($env['FBIRD_USER'])
            ::setPassword($env['FBIRD_PASSWORD'])
            ::setCharset('utf8')
            ::setOptions([
                FBird::ATTR_PERSISTENT => $persistent,
                FBird::ATTR_CONNECT_TIMEOUT => 28800,
            ])
            ::setException(true);

        return $instance;
    }

    public static function nativeSQLite(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|SQLiteEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_SQLITE_ENGINE;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'sqlite');
        }
        $instance = $className::setDatabase($env['SQLITE_DATABASE']);
        $instance::setCharset('utf8')
            ::setOptions([
                SQLite::ATTR_OPEN_READONLY => false,
                SQLite::ATTR_OPEN_READWRITE => true,
                SQLite::ATTR_OPEN_CREATE => true,
                SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                SQLite::ATTR_PERSISTENT => $persistent,
                SQLite::ATTR_AUTOCOMMIT => true
            ])
            ::setException(true);

        return $instance;
    }

    public static function nativeMemory(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|SQLiteEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_SQLITE_ENGINE;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'sqlite');
        }
        $instance = $className::setDatabase($env['SQLITE_DATABASE_MEMORY']);
        $instance::setCharset('utf8')
            ::setOptions([
                SQLite::ATTR_OPEN_READONLY => false,
                SQLite::ATTR_OPEN_READWRITE => true,
                SQLite::ATTR_OPEN_CREATE => true,
                SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                SQLite::ATTR_PERSISTENT => $persistent,
                SQLite::ATTR_AUTOCOMMIT => true
            ])
            ::setException(true);

        return $instance;
    }

    public static function pdoMySQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PDO_ENGINE;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        $instance = $className::setDriver('mysql');
        $instance::setHost($env['MYSQL_HOST'])
            ::setPort((int)$env['MYSQL_PORT'])
            ::setDatabase($env['MYSQL_DATABASE'])
            ::setUser($env['MYSQL_USER'])
            ::setPassword($env['MYSQL_PASSWORD'])
            ::setCharset('utf8')
            ::setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    public static function pdoPgSQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PDO_ENGINE;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        $instance = $className::setDriver('pgsql');
        $instance
            ::setHost($env['PGSQL_HOST'])
            ::setPort((int)$env['PGSQL_PORT'])
            ::setDatabase($env['PGSQL_DATABASE'])
            ::setUser($env['PGSQL_USER'])
            ::setPassword($env['PGSQL_PASSWORD'])
            ::setCharset('utf8')
            ::setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    public static function pdoSQLSrv(
        array $env,
        bool $strategy = false
    ): Connection|PDOEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PDO_ENGINE;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        $instance = $className::setDriver('sqlsrv');
        $instance
            ::setHost($env['SQLSRV_HOST'])
            ::setPort((int)$env['SQLSRV_PORT'])
            ::setDatabase($env['SQLSRV_DATABASE'])
            ::setUser($env['SQLSRV_USER'])
            ::setPassword($env['SQLSRV_PASSWORD'])
            ::setCharset('utf8')
            ::setOptions([
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    public static function pdoOCI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PDO_ENGINE;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        $instance = $className::setDriver('oci');
        $instance
            ::setHost($env['OCI_HOST'])
            ::setPort((int)$env['OCI_PORT'])
            ::setDatabase($env['OCI_DATABASE'])
            ::setUser($env['OCI_USER'])
            ::setPassword($env['OCI_PASSWORD'])
            ::setCharset('utf8')
            ::setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    public static function pdoFirebird(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PDO_ENGINE;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        $instance = $className::setDriver('firebird');
        $instance
            ::setHost($env['FBIRD_HOST'])
            ::setPort((int)$env['FBIRD_PORT'])
            ::setDatabase($env['FBIRD_DATABASE'])
            ::setUser($env['FBIRD_USER'])
            ::setPassword($env['FBIRD_PASSWORD'])
            ::setCharset('utf8')
            ::setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    public static function pdoSQLite(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PDO_ENGINE;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        $instance = $className::setDriver('sqlite');
        $instance
            ::setDatabase($env['SQLITE_DATABASE'])
            ::setCharset('utf8')
            ::setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    public static function pdoMemory(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PDO_ENGINE;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        $instance = $className::setDriver('sqlite');
        $instance
            ::setDatabase($env['SQLITE_DATABASE_MEMORY'])
            ::setCharset('utf8')
            ::setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }
}
