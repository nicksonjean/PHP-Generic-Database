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
use GenericDatabase\Engine\JSONConnection;
use GenericDatabase\Engine\JSON\Connection\JSON;
use GenericDatabase\Engine\XMLConnection;
use GenericDatabase\Engine\XML\Connection\XML;
use GenericDatabase\Engine\YAMLConnection;
use GenericDatabase\Engine\YAML\Connection\YAML;
use GenericDatabase\Engine\CSVConnection;
use GenericDatabase\Engine\CSV\Connection\CSV;
use GenericDatabase\Engine\INIConnection;
use GenericDatabase\Engine\INI\Connection\INI;

/**
 * Class Fluent
 * Provides methods to create database connections for various database engines.
 *
 * Methods:
 * - `nativeMySQLi(array $env, bool $persistent = false, bool $strategy = false): Connection|MySQLiConnection`: Creates a native MySQLi connection.
 * - `nativePgSQL(array $env, bool $persistent = false, bool $strategy = false): Connection|PgSQLConnection`: Creates a native PostgreSQL connection.
 * - `nativeSQLSrv(array $env, bool $persistent = false, bool $strategy = false): Connection|SQLSrvConnection`: Creates a native SQL Server connection.
 * - `nativeOCI(array $env, bool $persistent = false, bool $strategy = false): Connection|OCIConnection`: Creates a native Oracle OCI connection.
 * - `nativeFirebird(array $env, bool $persistent = false, bool $strategy = false): Connection|FirebirdConnection`: Creates a native Firebird connection.
 * - `nativeSQLite(array $env, bool $persistent = false, bool $strategy = false): Connection|SQLiteConnection`: Creates a native SQLite connection.
 * - `nativeMemory(array $env, bool $persistent = false, bool $strategy = false): Connection|SQLiteConnection`: Creates a native SQLite in-memory connection.
 * - `pdoMySQL(array $env, bool $persistent = false, bool $strategy = false): Connection|PDOConnection`: Creates a PDO MySQL connection.
 * - `pdoPgSQL(array $env, bool $persistent = false, bool $strategy = false): Connection|PDOConnection`: Creates a PDO PostgreSQL connection.
 * - `pdoSQLSrv(array $env, bool $strategy = false): Connection|PDOConnection`: Creates a PDO SQL Server connection.
 * - `pdoOCI(array $env, bool $persistent = false, bool $strategy = false): Connection|PDOConnection`: Creates a PDO Oracle OCI connection.
 * - `pdoFirebird(array $env, bool $persistent = false, bool $strategy = false): Connection|PDOConnection`: Creates a PDO Firebird connection.
 * - `pdoSQLite(array $env, bool $persistent = false, bool $strategy = false): Connection|PDOConnection`: Creates a PDO SQLite connection.
 * - `pdoMemory(array $env, bool $persistent = false, bool $strategy = false): Connection|PDOConnection`:Creates a PDO SQLite in-memory connection.
 * - `odbcMySQL(array $env, bool $persistent = false, bool $strategy = false): Connection|ODBCConnection`:Creates an ODBC MySQL connection.
 * - `odbcPgSQL(array $env, bool $persistent = false, bool $strategy = false): Connection|ODBCConnection`:Creates an ODBC PostgreSQL connection.
 * - `odbcSQLSrv(array $env, bool $persistent = false, bool $strategy = false): Connection|ODBCConnection`:Creates an ODBC SQL Server connection.
 * - `odbcOCI(array $env, bool $persistent = false, bool $strategy = false): Connection|ODBCConnection`:Creates an ODBC Oracle OCI connection.
 * - `odbcFirebird(array $env, bool $persistent = false, bool $strategy = false): Connection|ODBCConnection`: Creates an ODBC Firebird connection.
 * - `odbcSQLite(array $env, bool $persistent = false, bool $strategy = false): Connection|ODBCConnection`: Creates an ODBC SQLite connection.
 * - `odbcAccess(array $env, bool $persistent = false, bool $strategy = false): Connection|ODBCConnection`: Creates an ODBC Microsoft Access connection.
 * - `odbcExcel(array $env, bool $persistent = false, bool $strategy = false): Connection|ODBCConnection`: Creates an ODBC Microsoft Excel connection.
 * - `odbcText(array $env, bool $persistent = false, bool $strategy = false): Connection|ODBCConnection`: Creates an ODBC Text connection.
 * - `odbcMemory(array $env, bool $persistent = false, bool $strategy = false): Connection|ODBCConnection`: Creates an ODBC SQLite in-memory connection.
 */
class Fluent
{
    /**
     * Creates a native MySQLi connection using the provided environment settings.
     *
     * @param array $env An associative array containing MySQL connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|MySQLiConnection Returns a MySQLi connection instance.
     */
    public static function nativeMySQLi(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|MySQLiConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_MYSQLI_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'mysqli');
        }
        /** @var Connection|MySQLiConnection $instance */
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
                MySQL::ATTR_DEFAULT_FETCH_MODE => MySQL::FETCH_OBJ,
                MySQL::ATTR_REPORT => MySQL::REPORT_ERROR | MySQL::REPORT_STRICT
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a native PgSQL connection using the provided environment settings.
     *
     * @param array $env An associative array containing PgSQL connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PgSQLConnection Returns a PgSQL connection instance.
     */
    public static function nativePgSQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PgSQLConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PGSQL_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pgsql');
        }
        /** @var Connection|PgSQLConnection $instance */
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
                PgSQL::ATTR_DEFAULT_FETCH_MODE => PgSQL::FETCH_OBJ,
                PgSQL::ATTR_REPORT => PgSQL::REPORT_ERROR | PgSQL::REPORT_STRICT
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a native SQLSrv connection using the provided environment settings.
     *
     * @param array $env An associative array containing SQLSrv connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|SQLSrvConnection Returns a SQLSrv connection instance.
     */
    public static function nativeSQLSrv(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|SQLSrvConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_SQLSRV_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'sqlsrv');
        }
        /** @var Connection|SQLSrvConnection $instance */
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
                SQLSrv::ATTR_DEFAULT_FETCH_MODE => SQLSrv::FETCH_OBJ,
                SQLSrv::ATTR_REPORT => SQLSrv::REPORT_ERROR | SQLSrv::REPORT_STRICT
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a native OCI connection using the provided environment settings.
     *
     * @param array $env An associative array containing OCI connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|OCIConnection Returns a OCI connection instance.
     */
    public static function nativeOCI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|OCIConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_OCI_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'oci');
        }
        /** @var Connection|OCIConnection $instance */
        $instance = $className::setHost($env['OCI_HOST']);
        $instance::setPort((int) $env['OCI_PORT'])
            ::setDatabase($env['OCI_DATABASE'])
            ::setUser($env['OCI_USERNAME'])
            ::setPassword($env['OCI_PASSWORD'])
            ::setCharset($env['OCI_CHARSET'])
            ::setOptions([
                OCI::ATTR_PERSISTENT => $persistent,
                OCI::ATTR_CONNECT_TIMEOUT => 28800,
                OCI::ATTR_DEFAULT_FETCH_MODE => OCI::FETCH_OBJ,
                OCI::ATTR_REPORT => OCI::REPORT_ERROR | OCI::REPORT_STRICT
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a native Firebird connection using the provided environment settings.
     *
     * @param array $env An associative array containing Firebird connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|FirebirdConnection Returns a Firebird connection instance.
     */
    public static function nativeFirebird(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|FirebirdConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_FIREBIRD_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'firebird');
        }
        /** @var Connection|FirebirdConnection $instance */
        $instance = $className::setHost($env['IBASE_HOST']);
        $instance::setPort((int) $env['IBASE_PORT'])
            ::setDatabase($env['IBASE_DATABASE'])
            ::setUser($env['IBASE_USERNAME'])
            ::setPassword($env['IBASE_PASSWORD'])
            ::setCharset($env['IBASE_CHARSET'])
            ::setOptions([
                Firebird::ATTR_PERSISTENT => $persistent,
                Firebird::ATTR_CONNECT_TIMEOUT => 28800,
                Firebird::ATTR_DEFAULT_FETCH_MODE => Firebird::FETCH_OBJ,
                Firebird::ATTR_REPORT => Firebird::REPORT_ERROR | Firebird::REPORT_STRICT
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a native SQLite connection using the provided environment settings.
     *
     * @param array $env An associative array containing SQLite connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|SQLiteConnection Returns a SQLite connection instance.
     */
    public static function nativeSQLite(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|SQLiteConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_SQLITE_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'sqlite');
        }
        /** @var Connection|SQLiteConnection $instance */
        $instance = $className::setDatabase($env['SQLITE_DATABASE']);
        $instance::setCharset($env['SQLITE_CHARSET'])
            ::setOptions([
                SQLite::ATTR_OPEN_READONLY => false,
                SQLite::ATTR_OPEN_READWRITE => true,
                SQLite::ATTR_OPEN_CREATE => true,
                SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                SQLite::ATTR_PERSISTENT => $persistent,
                SQLite::ATTR_AUTOCOMMIT => true,
                SQLite::ATTR_DEFAULT_FETCH_MODE => SQLite::FETCH_OBJ,
                SQLite::ATTR_REPORT => SQLite::REPORT_ERROR | SQLite::REPORT_STRICT
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a native SQLite in-memory connection using the provided environment settings.
     *
     * @param array $env An associative array containing SQLite connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|SQLiteConnection Returns a SQLite connection instance.
     */
    public static function nativeMemory(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|SQLiteConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_SQLITE_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'sqlite');
        }
        /** @var Connection|SQLiteConnection $instance */
        $instance = $className::setDatabase($env['SQLITE_DATABASE_MEMORY']);
        $instance::setCharset($env['SQLITE_CHARSET'])
            ::setOptions([
                SQLite::ATTR_OPEN_READONLY => false,
                SQLite::ATTR_OPEN_READWRITE => true,
                SQLite::ATTR_OPEN_CREATE => true,
                SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                SQLite::ATTR_PERSISTENT => $persistent,
                SQLite::ATTR_AUTOCOMMIT => true,
                SQLite::ATTR_DEFAULT_FETCH_MODE => SQLite::FETCH_OBJ,
                SQLite::ATTR_REPORT => SQLite::REPORT_ERROR | SQLite::REPORT_STRICT
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a PDO MySQL connection using the provided environment settings.
     *
     * @param array $env An associative array containing MySQL connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PDOConnection Returns a MySQL connection instance.
     */
    public static function pdoMySQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PDO_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOConnection $instance */
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
     * Creates a PDO PgSQL connection using the provided environment settings.
     *
     * @param array $env An associative array containing PgSQL connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PDOConnection Returns a PgSQL connection instance.
     */
    public static function pdoPgSQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PDO_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOConnection $instance */
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
     * Creates a PDO SQLSrv connection using the provided environment settings.
     *
     * @param array $env An associative array containing SQLSrv connection parameters.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PDOConnection Returns a SQLSrv connection instance.
     */
    public static function pdoSQLSrv(
        array $env,
        bool $strategy = false
    ): Connection|PDOConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PDO_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOConnection $instance */
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
     * Creates a PDO OCI connection using the provided environment settings.
     *
     * @param array $env An associative array containing OCI connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PDOConnection Returns a OCI connection instance.
     */
    public static function pdoOCI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PDO_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOConnection $instance */
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
     * Creates a PDO Firebird connection using the provided environment settings.
     *
     * @param array $env An associative array containing Firebird connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PDOConnection Returns a Firebird connection instance.
     */
    public static function pdoFirebird(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PDO_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOConnection $instance */
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
     * Creates a PDO SQLite connection using the provided environment settings.
     *
     * @param array $env An associative array containing SQLite connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PDOConnection Returns a SQLite connection instance.
     */
    public static function pdoSQLite(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PDO_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOConnection $instance */
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
     * Creates a PDO SQLite in-memory connection using the provided environment settings.
     *
     * @param array $env An associative array containing SQLite connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PDOConnection Returns a SQLite connection instance.
     */
    public static function pdoMemory(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PDO_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'pdo');
        }
        /** @var Connection|PDOConnection $instance */
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
     * Creates a ODBC MySQL connection using the provided environment settings.
     *
     * @param array $env An associative array containing MySQL connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a MySQL connection instance.
     */
    public static function odbcMySQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $instance */
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
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC PgSQL connection using the provided environment settings.
     *
     * @param array $env An associative array containing PgSQL connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a PgSQL connection instance.
     */
    public static function odbcPgSQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $instance */
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
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC SQLSrv connection using the provided environment settings.
     *
     * @param array $env An associative array containing SQLSrv connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a SQLSrv connection instance.
     */
    public static function odbcSQLSrv(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $instance */
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
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC OCI connection using the provided environment settings.
     *
     * @param array $env An associative array containing OCI connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a OCI connection instance.
     */
    public static function odbcOCI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $instance */
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
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC Firebird connection using the provided environment settings.
     *
     * @param array $env An associative array containing Firebird connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a Firebird connection instance.
     */
    public static function odbcFirebird(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $instance */
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
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC SQLite connection using the provided environment settings.
     *
     * @param array $env An associative array containing SQLite connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a SQLite connection instance.
     */
    public static function odbcSQLite(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $instance */
        $instance = $className::setDriver('sqlite');
        $instance
            ::setDatabase($env['SQLITE_DATABASE'])
            ::setCharset($env['SQLITE_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC Access connection using the provided environment settings.
     *
     * @param array $env An associative array containing Access connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a Access connection instance.
     */
    public static function odbcAccess(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $instance */
        $instance = $className::setDriver('access');
        $instance
            ::setDatabase($env['ACCESS_DATABASE'])
            ::setUser($env['ACCESS_USERNAME'])
            ::setPassword($env['ACCESS_PASSWORD'])
            ::setCharset($env['ACCESS_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC Excel connection using the provided environment settings.
     *
     * @param array $env An associative array containing Excel connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a Excel connection instance.
     */
    public static function odbcExcel(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $instance */
        $instance = $className::setDriver('excel');
        $instance
            ::setDatabase($env['EXCEL_DATABASE'])
            ::setCharset($env['EXCEL_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC Text connection using the provided environment settings.
     *
     * @param array $env An associative array containing Text connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a Text connection instance.
     */
    public static function odbcText(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $instance */
        $instance = $className::setDriver('text');
        $instance
            ::setDatabase($env['TEXT_DATABASE'])
            ::setCharset($env['TEXT_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC Memory connection using the provided environment settings.
     *
     * @param array $env An associative array containing ODBC Memory connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a ODBC Memory connection instance.
     */
    public static function odbcMemory(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'odbc');
        }
        /** @var Connection|ODBCConnection $instance */
        $instance = $className::setDriver('sqlite');
        $instance
            ::setDatabase($env['SQLITE_DATABASE_MEMORY'])
            ::setCharset($env['SQLITE_CHARSET'])
            ::setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a native JSON connection using the provided environment settings.
     *
     * @param array $env An associative array containing native JSON connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|JSONConnection Returns a native JSON connection instance.
     */
    public static function nativeJSON(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|JSONConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_JSON_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'json');
        }
        /** @var Connection|JSONConnection $instance */
        $instance = $className::setDatabase($env['JSON_DATABASE']);
        $instance::setCharset($env['JSON_CHARSET'])
            ::setOptions([
                JSON::ATTR_PERSISTENT => $persistent,
                JSON::ATTR_AUTOCOMMIT => true,
                JSON::ATTR_CONNECT_TIMEOUT => 28800,
                JSON::ATTR_DEFAULT_FETCH_MODE => JSON::FETCH_OBJ,
                JSON::ATTR_REPORT => JSON::REPORT_ERROR | JSON::REPORT_STRICT,
                JSON::ATTR_JSON_PRETTY_PRINT => true
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a native XML connection using the provided environment settings.
     *
     * @param array $env An associative array containing native XML connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|XMLConnection Returns a native XML connection instance.
     */
    public static function nativeXML(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|XMLConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_XML_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'xml');
        }
        /** @var Connection|XMLConnection $instance */
        $instance = $className::setDatabase($env['XML_DATABASE']);
        $instance::setCharset($env['XML_CHARSET'])
            ::setOptions([
                XML::ATTR_PERSISTENT => $persistent,
                XML::ATTR_AUTOCOMMIT => true,
                XML::ATTR_CONNECT_TIMEOUT => 28800,
                XML::ATTR_DEFAULT_FETCH_MODE => XML::FETCH_OBJ,
                XML::ATTR_REPORT => XML::REPORT_ERROR | XML::REPORT_STRICT
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a native YAML connection using the provided environment settings.
     *
     * @param array $env An associative array containing native YAML connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|YAMLConnection Returns a native YAML connection instance.
     */
    public static function nativeYAML(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|YAMLConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_YAML_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'yaml');
        }
        /** @var Connection|YAMLConnection $instance */
        $instance = $className::setDatabase($env['YAML_DATABASE']);
        $instance::setCharset($env['YAML_CHARSET'])
            ::setOptions([
                YAML::ATTR_PERSISTENT => $persistent,
                YAML::ATTR_AUTOCOMMIT => true,
                YAML::ATTR_CONNECT_TIMEOUT => 28800,
                YAML::ATTR_DEFAULT_FETCH_MODE => YAML::FETCH_OBJ,
                YAML::ATTR_REPORT => YAML::REPORT_ERROR | YAML::REPORT_STRICT
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a native CSV connection using the provided environment settings.
     *
     * @param array $env An associative array containing native CSV connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|CSVConnection Returns a native CSV connection instance.
     */
    public static function nativeCSV(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|CSVConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_CSV_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'csv');
        }
        /** @var Connection|CSVConnection $instance */
        $instance = $className::setDatabase($env['CSV_DATABASE']);
        $instance::setCharset($env['CSV_CHARSET'])
            ::setOptions([
                CSV::ATTR_PERSISTENT => $persistent,
                CSV::ATTR_AUTOCOMMIT => true,
                CSV::ATTR_CONNECT_TIMEOUT => 28800,
                CSV::ATTR_DEFAULT_FETCH_MODE => CSV::FETCH_OBJ,
                CSV::ATTR_REPORT => CSV::REPORT_ERROR | CSV::REPORT_STRICT
            ])
            ::setException(true);

        return $instance;
    }

    /**
     * Creates a native INI connection using the provided environment settings.
     *
     * @param array $env An associative array containing native INI connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|INIConnection Returns a native INI connection instance.
     */
    public static function nativeINI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|INIConnection {
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_INI_ENGINE()->value;
        if ($strategy) {
            call_user_func([$className, 'setEngine'], 'ini');
        }
        /** @var Connection|INIConnection $instance */
        $instance = $className::setDatabase($env['INI_DATABASE']);
        $instance::setCharset($env['INI_CHARSET'])
            ::setOptions([
                INI::ATTR_PERSISTENT => $persistent,
                INI::ATTR_AUTOCOMMIT => true,
                INI::ATTR_CONNECT_TIMEOUT => 28800,
                INI::ATTR_DEFAULT_FETCH_MODE => INI::FETCH_OBJ,
                INI::ATTR_REPORT => INI::REPORT_ERROR | INI::REPORT_STRICT
            ])
            ::setException(true);

        return $instance;
    }
}
