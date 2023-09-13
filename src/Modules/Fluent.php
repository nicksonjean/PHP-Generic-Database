<?php

declare(strict_types=1);

namespace GenericDatabase\Modules;

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
use GenericDatabase\Core\Entity;
use PDO;

class Fluent
{
    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|MySQLiEngine
     */
    public static function nativeMySQLi(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|MySQLiEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_MYSQLI_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'mysqli');
        }
        /** @var Connection|MySQLiEngine $className */
        $instance = $className::setHost($env['MYSQL_HOST']);
        $instance::setPort((int)$env['MYSQL_PORT'])
            ::setDatabase($env['MYSQL_DATABASE'])
            ::setUser($env['MYSQL_USER'])
            ::setPassword($env['MYSQL_PASSWORD'])
            ::setCharset($env['MYSQL_CHARSET'])
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

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|PgSQLEngine
     */
    public static function nativePgSQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PgSQLEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PGSQL_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pgsql');
        }
        /** @var Connection|PgSQLEngine $className */
        $instance = $className::setHost($env['PGSQL_HOST']);
        $instance::setPort((int)$env['PGSQL_PORT'])
            ::setDatabase($env['PGSQL_DATABASE'])
            ::setUser($env['PGSQL_USER'])
            ::setPassword($env['PGSQL_PASSWORD'])
            ::setCharset($env['PGSQL_CHARSET'])
            ::setOptions([
                PgSQL::ATTR_PERSISTENT => $persistent,
                PgSQL::ATTR_CONNECT_ASYNC => true,
                PgSQL::ATTR_CONNECT_FORCE_NEW => true,
                PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|SQLSrvEngine
     */
    public static function nativeSQLSrv(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|SQLSrvEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLSRV_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'sqlsrv');
        }
        /** @var Connection|SQLSrvEngine $className */
        $instance = $className::setHost($env['SQLSRV_HOST']);
        $instance::setPort((int)$env['SQLSRV_PORT'])
            ::setPort((int)$env['SQLSRV_PORT'])
            ::setDatabase($env['SQLSRV_DATABASE'])
            ::setUser($env['SQLSRV_USER'])
            ::setPassword($env['SQLSRV_PASSWORD'])
            ::setCharset($env['SQLSRV_CHARSET'])
            ::setOptions([
                SQLSrv::ATTR_PERSISTENT => $persistent,
                SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|OCIEngine
     */
    public static function nativeOCI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|OCIEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_OCI_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'oci');
        }
        /** @var Connection|OCIEngine $className */
        $instance = $className::setHost($env['OCI_HOST']);
        $instance::setPort((int)$env['OCI_PORT'])
            ::setDatabase($env['OCI_DATABASE'])
            ::setUser($env['OCI_USER'])
            ::setPassword($env['OCI_PASSWORD'])
            ::setCharset($env['OCI_CHARSET'])
            ::setOptions([
                OCI::ATTR_PERSISTENT => $persistent,
                OCI::ATTR_CONNECT_TIMEOUT => 28800,
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|FBirdEngine
     */
    public static function nativeFBird(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|FBirdEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_FBIRD_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'fbird');
        }
        /** @var Connection|FBirdEngine $className */
        $instance = $className::setHost($env['FBIRD_HOST']);
        $instance::setPort((int)$env['FBIRD_PORT'])
            ::setDatabase($env['FBIRD_DATABASE'])
            ::setUser($env['FBIRD_USER'])
            ::setPassword($env['FBIRD_PASSWORD'])
            ::setCharset($env['FBIRD_CHARSET'])
            ::setOptions([
                FBird::ATTR_PERSISTENT => $persistent,
                FBird::ATTR_CONNECT_TIMEOUT => 28800,
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|SQLiteEngine
     */
    public static function nativeSQLite(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|SQLiteEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLITE_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'sqlite');
        }
        /** @var Connection|SQLiteEngine $className */
        $instance = $className::setDatabase($env['SQLITE_DATABASE']);
        $instance::setCharset($env['SQLITE_CHARSET'])
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

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|SQLiteEngine
     */
    public static function nativeMemory(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|SQLiteEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLITE_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'sqlite');
        }
        /** @var Connection|SQLiteEngine $className */
        $instance = $className::setDatabase($env['SQLITE_DATABASE_MEMORY']);
        $instance::setCharset($env['SQLITE_CHARSET'])
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

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|PDOEngine
     */
    public static function pdoMySQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOEngine $className */
        $instance = $className::setDriver('mysql');
        $instance::setHost($env['MYSQL_HOST'])
            ::setPort((int)$env['MYSQL_PORT'])
            ::setDatabase($env['MYSQL_DATABASE'])
            ::setUser($env['MYSQL_USER'])
            ::setPassword($env['MYSQL_PASSWORD'])
            ::setCharset($env['MYSQL_CHARSET'])
            ::setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|PDOEngine
     */
    public static function pdoPgSQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOEngine $className */
        $instance = $className::setDriver('pgsql');
        $instance
            ::setHost($env['PGSQL_HOST'])
            ::setPort((int)$env['PGSQL_PORT'])
            ::setDatabase($env['PGSQL_DATABASE'])
            ::setUser($env['PGSQL_USER'])
            ::setPassword($env['PGSQL_PASSWORD'])
            ::setCharset($env['PGSQL_CHARSET'])
            ::setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $strategy
     * @return Connection|PDOEngine
     */
    public static function pdoSQLSrv(
        array $env,
        bool $strategy = false
    ): Connection|PDOEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOEngine $className */
        $instance = $className::setDriver('sqlsrv');
        $instance
            ::setHost($env['SQLSRV_HOST'])
            ::setPort((int)$env['SQLSRV_PORT'])
            ::setDatabase($env['SQLSRV_DATABASE'])
            ::setUser($env['SQLSRV_USER'])
            ::setPassword($env['SQLSRV_PASSWORD'])
            ::setCharset($env['SQLSRV_CHARSET'])
            ::setOptions([
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|PDOEngine
     */
    public static function pdoOCI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOEngine $className */
        $instance = $className::setDriver('oci');
        $instance
            ::setHost($env['OCI_HOST'])
            ::setPort((int)$env['OCI_PORT'])
            ::setDatabase($env['OCI_DATABASE'])
            ::setUser($env['OCI_USER'])
            ::setPassword($env['OCI_PASSWORD'])
            ::setCharset($env['OCI_CHARSET'])
            ::setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|PDOEngine
     */
    public static function pdoFirebird(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOEngine $className */
        $instance = $className::setDriver('firebird');
        $instance
            ::setHost($env['FBIRD_HOST'])
            ::setPort((int)$env['FBIRD_PORT'])
            ::setDatabase($env['FBIRD_DATABASE'])
            ::setUser($env['FBIRD_USER'])
            ::setPassword($env['FBIRD_PASSWORD'])
            ::setCharset($env['FBIRD_CHARSET'])
            ::setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|PDOEngine
     */
    public static function pdoSQLite(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOEngine $className */
        $instance = $className::setDriver('sqlite');
        $instance
            ::setDatabase($env['SQLITE_DATABASE'])
            ::setCharset($env['SQLITE_CHARSET'])
            ::setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|PDOEngine
     */
    public static function pdoMemory(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOEngine {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOEngine $className */
        $instance = $className::setDriver('sqlite');
        $instance
            ::setDatabase($env['SQLITE_DATABASE_MEMORY'])
            ::setCharset($env['SQLITE_CHARSET'])
            ::setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }
}
