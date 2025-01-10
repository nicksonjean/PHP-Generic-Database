<?php

declare(strict_types=1);

namespace GenericDatabase\Modules;

use GenericDatabase\Core\Entity;
use GenericDatabase\Connection;
use GenericDatabase\Engine\MySQLiConnection;
use GenericDatabase\Engine\MySQLi\Connection\MySQL;
use GenericDatabase\Engine\PgSQLConnection;
use GenericDatabase\Engine\PgSQL\Connection\PgSQL;
use GenericDatabase\Engine\SQLSrvConnection;
use GenericDatabase\Engine\SQLSrv\Connection\SQLSrv;
use GenericDatabase\Engine\OCIConnection;
use GenericDatabase\Engine\OCI\Connection\OCI;
use GenericDatabase\Engine\FirebirdConnection;
use GenericDatabase\Engine\Firebird\Connection\Firebird;
use GenericDatabase\Engine\SQLiteConnection;
use GenericDatabase\Engine\SQLite\Connection\SQLite;
use GenericDatabase\Engine\ODBCConnection;
use GenericDatabase\Engine\ODBC\Connection\ODBC;
use GenericDatabase\Engine\PDOConnection;
use PDO;

class Fluent
{
    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|MySQLiConnection
     */
    public static function nativeMySQLi(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|MySQLiConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_MYSQLI_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'mysqli');
        }
        /** @var Connection|MySQLiConnection $className */
        $instance = $className::setHost($env['MYSQL_HOST']);
        $instance::setPort((int) $env['MYSQL_PORT'])
            ::setDatabase($env['MYSQL_DATABASE'])
            ::setUser($env['MYSQL_USERNAME'])
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
                MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M",
                MySQL::ATTR_DEFAULT_FETCH_MODE => MySQL::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|PgSQLConnection
     */
    public static function nativePgSQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PgSQLConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PGSQL_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pgsql');
        }
        /** @var Connection|PgSQLConnection $className */
        $instance = $className::setHost($env['PGSQL_HOST']);
        $instance::setPort((int) $env['PGSQL_PORT'])
            ::setDatabase($env['PGSQL_DATABASE'])
            ::setUser($env['PGSQL_USERNAME'])
            ::setPassword($env['PGSQL_PASSWORD'])
            ::setCharset($env['PGSQL_CHARSET'])
            ::setOptions([
                PgSQL::ATTR_PERSISTENT => $persistent,
                PgSQL::ATTR_CONNECT_ASYNC => true,
                PgSQL::ATTR_CONNECT_FORCE_NEW => true,
                PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
                PgSQL::ATTR_DEFAULT_FETCH_MODE => PgSQL::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|SQLSrvConnection
     */
    public static function nativeSQLSrv(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|SQLSrvConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLSRV_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'sqlsrv');
        }
        /** @var Connection|SQLSrvConnection $className */
        $instance = $className::setHost($env['SQLSRV_HOST']);
        $instance::setPort((int) $env['SQLSRV_PORT'])
            ::setPort((int) $env['SQLSRV_PORT'])
            ::setDatabase($env['SQLSRV_DATABASE'])
            ::setUser($env['SQLSRV_USERNAME'])
            ::setPassword($env['SQLSRV_PASSWORD'])
            ::setCharset($env['SQLSRV_CHARSET'])
            ::setOptions([
                SQLSrv::ATTR_PERSISTENT => $persistent,
                SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
                SQLSrv::ATTR_DEFAULT_FETCH_MODE => SQLSrv::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|OCIConnection
     */
    public static function nativeOCI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|OCIConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_OCI_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'oci');
        }
        /** @var Connection|OCIConnection $className */
        $instance = $className::setHost($env['OCI_HOST']);
        $instance::setPort((int) $env['OCI_PORT'])
            ::setDatabase($env['OCI_DATABASE'])
            ::setUser($env['OCI_USERNAME'])
            ::setPassword($env['OCI_PASSWORD'])
            ::setCharset($env['OCI_CHARSET'])
            ::setOptions([
                OCI::ATTR_PERSISTENT => $persistent,
                OCI::ATTR_CONNECT_TIMEOUT => 28800,
                OCI::ATTR_DEFAULT_FETCH_MODE => OCI::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|FirebirdConnection
     */
    public static function nativeFirebird(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|FirebirdConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_FIREBIRD_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'firebird');
        }
        /** @var Connection|FirebirdConnection $className */
        $instance = $className::setHost($env['FBIRD_HOST']);
        $instance::setPort((int) $env['FBIRD_PORT'])
            ::setDatabase($env['FBIRD_DATABASE'])
            ::setUser($env['FBIRD_USERNAME'])
            ::setPassword($env['FBIRD_PASSWORD'])
            ::setCharset($env['FBIRD_CHARSET'])
            ::setOptions([
                Firebird::ATTR_PERSISTENT => $persistent,
                Firebird::ATTR_CONNECT_TIMEOUT => 28800,
                Firebird::ATTR_DEFAULT_FETCH_MODE => Firebird::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|SQLiteConnection
     */
    public static function nativeSQLite(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|SQLiteConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLITE_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'sqlite');
        }
        /** @var Connection|SQLiteConnection $className */
        $instance = $className::setDatabase($env['SQLITE_DATABASE']);
        $instance::setCharset($env['SQLITE_CHARSET'])
            ::setOptions([
                SQLite::ATTR_OPEN_READONLY => false,
                SQLite::ATTR_OPEN_READWRITE => true,
                SQLite::ATTR_OPEN_CREATE => true,
                SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                SQLite::ATTR_PERSISTENT => $persistent,
                SQLite::ATTR_AUTOCOMMIT => true,
                SQLite::ATTR_DEFAULT_FETCH_MODE => SQLite::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|SQLiteConnection
     */
    public static function nativeMemory(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|SQLiteConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLITE_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'sqlite');
        }
        /** @var Connection|SQLiteConnection $className */
        $instance = $className::setDatabase($env['SQLITE_DATABASE_MEMORY']);
        $instance::setCharset($env['SQLITE_CHARSET'])
            ::setOptions([
                SQLite::ATTR_OPEN_READONLY => false,
                SQLite::ATTR_OPEN_READWRITE => true,
                SQLite::ATTR_OPEN_CREATE => true,
                SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                SQLite::ATTR_PERSISTENT => $persistent,
                SQLite::ATTR_AUTOCOMMIT => true,
                SQLite::ATTR_DEFAULT_FETCH_MODE => SQLite::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|PDOConnection
     */
    public static function pdoMySQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOConnection $className */
        $instance = $className::setDriver('mysql');
        $instance::setHost($env['MYSQL_HOST'])
            ::setPort((int) $env['MYSQL_PORT'])
            ::setDatabase($env['MYSQL_DATABASE'])
            ::setUser($env['MYSQL_USERNAME'])
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
     * @return Connection|PDOConnection
     */
    public static function pdoPgSQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOConnection $className */
        $instance = $className::setDriver('pgsql');
        $instance
            ::setHost($env['PGSQL_HOST'])
            ::setPort((int) $env['PGSQL_PORT'])
            ::setDatabase($env['PGSQL_DATABASE'])
            ::setUser($env['PGSQL_USERNAME'])
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
     * @return Connection|PDOConnection
     */
    public static function pdoSQLSrv(
        array $env,
        bool $strategy = false
    ): Connection|PDOConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOConnection $className */
        $instance = $className::setDriver('sqlsrv');
        $instance
            ::setHost($env['SQLSRV_HOST'])
            ::setPort((int) $env['SQLSRV_PORT'])
            ::setDatabase($env['SQLSRV_DATABASE'])
            ::setUser($env['SQLSRV_USERNAME'])
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
     * @return Connection|PDOConnection
     */
    public static function pdoOCI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOConnection $className */
        $instance = $className::setDriver('oci');
        $instance
            ::setHost($env['OCI_HOST'])
            ::setPort((int) $env['OCI_PORT'])
            ::setDatabase($env['OCI_DATABASE'])
            ::setUser($env['OCI_USERNAME'])
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
     * @return Connection|PDOConnection
     */
    public static function pdoFirebird(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOConnection $className */
        $instance = $className::setDriver('firebird');
        $instance
            ::setHost($env['FBIRD_HOST'])
            ::setPort((int) $env['FBIRD_PORT'])
            ::setDatabase($env['FBIRD_DATABASE'])
            ::setUser($env['FBIRD_USERNAME'])
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
     * @return Connection|PDOConnection
     */
    public static function pdoSQLite(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOConnection $className */
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
     * @return Connection|PDOConnection
     */
    public static function pdoMemory(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOConnection $className */
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

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCConnection
     */
    public static function odbcMySQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $className */
        $instance = $className::setDriver('mysql');
        $instance
            ::setHost($env['MYSQL_HOST'])
            ::setPort((int) $env['MYSQL_PORT'])
            ::setDatabase($env['MYSQL_DATABASE'])
            ::setUser($env['MYSQL_USERNAME'])
            ::setPassword($env['MYSQL_PASSWORD'])
            ::setCharset($env['MYSQL_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCConnection
     */
    public static function odbcPgSQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $className */
        $instance = $className::setDriver('pgsql');
        $instance
            ::setHost($env['PGSQL_HOST'])
            ::setPort((int) $env['PGSQL_PORT'])
            ::setDatabase($env['PGSQL_DATABASE'])
            ::setUser($env['PGSQL_USERNAME'])
            ::setPassword($env['PGSQL_PASSWORD'])
            ::setCharset($env['PGSQL_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCConnection
     */
    public static function odbcSQLSrv(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $className */
        $instance = $className::setDriver('sqlsrv');
        $instance
            ::setHost($env['SQLSRV_HOST'])
            ::setPort((int) $env['SQLSRV_PORT'])
            ::setDatabase($env['SQLSRV_DATABASE'])
            ::setUser($env['SQLSRV_USERNAME'])
            ::setPassword($env['SQLSRV_PASSWORD'])
            ::setCharset($env['SQLSRV_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCConnection
     */
    public static function odbcOCI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $className */
        $instance = $className::setDriver('oci');
        $instance
            ::setHost($env['OCI_HOST'])
            ::setPort((int) $env['OCI_PORT'])
            ::setDatabase($env['OCI_DATABASE'])
            ::setUser($env['OCI_USERNAME'])
            ::setPassword($env['OCI_PASSWORD'])
            ::setCharset($env['OCI_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCConnection
     */
    public static function odbcFirebird(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $className */
        $instance = $className::setDriver('firebird');
        $instance
            ::setHost($env['FBIRD_HOST'])
            ::setPort((int) $env['FBIRD_PORT'])
            ::setDatabase($env['FBIRD_DATABASE'])
            ::setUser($env['FBIRD_USERNAME'])
            ::setPassword($env['FBIRD_PASSWORD'])
            ::setCharset($env['FBIRD_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCConnection
     */
    public static function odbcSQLite(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $className */
        $instance = $className::setDriver('sqlite');
        $instance
            ::setDatabase($env['SQLITE_DATABASE'])
            ::setCharset($env['SQLITE_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCConnection
     */
    public static function odbcAccess(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $className */
        $instance = $className::setDriver('access');
        $instance
            ::setDatabase($env['ACCESS_DATABASE'])
            ::setUser($env['ACCESS_USERNAME'])
            ::setPassword($env['ACCESS_PASSWORD'])
            ::setCharset($env['ACCESS_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCConnection
     */
    public static function odbcExcel(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $className */
        $instance = $className::setDriver('excel');
        $instance
            ::setDatabase($env['EXCEL_DATABASE'])
            ::setCharset($env['EXCEL_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCConnection
     */
    public static function odbcText(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $className */
        $instance = $className::setDriver('text');
        $instance
            ::setDatabase($env['TEXT_DATABASE'])
            ::setCharset($env['TEXT_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCConnection
     */
    public static function odbcMemory(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $className */
        $instance = $className::setDriver('sqlite');
        $instance
            ::setDatabase($env['SQLITE_DATABASE_MEMORY'])
            ::setCharset($env['SQLITE_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ::setException(true);

        return $instance;
    }
}
