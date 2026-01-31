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
 * Class StaticArgs
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
class StaticArgs
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
        /** @var Connection|MySQLiConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_MYSQLI_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'mysqli',
                host: $env['MYSQL_HOST'],
                port: (int) $env['MYSQL_PORT'],
                database: $env['MYSQL_DATABASE'],
                user: $env['MYSQL_USERNAME'],
                password: $env['MYSQL_PASSWORD'],
                charset: 'utf8',
                options: [
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
                ],
                exception: true
            );
        } else {
            return $constructor(
                host: $env['MYSQL_HOST'],
                port: (int) $env['MYSQL_PORT'],
                database: $env['MYSQL_DATABASE'],
                user: $env['MYSQL_USERNAME'],
                password: $env['MYSQL_PASSWORD'],
                charset: 'utf8',
                options: [
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
                ],
                exception: true
            );
        }
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
        /** @var Connection|PgSQLConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PGSQL_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'pgsql',
                host: $env['PGSQL_HOST'],
                port: (int) $env['PGSQL_PORT'],
                database: $env['PGSQL_DATABASE'],
                user: $env['PGSQL_USERNAME'],
                password: $env['PGSQL_PASSWORD'],
                charset: 'utf8',
                options: [
                    PgSQL::ATTR_PERSISTENT => $persistent,
                    PgSQL::ATTR_CONNECT_ASYNC => true,
                    PgSQL::ATTR_CONNECT_FORCE_NEW => true,
                    PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
                    PgSQL::ATTR_DEFAULT_FETCH_MODE => PgSQL::FETCH_OBJ,
                    PgSQL::ATTR_REPORT => PgSQL::REPORT_ERROR | PgSQL::REPORT_STRICT
                ],
                exception: true
            );
        } else {
            return $constructor(
                host: $env['PGSQL_HOST'],
                port: (int) $env['PGSQL_PORT'],
                database: $env['PGSQL_DATABASE'],
                user: $env['PGSQL_USERNAME'],
                password: $env['PGSQL_PASSWORD'],
                charset: 'utf8',
                options: [
                    PgSQL::ATTR_PERSISTENT => $persistent,
                    PgSQL::ATTR_CONNECT_ASYNC => true,
                    PgSQL::ATTR_CONNECT_FORCE_NEW => true,
                    PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
                    PgSQL::ATTR_DEFAULT_FETCH_MODE => PgSQL::FETCH_OBJ,
                    PgSQL::ATTR_REPORT => PgSQL::REPORT_ERROR | PgSQL::REPORT_STRICT
                ],
                exception: true
            );
        }
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
        /** @var Connection|SQLSrvConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_SQLSRV_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'sqlsrv',
                host: $env['SQLSRV_HOST'],
                port: (int) $env['SQLSRV_PORT'],
                database: $env['SQLSRV_DATABASE'],
                user: $env['SQLSRV_USERNAME'],
                password: $env['SQLSRV_PASSWORD'],
                charset: 'utf8',
                options: [
                    SQLSrv::ATTR_PERSISTENT => $persistent,
                    SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
                    SQLSrv::ATTR_DEFAULT_FETCH_MODE => SQLSrv::FETCH_OBJ,
                    SQLSrv::ATTR_REPORT => SQLSrv::REPORT_ERROR | SQLSrv::REPORT_STRICT
                ],
                exception: true
            );
        } else {
            return $constructor(
                host: $env['SQLSRV_HOST'],
                port: (int) $env['SQLSRV_PORT'],
                database: $env['SQLSRV_DATABASE'],
                user: $env['SQLSRV_USERNAME'],
                password: $env['SQLSRV_PASSWORD'],
                charset: 'utf8',
                options: [
                    SQLSrv::ATTR_PERSISTENT => $persistent,
                    SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
                    SQLSrv::ATTR_DEFAULT_FETCH_MODE => SQLSrv::FETCH_OBJ,
                    SQLSrv::ATTR_REPORT => SQLSrv::REPORT_ERROR | SQLSrv::REPORT_STRICT
                ],
                exception: true
            );
        }
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
        /** @var Connection|OCIConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_OCI_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'oci',
                host: $env['OCI_HOST'],
                port: (int) $env['OCI_PORT'],
                database: $env['OCI_DATABASE'],
                user: $env['OCI_USERNAME'],
                password: $env['OCI_PASSWORD'],
                charset: 'utf8',
                options: [
                    OCI::ATTR_PERSISTENT => $persistent,
                    OCI::ATTR_CONNECT_TIMEOUT => 28800,
                    OCI::ATTR_DEFAULT_FETCH_MODE => OCI::FETCH_OBJ,
                    OCI::ATTR_REPORT => OCI::REPORT_ERROR | OCI::REPORT_STRICT
                ],
                exception: true
            );
        } else {
            return $constructor(
                host: $env['OCI_HOST'],
                port: (int) $env['OCI_PORT'],
                database: $env['OCI_DATABASE'],
                user: $env['OCI_USERNAME'],
                password: $env['OCI_PASSWORD'],
                charset: 'utf8',
                options: [
                    OCI::ATTR_PERSISTENT => $persistent,
                    OCI::ATTR_CONNECT_TIMEOUT => 28800,
                    OCI::ATTR_DEFAULT_FETCH_MODE => OCI::FETCH_OBJ,
                    OCI::ATTR_REPORT => OCI::REPORT_ERROR | OCI::REPORT_STRICT
                ],
                exception: true
            );
        }
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
        /** @var Connection|FirebirdConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_FIREBIRD_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'firebird',
                host: $env['IBASE_HOST'],
                port: (int) $env['IBASE_PORT'],
                database: $env['IBASE_DATABASE'],
                user: $env['IBASE_USERNAME'],
                password: $env['IBASE_PASSWORD'],
                charset: 'utf8',
                options: [
                    Firebird::ATTR_PERSISTENT => $persistent,
                    Firebird::ATTR_CONNECT_TIMEOUT => 28800,
                    Firebird::ATTR_DEFAULT_FETCH_MODE => Firebird::FETCH_OBJ,
                    Firebird::ATTR_REPORT => Firebird::REPORT_ERROR | Firebird::REPORT_STRICT
                ],
                exception: true
            );
        } else {
            return $constructor(
                host: $env['IBASE_HOST'],
                port: (int) $env['IBASE_PORT'],
                database: $env['IBASE_DATABASE'],
                user: $env['IBASE_USERNAME'],
                password: $env['IBASE_PASSWORD'],
                charset: 'utf8',
                options: [
                    Firebird::ATTR_PERSISTENT => $persistent,
                    Firebird::ATTR_CONNECT_TIMEOUT => 28800,
                    Firebird::ATTR_DEFAULT_FETCH_MODE => Firebird::FETCH_OBJ,
                    Firebird::ATTR_REPORT => Firebird::REPORT_ERROR | Firebird::REPORT_STRICT
                ],
                exception: true
            );
        }
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
        /** @var Connection|SQLiteConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_SQLITE_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'sqlite',
                database: $env['SQLITE_DATABASE'],
                charset: 'utf8',
                options: [
                    SQLite::ATTR_OPEN_READONLY => false,
                    SQLite::ATTR_OPEN_READWRITE => true,
                    SQLite::ATTR_OPEN_CREATE => true,
                    SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                    SQLite::ATTR_PERSISTENT => $persistent,
                    SQLite::ATTR_AUTOCOMMIT => true,
                    SQLite::ATTR_DEFAULT_FETCH_MODE => SQLite::FETCH_OBJ,
                    SQLite::ATTR_REPORT => SQLite::REPORT_ERROR | SQLite::REPORT_STRICT
                ],
                exception: true
            );
        } else {
            return $constructor(
                database: $env['SQLITE_DATABASE'],
                charset: 'utf8',
                options: [
                    SQLite::ATTR_OPEN_READONLY => false,
                    SQLite::ATTR_OPEN_READWRITE => true,
                    SQLite::ATTR_OPEN_CREATE => true,
                    SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                    SQLite::ATTR_PERSISTENT => $persistent,
                    SQLite::ATTR_AUTOCOMMIT => true,
                    SQLite::ATTR_DEFAULT_FETCH_MODE => SQLite::FETCH_OBJ,
                    SQLite::ATTR_REPORT => SQLite::REPORT_ERROR | SQLite::REPORT_STRICT
                ],
                exception: true
            );
        }
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
        /** @var Connection|SQLiteConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_SQLITE_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'sqlite',
                database: $env['SQLITE_DATABASE_MEMORY'],
                charset: 'utf8',
                options: [
                    SQLite::ATTR_OPEN_READONLY => false,
                    SQLite::ATTR_OPEN_READWRITE => true,
                    SQLite::ATTR_OPEN_CREATE => true,
                    SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                    SQLite::ATTR_PERSISTENT => $persistent,
                    SQLite::ATTR_AUTOCOMMIT => true,
                    SQLite::ATTR_DEFAULT_FETCH_MODE => SQLite::FETCH_OBJ,
                    SQLite::ATTR_REPORT => SQLite::REPORT_ERROR | SQLite::REPORT_STRICT
                ],
                exception: true
            );
        } else {
            return $constructor(
                database: $env['SQLITE_DATABASE_MEMORY'],
                charset: 'utf8',
                options: [
                    SQLite::ATTR_OPEN_READONLY => false,
                    SQLite::ATTR_OPEN_READWRITE => true,
                    SQLite::ATTR_OPEN_CREATE => true,
                    SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                    SQLite::ATTR_PERSISTENT => $persistent,
                    SQLite::ATTR_AUTOCOMMIT => true,
                    SQLite::ATTR_DEFAULT_FETCH_MODE => SQLite::FETCH_OBJ,
                    SQLite::ATTR_REPORT => SQLite::REPORT_ERROR | SQLite::REPORT_STRICT
                ],
                exception: true
            );
        }
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
        /** @var Connection|PDOConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PDO_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'pdo',
                driver: 'mysql',
                host: $env['MYSQL_HOST'],
                port: (int) $env['MYSQL_PORT'],
                database: $env['MYSQL_DATABASE'],
                user: $env['MYSQL_USERNAME'],
                password: $env['MYSQL_PASSWORD'],
                charset: 'utf8',
                options: [
                    PDO::ATTR_PERSISTENT => $persistent,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'mysql',
                host: $env['MYSQL_HOST'],
                port: (int) $env['MYSQL_PORT'],
                database: $env['MYSQL_DATABASE'],
                user: $env['MYSQL_USERNAME'],
                password: $env['MYSQL_PASSWORD'],
                charset: 'utf8',
                options: [
                    PDO::ATTR_PERSISTENT => $persistent,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ],
                exception: true
            );
        }
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
        /** @var Connection|PDOConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PDO_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'pdo',
                driver: 'pgsql',
                host: $env['PGSQL_HOST'],
                port: (int) $env['PGSQL_PORT'],
                database: $env['PGSQL_DATABASE'],
                user: $env['PGSQL_USERNAME'],
                password: $env['PGSQL_PASSWORD'],
                charset: 'utf8',
                options: [
                    PDO::ATTR_PERSISTENT => $persistent,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'pgsql',
                host: $env['PGSQL_HOST'],
                port: (int) $env['PGSQL_PORT'],
                database: $env['PGSQL_DATABASE'],
                user: $env['PGSQL_USERNAME'],
                password: $env['PGSQL_PASSWORD'],
                charset: 'utf8',
                options: [
                    PDO::ATTR_PERSISTENT => $persistent,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ],
                exception: true
            );
        }
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
        /** @var Connection|PDOConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PDO_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'pdo',
                driver: 'sqlsrv',
                host: $env['SQLSRV_HOST'],
                port: (int) $env['SQLSRV_PORT'],
                database: $env['SQLSRV_DATABASE'],
                user: $env['SQLSRV_USERNAME'],
                password: $env['SQLSRV_PASSWORD'],
                charset: 'utf8',
                options: [
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'sqlsrv',
                host: $env['SQLSRV_HOST'],
                port: (int) $env['SQLSRV_PORT'],
                database: $env['SQLSRV_DATABASE'],
                user: $env['SQLSRV_USERNAME'],
                password: $env['SQLSRV_PASSWORD'],
                charset: 'utf8',
                options: [
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ],
                exception: true
            );
        }
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
        /** @var Connection|PDOConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PDO_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'pdo',
                driver: 'oci',
                host: $env['OCI_HOST'],
                port: (int) $env['OCI_PORT'],
                database: $env['OCI_DATABASE'],
                user: $env['OCI_USERNAME'],
                password: $env['OCI_PASSWORD'],
                charset: 'utf8',
                options: [
                    PDO::ATTR_PERSISTENT => $persistent,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'oci',
                host: $env['OCI_HOST'],
                port: (int) $env['OCI_PORT'],
                database: $env['OCI_DATABASE'],
                user: $env['OCI_USERNAME'],
                password: $env['OCI_PASSWORD'],
                charset: 'utf8',
                options: [
                    PDO::ATTR_PERSISTENT => $persistent,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ],
                exception: true
            );
        }
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
        /** @var Connection|PDOConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PDO_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'pdo',
                driver: 'firebird',
                host: $env['FBIRD_HOST'],
                port: (int) $env['FBIRD_PORT'],
                database: $env['FBIRD_DATABASE'],
                user: $env['FBIRD_USERNAME'],
                password: $env['FBIRD_PASSWORD'],
                charset: 'utf8',
                options: [
                    PDO::ATTR_PERSISTENT => $persistent,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'firebird',
                host: $env['FBIRD_HOST'],
                port: (int) $env['FBIRD_PORT'],
                database: $env['FBIRD_DATABASE'],
                user: $env['FBIRD_USERNAME'],
                password: $env['FBIRD_PASSWORD'],
                charset: 'utf8',
                options: [
                    PDO::ATTR_PERSISTENT => $persistent,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ],
                exception: true
            );
        }
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
        /** @var Connection|PDOConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PDO_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'pdo',
                driver: 'sqlite',
                database: $env['SQLITE_DATABASE'],
                charset: 'utf8',
                options: [
                    PDO::ATTR_PERSISTENT => $persistent,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'sqlite',
                database: $env['SQLITE_DATABASE'],
                charset: 'utf8',
                options: [
                    PDO::ATTR_PERSISTENT => $persistent,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ],
                exception: true
            );
        }
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
        /** @var Connection|PDOConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_PDO_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'pdo',
                driver: 'sqlite',
                database: $env['SQLITE_DATABASE_MEMORY'],
                charset: 'utf8',
                options: [
                    PDO::ATTR_PERSISTENT => $persistent,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'sqlite',
                database: $env['SQLITE_DATABASE_MEMORY'],
                charset: 'utf8',
                options: [
                    PDO::ATTR_PERSISTENT => $persistent,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                ],
                exception: true
            );
        }
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'odbc',
                driver: 'mysql',
                host: $env['MYSQL_HOST'],
                port: (int) $env['MYSQL_PORT'],
                database: $env['MYSQL_DATABASE'],
                user: $env['MYSQL_USERNAME'],
                password: $env['MYSQL_PASSWORD'],
                charset: $env['MYSQL_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'mysql',
                host: $env['MYSQL_HOST'],
                port: (int) $env['MYSQL_PORT'],
                database: $env['MYSQL_DATABASE'],
                user: $env['MYSQL_USERNAME'],
                password: $env['MYSQL_PASSWORD'],
                charset: $env['MYSQL_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        }
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'odbc',
                driver: 'pgsql',
                host: $env['PGSQL_HOST'],
                port: (int) $env['PGSQL_PORT'],
                database: $env['PGSQL_DATABASE'],
                user: $env['PGSQL_USERNAME'],
                password: $env['PGSQL_PASSWORD'],
                charset: $env['PGSQL_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'pgsql',
                host: $env['PGSQL_HOST'],
                port: (int) $env['PGSQL_PORT'],
                database: $env['PGSQL_DATABASE'],
                user: $env['PGSQL_USERNAME'],
                password: $env['PGSQL_PASSWORD'],
                charset: $env['PGSQL_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        }
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'odbc',
                driver: 'sqlsrv',
                host: $env['SQLSRV_HOST'],
                port: (int) $env['SQLSRV_PORT'],
                database: $env['SQLSRV_DATABASE'],
                user: $env['SQLSRV_USERNAME'],
                password: $env['SQLSRV_PASSWORD'],
                charset: $env['SQLSRV_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'sqlsrv',
                host: $env['SQLSRV_HOST'],
                port: (int) $env['SQLSRV_PORT'],
                database: $env['SQLSRV_DATABASE'],
                user: $env['SQLSRV_USERNAME'],
                password: $env['SQLSRV_PASSWORD'],
                charset: $env['SQLSRV_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        }
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'odbc',
                driver: 'oci',
                host: $env['OCI_HOST'],
                port: (int) $env['OCI_PORT'],
                database: $env['OCI_DATABASE'],
                user: $env['OCI_USERNAME'],
                password: $env['OCI_PASSWORD'],
                charset: $env['OCI_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'oci',
                host: $env['OCI_HOST'],
                port: (int) $env['OCI_PORT'],
                database: $env['OCI_DATABASE'],
                user: $env['OCI_USERNAME'],
                password: $env['OCI_PASSWORD'],
                charset: $env['OCI_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        }
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'odbc',
                driver: 'firebird',
                host: $env['FBIRD_HOST'],
                port: (int) $env['FBIRD_PORT'],
                database: $env['FBIRD_DATABASE'],
                user: $env['FBIRD_USERNAME'],
                password: $env['FBIRD_PASSWORD'],
                charset: $env['FBIRD_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'firebird',
                host: $env['FBIRD_HOST'],
                port: (int) $env['FBIRD_PORT'],
                database: $env['FBIRD_DATABASE'],
                user: $env['FBIRD_USERNAME'],
                password: $env['FBIRD_PASSWORD'],
                charset: $env['FBIRD_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        }
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'odbc',
                driver: 'sqlite',
                database: $env['SQLITE_DATABASE'],
                charset: $env['SQLITE_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'sqlite',
                database: $env['SQLITE_DATABASE'],
                charset: $env['SQLITE_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        }
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'odbc',
                driver: 'access',
                database: $env['ACCESS_DATABASE'],
                user: $env['ACCESS_USERNAME'],
                password: $env['ACCESS_PASSWORD'],
                charset: $env['ACCESS_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'access',
                database: $env['ACCESS_DATABASE'],
                user: $env['ACCESS_USERNAME'],
                password: $env['ACCESS_PASSWORD'],
                charset: $env['ACCESS_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        }
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'odbc',
                driver: 'excel',
                database: $env['EXCEL_DATABASE'],
                charset: $env['EXCEL_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'excel',
                database: $env['EXCEL_DATABASE'],
                charset: $env['EXCEL_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        }
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'odbc',
                driver: 'text',
                database: $env['TEXT_DATABASE'],
                charset: $env['TEXT_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'text',
                database: $env['TEXT_DATABASE'],
                charset: $env['TEXT_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        }
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_ODBC_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'odbc',
                driver: 'sqlite',
                database: $env['SQLITE_DATABASE_MEMORY'],
                charset: $env['SQLITE_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        } else {
            return $constructor(
                driver: 'sqlite',
                database: $env['SQLITE_DATABASE_MEMORY'],
                charset: $env['SQLITE_CHARSET'],
                options: [
                    ODBC::ATTR_PERSISTENT => $persistent,
                    ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                    ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ,
                    ODBC::ATTR_REPORT => ODBC::REPORT_ERROR | ODBC::REPORT_STRICT,
                    ODBC::ATTR_SQL_CUR_USE => ODBC::SQL_CUR_USE_ODBC
                ],
                exception: true
            );
        }
    }

    public static function nativeJSON(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|JSONConnection {
        /** @var Connection|JSONConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_JSON_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'json',
                database: $env['JSON_DATABASE'],
                charset: $env['JSON_CHARSET'],
                options: [
                    JSON::ATTR_PERSISTENT => $persistent,
                    JSON::ATTR_AUTOCOMMIT => true,
                    JSON::ATTR_CONNECT_TIMEOUT => 28800,
                    JSON::ATTR_DEFAULT_FETCH_MODE => JSON::FETCH_OBJ,
                    JSON::ATTR_REPORT => JSON::REPORT_ERROR | JSON::REPORT_STRICT,
                    JSON::ATTR_JSON_PRETTY_PRINT => true
                ],
                exception: true
            );
        } else {
            return $constructor(
                database: $env['JSON_DATABASE'],
                charset: $env['JSON_CHARSET'],
                options: [
                    JSON::ATTR_PERSISTENT => $persistent,
                    JSON::ATTR_AUTOCOMMIT => true,
                    JSON::ATTR_CONNECT_TIMEOUT => 28800,
                    JSON::ATTR_DEFAULT_FETCH_MODE => JSON::FETCH_OBJ,
                    JSON::ATTR_REPORT => JSON::REPORT_ERROR | JSON::REPORT_STRICT,
                    JSON::ATTR_JSON_PRETTY_PRINT => true
                ],
                exception: true
            );
        }
    }

    public static function nativeXML(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|XMLConnection {
        /** @var Connection|XMLConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_XML_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'xml',
                database: $env['XML_DATABASE'],
                charset: $env['XML_CHARSET'],
                options: [
                    XML::ATTR_PERSISTENT => $persistent,
                    XML::ATTR_AUTOCOMMIT => true,
                    XML::ATTR_CONNECT_TIMEOUT => 28800,
                    XML::ATTR_DEFAULT_FETCH_MODE => XML::FETCH_OBJ,
                    XML::ATTR_REPORT => XML::REPORT_ERROR | XML::REPORT_STRICT
                ],
                exception: true
            );
        } else {
            return $constructor(
                database: $env['XML_DATABASE'],
                charset: $env['XML_CHARSET'],
                options: [
                    XML::ATTR_PERSISTENT => $persistent,
                    XML::ATTR_AUTOCOMMIT => true,
                    XML::ATTR_CONNECT_TIMEOUT => 28800,
                    XML::ATTR_DEFAULT_FETCH_MODE => XML::FETCH_OBJ,
                    XML::ATTR_REPORT => XML::REPORT_ERROR | XML::REPORT_STRICT
                ],
                exception: true
            );
        }
    }

    public static function nativeYAML(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|YAMLConnection {
        /** @var Connection|YAMLConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_YAML_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'yaml',
                database: $env['YAML_DATABASE'],
                charset: $env['YAML_CHARSET'],
                options: [
                    YAML::ATTR_PERSISTENT => $persistent,
                    YAML::ATTR_AUTOCOMMIT => true,
                    YAML::ATTR_CONNECT_TIMEOUT => 28800,
                    YAML::ATTR_DEFAULT_FETCH_MODE => YAML::FETCH_OBJ,
                    YAML::ATTR_REPORT => YAML::REPORT_ERROR | YAML::REPORT_STRICT
                ],
                exception: true
            );
        } else {
            return $constructor(
                database: $env['YAML_DATABASE'],
                charset: $env['YAML_CHARSET'],
                options: [
                    YAML::ATTR_PERSISTENT => $persistent,
                    YAML::ATTR_AUTOCOMMIT => true,
                    YAML::ATTR_CONNECT_TIMEOUT => 28800,
                    YAML::ATTR_DEFAULT_FETCH_MODE => YAML::FETCH_OBJ,
                    YAML::ATTR_REPORT => YAML::REPORT_ERROR | YAML::REPORT_STRICT
                ],
                exception: true
            );
        }
    }

    public static function nativeCSV(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|CSVConnection {
        /** @var Connection|CSVConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_CSV_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'csv',
                database: $env['CSV_DATABASE'],
                charset: $env['CSV_CHARSET'],
                options: [
                    CSV::ATTR_PERSISTENT => $persistent,
                    CSV::ATTR_AUTOCOMMIT => true,
                    CSV::ATTR_CONNECT_TIMEOUT => 28800,
                    CSV::ATTR_DEFAULT_FETCH_MODE => CSV::FETCH_OBJ,
                    CSV::ATTR_REPORT => CSV::REPORT_ERROR | CSV::REPORT_STRICT
                ],
                exception: true
            );
        } else {
            return $constructor(
                database: $env['CSV_DATABASE'],
                charset: $env['CSV_CHARSET'],
                options: [
                    CSV::ATTR_PERSISTENT => $persistent,
                    CSV::ATTR_AUTOCOMMIT => true,
                    CSV::ATTR_CONNECT_TIMEOUT => 28800,
                    CSV::ATTR_DEFAULT_FETCH_MODE => CSV::FETCH_OBJ,
                    CSV::ATTR_REPORT => CSV::REPORT_ERROR | CSV::REPORT_STRICT
                ],
                exception: true
            );
        }
    }

    public static function nativeINI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|INIConnection {
        /** @var Connection|INIConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION()->value : Entity::CLASS_INI_ENGINE()->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        if ($strategy) {
            return $constructor(
                engine: 'ini',
                database: $env['INI_DATABASE'],
                charset: $env['INI_CHARSET'],
                options: [
                    INI::ATTR_PERSISTENT => $persistent,
                    INI::ATTR_AUTOCOMMIT => true,
                    INI::ATTR_CONNECT_TIMEOUT => 28800,
                    INI::ATTR_DEFAULT_FETCH_MODE => INI::FETCH_OBJ,
                    INI::ATTR_REPORT => INI::REPORT_ERROR | INI::REPORT_STRICT
                ],
                exception: true
            );
        } else {
            return $constructor(
                database: $env['INI_DATABASE'],
                charset: $env['INI_CHARSET'],
                options: [
                    INI::ATTR_PERSISTENT => $persistent,
                    INI::ATTR_AUTOCOMMIT => true,
                    INI::ATTR_CONNECT_TIMEOUT => 28800,
                    INI::ATTR_DEFAULT_FETCH_MODE => INI::FETCH_OBJ,
                    INI::ATTR_REPORT => INI::REPORT_ERROR | INI::REPORT_STRICT
                ],
                exception: true
            );
        }
    }
}
