<?php

declare(strict_types=1);

namespace GenericDatabase\Modules;

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

/**
 * Class Chainable
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
class Chainable
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('mysqli');
        } else {
            $instance = new MySQLiConnection();
        }
        $instance->setHost($env['MYSQL_HOST'])
            ->setPort((int) $env['MYSQL_PORT'])
            ->setDatabase($env['MYSQL_DATABASE'])
            ->setUser($env['MYSQL_USERNAME'])
            ->setPassword($env['MYSQL_PASSWORD'])
            ->setCharset($env['MYSQL_CHARSET'])
            ->setOptions([
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
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pgsql');
        } else {
            $instance = new PgSQLConnection();
        }
        $instance->setHost($env['PGSQL_HOST'])
            ->setPort((int) $env['PGSQL_PORT'])
            ->setDatabase($env['PGSQL_DATABASE'])
            ->setUser($env['PGSQL_USERNAME'])
            ->setPassword($env['PGSQL_PASSWORD'])
            ->setCharset($env['PGSQL_CHARSET'])
            ->setOptions([
                PgSQL::ATTR_PERSISTENT => $persistent,
                PgSQL::ATTR_CONNECT_ASYNC => true,
                PgSQL::ATTR_CONNECT_FORCE_NEW => true,
                PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
                PgSQL::ATTR_DEFAULT_FETCH_MODE => PgSQL::FETCH_OBJ,
                PgSQL::ATTR_REPORT => PgSQL::REPORT_ERROR | PgSQL::REPORT_STRICT
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('sqlsrv');
        } else {
            $instance = new SQLSrvConnection();
        }
        $instance->setHost($env['SQLSRV_HOST'])
            ->setPort((int) $env['SQLSRV_PORT'])
            ->setDatabase($env['SQLSRV_DATABASE'])
            ->setUser($env['SQLSRV_USERNAME'])
            ->setPassword($env['SQLSRV_PASSWORD'])
            ->setCharset($env['SQLSRV_CHARSET'])
            ->setOptions([
                SQLSrv::ATTR_PERSISTENT => $persistent,
                SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
                SQLSrv::ATTR_DEFAULT_FETCH_MODE => SQLSrv::FETCH_OBJ,
                SQLSrv::ATTR_REPORT => SQLSrv::REPORT_ERROR | SQLSrv::REPORT_STRICT
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('oci');
        } else {
            $instance = new OCIConnection();
        }
        $instance->setHost($env['OCI_HOST'])
            ->setPort((int) $env['OCI_PORT'])
            ->setDatabase($env['OCI_DATABASE'])
            ->setUser($env['OCI_USERNAME'])
            ->setPassword($env['OCI_PASSWORD'])
            ->setCharset($env['OCI_CHARSET'])
            ->setOptions([
                OCI::ATTR_PERSISTENT => $persistent,
                OCI::ATTR_CONNECT_TIMEOUT => 28800,
                OCI::ATTR_DEFAULT_FETCH_MODE => OCI::FETCH_OBJ,
                OCI::ATTR_REPORT => OCI::REPORT_ERROR | OCI::REPORT_STRICT
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('firebird');
        } else {
            $instance = new FirebirdConnection();
        }
        $instance->setHost($env['IBASE_HOST'])
            ->setPort((int) $env['IBASE_PORT'])
            ->setDatabase($env['IBASE_DATABASE'])
            ->setUser($env['IBASE_USERNAME'])
            ->setPassword($env['IBASE_PASSWORD'])
            ->setCharset($env['IBASE_CHARSET'])
            ->setOptions([
                Firebird::ATTR_PERSISTENT => $persistent,
                Firebird::ATTR_CONNECT_TIMEOUT => 28800,
                Firebird::ATTR_DEFAULT_FETCH_MODE => Firebird::FETCH_OBJ,
                Firebird::ATTR_REPORT => Firebird::REPORT_ERROR | Firebird::REPORT_STRICT
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('sqlite');
        } else {
            $instance = new SQLiteConnection();
        }
        $instance->setDatabase($env['SQLITE_DATABASE'])
            ->setCharset($env['SQLITE_CHARSET'])
            ->setOptions([
                SQLite::ATTR_OPEN_READONLY => false,
                SQLite::ATTR_OPEN_READWRITE => true,
                SQLite::ATTR_OPEN_CREATE => true,
                SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                SQLite::ATTR_PERSISTENT => $persistent,
                SQLite::ATTR_AUTOCOMMIT => true,
                SQLite::ATTR_DEFAULT_FETCH_MODE => SQLite::FETCH_OBJ,
                SQLite::ATTR_REPORT => SQLite::REPORT_ERROR | SQLite::REPORT_STRICT
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('sqlite');
        } else {
            $instance = new SQLiteConnection();
        }
        $instance->setDatabase($env['SQLITE_DATABASE_MEMORY'])
            ->setCharset($env['SQLITE_CHARSET'])
            ->setOptions([
                SQLite::ATTR_OPEN_READONLY => false,
                SQLite::ATTR_OPEN_READWRITE => true,
                SQLite::ATTR_OPEN_CREATE => true,
                SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                SQLite::ATTR_PERSISTENT => $persistent,
                SQLite::ATTR_AUTOCOMMIT => true,
                SQLite::ATTR_DEFAULT_FETCH_MODE => SQLite::FETCH_OBJ,
                SQLite::ATTR_REPORT => SQLite::REPORT_ERROR | SQLite::REPORT_STRICT
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a PDO MySQL connection using the provided environment settings.
     *
     * @param array $env An associative array containing PDO MySQL connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PDOConnection Returns a PDO MySQL connection instance.
     */
    public static function pdoMySQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pdo');
        } else {
            $instance = new PDOConnection();
        }
        $instance->setDriver('mysql')
            ->setHost($env['MYSQL_HOST'])
            ->setPort((int) $env['MYSQL_PORT'])
            ->setDatabase($env['MYSQL_DATABASE'])
            ->setUser($env['MYSQL_USERNAME'])
            ->setPassword($env['MYSQL_PASSWORD'])
            ->setCharset($env['MYSQL_CHARSET'])
            ->setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a PDO PgSQL connection using the provided environment settings.
     *
     * @param array $env An associative array containing PDO PgSQL connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PDOConnection Returns a PDO PgSQL connection instance.
     */
    public static function pdoPgSQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pdo');
        } else {
            $instance = new PDOConnection();
        }
        $instance->setDriver('pgsql')
            ->setHost($env['PGSQL_HOST'])
            ->setPort((int) $env['PGSQL_PORT'])
            ->setDatabase($env['PGSQL_DATABASE'])
            ->setUser($env['PGSQL_USERNAME'])
            ->setPassword($env['PGSQL_PASSWORD'])
            ->setCharset('utf8')
            ->setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a PDO SQLSrv connection using the provided environment settings.
     *
     * @param array $env An associative array containing PDO SQLSrv connection parameters.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PDOConnection Returns a PDO SQLSrv connection instance.
     */
    public static function pdoSQLSrv(
        array $env,
        bool $strategy = false
    ): Connection|PDOConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pdo');
        } else {
            $instance = new PDOConnection();
        }
        $instance->setDriver('sqlsrv')
            ->setHost($env['SQLSRV_HOST'])
            ->setPort((int) $env['SQLSRV_PORT'])
            ->setDatabase($env['SQLSRV_DATABASE'])
            ->setUser($env['SQLSRV_USERNAME'])
            ->setPassword($env['SQLSRV_PASSWORD'])
            ->setCharset($env['SQLSRV_CHARSET'])
            ->setOptions([
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a PDO OCI connection using the provided environment settings.
     *
     * @param array $env An associative array containing PDO OCI connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PDOConnection Returns a PDO OCI connection instance.
     */
    public static function pdoOCI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pdo');
        } else {
            $instance = new PDOConnection();
        }
        $instance->setDriver('oci')
            ->setHost($env['OCI_HOST'])
            ->setPort((int) $env['OCI_PORT'])
            ->setDatabase($env['OCI_DATABASE'])
            ->setUser($env['OCI_USERNAME'])
            ->setPassword($env['OCI_PASSWORD'])
            ->setCharset($env['OCI_CHARSET'])
            ->setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a PDO Firebird connection using the provided environment settings.
     *
     * @param array $env An associative array containing PDO Firebird connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PDOConnection Returns a PDO Firebird connection instance.
     */
    public static function pdoFirebird(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pdo');
        } else {
            $instance = new PDOConnection();
        }
        $instance->setDriver('firebird')
            ->setHost($env['FBIRD_HOST'])
            ->setPort((int) $env['FBIRD_PORT'])
            ->setDatabase($env['FBIRD_DATABASE'])
            ->setUser($env['FBIRD_USERNAME'])
            ->setPassword($env['FBIRD_PASSWORD'])
            ->setCharset($env['FBIRD_CHARSET'])
            ->setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a PDO SQLite connection using the provided environment settings.
     *
     * @param array $env An associative array containing PDO SQLite connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PDOConnection Returns a PDO SQLite connection instance.
     */
    public static function pdoSQLite(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pdo');
        } else {
            $instance = new PDOConnection();
        }
        $instance->setDriver('sqlite')
            ->setDatabase($env['SQLITE_DATABASE'])
            ->setCharset($env['SQLITE_CHARSET'])
            ->setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a PDO SQLite in-memory connection using the provided environment settings.
     *
     * @param array $env An associative array containing PDO SQLite connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|PDOConnection Returns a PDO SQLite connection instance.
     */
    public static function pdoMemory(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|PDOConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pdo');
        } else {
            $instance = new PDOConnection();
        }
        $instance->setDriver('sqlite')
            ->setDatabase($env['SQLITE_DATABASE_MEMORY'])
            ->setCharset($env['SQLITE_CHARSET'])
            ->setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC MySQL connection using the provided environment settings.
     *
     * @param array $env An associative array containing ODBC MySQL connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a ODBC MySQL connection instance.
     */
    public static function odbcMySQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCConnection();
        }
        $instance->setDriver('mysql')
            ->setHost($env['MYSQL_HOST'])
            ->setPort((int) $env['MYSQL_PORT'])
            ->setDatabase($env['MYSQL_DATABASE'])
            ->setUser($env['MYSQL_USERNAME'])
            ->setPassword($env['MYSQL_PASSWORD'])
            ->setCharset($env['MYSQL_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ->setException(true);
        return $instance;
    }

    /**
     * Creates a ODBC PgSQL connection using the provided environment settings.
     *
     * @param array $env An associative array containing ODBC PgSQL connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a ODBC PgSQL connection instance.
     */
    public static function odbcPgSQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCConnection();
        }
        $instance->setDriver('pgsql')
            ->setHost($env['PGSQL_HOST'])
            ->setPort((int) $env['PGSQL_PORT'])
            ->setDatabase($env['PGSQL_DATABASE'])
            ->setUser($env['PGSQL_USERNAME'])
            ->setPassword($env['PGSQL_PASSWORD'])
            ->setCharset($env['PGSQL_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC SQLSrv connection using the provided environment settings.
     *
     * @param array $env An associative array containing ODBC SQLSrv connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a ODBC SQLSrv connection instance.
     */
    public static function odbcSQLSrv(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCConnection();
        }
        $instance->setDriver('sqlsrv')
            ->setHost($env['SQLSRV_HOST'])
            ->setPort((int) $env['SQLSRV_PORT'])
            ->setDatabase($env['SQLSRV_DATABASE'])
            ->setUser($env['SQLSRV_USERNAME'])
            ->setPassword($env['SQLSRV_PASSWORD'])
            ->setCharset($env['SQLSRV_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC OCI connection using the provided environment settings.
     *
     * @param array $env An associative array containing ODBC OCI connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a ODBC OCI connection instance.
     */
    public static function odbcOCI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCConnection();
        }
        $instance->setDriver('oci')
            ->setHost($env['OCI_HOST'])
            ->setPort((int) $env['OCI_PORT'])
            ->setDatabase($env['OCI_DATABASE'])
            ->setUser($env['OCI_USERNAME'])
            ->setPassword($env['OCI_PASSWORD'])
            ->setCharset($env['OCI_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC Firebird connection using the provided environment settings.
     *
     * @param array $env An associative array containing ODBC Firebird connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a ODBC Firebird connection instance.
     */
    public static function odbcFirebird(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCConnection();
        }
        $instance->setDriver('firebird')
            ->setHost($env['FBIRD_HOST'])
            ->setPort((int) $env['FBIRD_PORT'])
            ->setDatabase($env['FBIRD_DATABASE'])
            ->setUser($env['FBIRD_USERNAME'])
            ->setPassword($env['FBIRD_PASSWORD'])
            ->setCharset($env['FBIRD_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC SQLite connection using the provided environment settings.
     *
     * @param array $env An associative array containing ODBC SQLite connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a ODBC SQLite connection instance.
     */
    public static function odbcSQLite(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCConnection();
        }
        $instance->setDriver('sqlite')
            ->setDatabase($env['SQLITE_DATABASE'])
            ->setCharset($env['SQLITE_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC Access connection using the provided environment settings.
     *
     * @param array $env An associative array containing ODBC Access connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a ODBC Access connection instance.
     */
    public static function odbcAccess(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCConnection();
        }
        $instance->setDriver('access')
            ->setDatabase($env['ACCESS_DATABASE'])
            ->setUser($env['ACCESS_USERNAME'])
            ->setPassword($env['ACCESS_PASSWORD'])
            ->setCharset($env['ACCESS_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC Excel connection using the provided environment settings.
     *
     * @param array $env An associative array containing ODBC Excel connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a ODBC Excel connection instance.
     */
    public static function odbcExcel(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCConnection();
        }
        $instance->setDriver('excel')
            ->setDatabase($env['EXCEL_DATABASE'])
            ->setCharset($env['EXCEL_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * Creates a ODBC Text connection using the provided environment settings.
     *
     * @param array $env An associative array containing ODBC Text connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a ODBC Text connection instance.
     */
    public static function odbcText(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCConnection();
        }
        $instance->setDriver('text')
            ->setDatabase($env['TEXT_DATABASE'])
            ->setCharset($env['TEXT_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCConnection();
        }
        $instance->setDriver('sqlite')
            ->setDatabase($env['SQLITE_DATABASE_MEMORY'])
            ->setCharset($env['SQLITE_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
            ])
            ->setException(true);

        return $instance;
    }

     /**
     * Creates a native JSON connection using the provided environment settings.
     *
     * @param array $env An associative array containing ODBC Memory connection parameters.
     * @param bool $persistent Optional. Whether to use a persistent connection. Default is false.
     * @param bool $strategy Optional. Whether to use a generic connection strategy. Default is false.
     * @return Connection|ODBCConnection Returns a ODBC Memory connection instance.
     */
    public static function nativeJSON(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|JSONConnection {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('json');
        } else {
            $instance = new JSONConnection();
        }
        $instance->setDatabase($env['JSON_DATABASE'])
            ->setCharset($env['JSON_CHARSET'])
            ->setOptions([
                JSON::ATTR_PERSISTENT => $persistent,
                JSON::ATTR_AUTOCOMMIT => true,
                JSON::ATTR_CONNECT_TIMEOUT => 28800,
                JSON::ATTR_DEFAULT_FETCH_MODE => JSON::FETCH_OBJ,
                JSON::ATTR_REPORT => JSON::REPORT_ERROR | JSON::REPORT_STRICT
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('xml');
        } else {
            $instance = new XMLConnection();
        }
        $instance->setDatabase($env['XML_DATABASE'])
            ->setCharset($env['XML_CHARSET'])
            ->setOptions([
                XML::ATTR_PERSISTENT => $persistent,
                XML::ATTR_AUTOCOMMIT => true,
                XML::ATTR_CONNECT_TIMEOUT => 28800,
                XML::ATTR_DEFAULT_FETCH_MODE => XML::FETCH_OBJ,
                XML::ATTR_REPORT => XML::REPORT_ERROR | XML::REPORT_STRICT
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('yaml');
        } else {
            $instance = new YAMLConnection();
        }
        $instance->setDatabase($env['YAML_DATABASE'])
            ->setCharset($env['YAML_CHARSET'])
            ->setOptions([
                YAML::ATTR_PERSISTENT => $persistent,
                YAML::ATTR_AUTOCOMMIT => true,
                YAML::ATTR_CONNECT_TIMEOUT => 28800,
                YAML::ATTR_DEFAULT_FETCH_MODE => YAML::FETCH_OBJ,
                YAML::ATTR_REPORT => YAML::REPORT_ERROR | YAML::REPORT_STRICT
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('csv');
        } else {
            $instance = new CSVConnection();
        }
        $instance->setDatabase($env['CSV_DATABASE'])
            ->setCharset($env['CSV_CHARSET'])
            ->setOptions([
                CSV::ATTR_PERSISTENT => $persistent,
                CSV::ATTR_AUTOCOMMIT => true,
                CSV::ATTR_CONNECT_TIMEOUT => 28800,
                CSV::ATTR_DEFAULT_FETCH_MODE => CSV::FETCH_OBJ,
                CSV::ATTR_REPORT => CSV::REPORT_ERROR | CSV::REPORT_STRICT
            ])
            ->setException(true);

        return $instance;
    }
}

