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

class StaticArray
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
        /** @var Connection|MySQLiConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_MYSQLI_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'mysqli'] : [],
            'host' => $env['MYSQL_HOST'],
            'port' => (int) $env['MYSQL_PORT'],
            'database' => $env['MYSQL_DATABASE'],
            'user' => $env['MYSQL_USERNAME'],
            'password' => $env['MYSQL_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                MySQL::ATTR_PERSISTENT => $persistent,
                MySQL::ATTR_AUTOCOMMIT => true,
                MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
                MySQL::ATTR_SET_CHARSET_NAME => "utf8",
                MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
                MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
                MySQL::ATTR_OPT_READ_TIMEOUT => 30,
                MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M",
                MySQL::ATTR_DEFAULT_FETCH_MODE => MySQL::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|PgSQLConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PGSQL_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'pgsql'] : [],
            'host' => $env['PGSQL_HOST'],
            'port' => (int) $env['PGSQL_PORT'],
            'database' => $env['PGSQL_DATABASE'],
            'user' => $env['PGSQL_USERNAME'],
            'password' => $env['PGSQL_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                PgSQL::ATTR_PERSISTENT => $persistent,
                PgSQL::ATTR_CONNECT_ASYNC => true,
                PgSQL::ATTR_CONNECT_FORCE_NEW => true,
                PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
                PgSQL::ATTR_DEFAULT_FETCH_MODE => PgSQL::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|SQLSrvConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLSRV_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'sqlsrv'] : [],
            'host' => $env['SQLSRV_HOST'],
            'port' => (int) $env['SQLSRV_PORT'],
            'database' => $env['SQLSRV_DATABASE'],
            'user' => $env['SQLSRV_USERNAME'],
            'password' => $env['SQLSRV_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                SQLSrv::ATTR_PERSISTENT => $persistent,
                SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
                SQLSrv::ATTR_DEFAULT_FETCH_MODE => SQLSrv::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|OCIConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_OCI_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'oci'] : [],
            'host' => $env['OCI_HOST'],
            'port' => (int) $env['OCI_PORT'],
            'database' => $env['OCI_DATABASE'],
            'user' => $env['OCI_USERNAME'],
            'password' => $env['OCI_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                OCI::ATTR_PERSISTENT => $persistent,
                OCI::ATTR_CONNECT_TIMEOUT => 28800,
                OCI::ATTR_DEFAULT_FETCH_MODE => OCI::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|FirebirdConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_FIREBIRD_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'firebird'] : [],
            'host' => $env['IBASE_HOST'],
            'port' => (int) $env['IBASE_PORT'],
            'database' => $env['IBASE_DATABASE'],
            'user' => $env['IBASE_USERNAME'],
            'password' => $env['IBASE_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                Firebird::ATTR_PERSISTENT => $persistent,
                Firebird::ATTR_CONNECT_TIMEOUT => 28800,
                Firebird::ATTR_DEFAULT_FETCH_MODE => Firebird::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|SQLiteConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLITE_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'sqlite'] : [],
            'database' => $env['SQLITE_DATABASE'],
            'charset' => 'utf8',
            'options' => [
                SQLite::ATTR_OPEN_READONLY => false,
                SQLite::ATTR_OPEN_READWRITE => true,
                SQLite::ATTR_OPEN_CREATE => true,
                SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                SQLite::ATTR_PERSISTENT => $persistent,
                SQLite::ATTR_AUTOCOMMIT => true,
                SQLite::ATTR_DEFAULT_FETCH_MODE => SQLite::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|SQLiteConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLITE_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'sqlite'] : [],
            'database' => $env['SQLITE_DATABASE_MEMORY'],
            'charset' => 'utf8',
            'options' => [
                SQLite::ATTR_OPEN_READONLY => false,
                SQLite::ATTR_OPEN_READWRITE => true,
                SQLite::ATTR_OPEN_CREATE => true,
                SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                SQLite::ATTR_PERSISTENT => $persistent,
                SQLite::ATTR_AUTOCOMMIT => true,
                SQLite::ATTR_DEFAULT_FETCH_MODE => SQLite::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|PDOConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'pdo'] : [],
            'driver' => 'mysql',
            'host' => $env['MYSQL_HOST'],
            'port' => (int) $env['MYSQL_PORT'],
            'database' => $env['MYSQL_DATABASE'],
            'user' => $env['MYSQL_USERNAME'],
            'password' => $env['MYSQL_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|PDOConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'pdo'] : [],
            'driver' => 'pgsql',
            'host' => $env['PGSQL_HOST'],
            'port' => (int) $env['PGSQL_PORT'],
            'database' => $env['PGSQL_DATABASE'],
            'user' => $env['PGSQL_USERNAME'],
            'password' => $env['PGSQL_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|PDOConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'pdo'] : [],
            'driver' => 'sqlsrv',
            'host' => $env['SQLSRV_HOST'],
            'port' => (int) $env['SQLSRV_PORT'],
            'database' => $env['SQLSRV_DATABASE'],
            'user' => $env['SQLSRV_USERNAME'],
            'password' => $env['SQLSRV_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|PDOConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'pdo'] : [],
            'driver' => 'oci',
            'host' => $env['OCI_HOST'],
            'port' => (int) $env['OCI_PORT'],
            'database' => $env['OCI_DATABASE'],
            'user' => $env['OCI_USERNAME'],
            'password' => $env['OCI_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|PDOConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'pdo'] : [],
            'driver' => 'firebird',
            'host' => $env['FBIRD_HOST'],
            'port' => (int) $env['FBIRD_PORT'],
            'database' => $env['FBIRD_DATABASE'],
            'user' => $env['FBIRD_USERNAME'],
            'password' => $env['FBIRD_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|PDOConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'pdo'] : [],
            'driver' => 'sqlite',
            'database' => $env['SQLITE_DATABASE'],
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|PDOConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'pdo'] : [],
            'driver' => 'sqlite',
            'database' => $env['SQLITE_DATABASE_MEMORY'],
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'odbc'] : [],
            'driver' => 'mysql',
            'host' => $env['MYSQL_HOST'],
            'port' => (int) $env['MYSQL_PORT'],
            'database' => $env['MYSQL_DATABASE'],
            'user' => $env['MYSQL_USERNAME'],
            'password' => $env['MYSQL_PASSWORD'],
            'charset' => $env['MYSQL_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'odbc'] : [],
            'driver' => 'pgsql',
            'host' => $env['PGSQL_HOST'],
            'port' => (int) $env['PGSQL_PORT'],
            'database' => $env['PGSQL_DATABASE'],
            'user' => $env['PGSQL_USERNAME'],
            'password' => $env['PGSQL_PASSWORD'],
            'charset' => $env['PGSQL_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'odbc'] : [],
            'driver' => 'sqlsrv',
            'host' => $env['SQLSRV_HOST'],
            'port' => (int) $env['SQLSRV_PORT'],
            'database' => $env['SQLSRV_DATABASE'],
            'user' => $env['SQLSRV_USERNAME'],
            'password' => $env['SQLSRV_PASSWORD'],
            'charset' => $env['SQLSRV_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'odbc'] : [],
            'driver' => 'oci',
            'host' => $env['OCI_HOST'],
            'port' => (int) $env['OCI_PORT'],
            'database' => $env['OCI_DATABASE'],
            'user' => $env['OCI_USERNAME'],
            'password' => $env['OCI_PASSWORD'],
            'charset' => $env['OCI_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'odbc'] : [],
            'driver' => 'firebird',
            'host' => $env['FBIRD_HOST'],
            'port' => (int) $env['FBIRD_PORT'],
            'database' => $env['FBIRD_DATABASE'],
            'user' => $env['FBIRD_USERNAME'],
            'password' => $env['FBIRD_PASSWORD'],
            'charset' => $env['FBIRD_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'odbc'] : [],
            'driver' => 'sqlite',
            'database' => $env['SQLITE_DATABASE'],
            'charset' => $env['SQLITE_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'odbc'] : [],
            'driver' => 'access',
            'database' => $env['ACCESS_DATABASE'],
            'user' => $env['ACCESS_USERNAME'],
            'password' => $env['ACCESS_PASSWORD'],
            'charset' => $env['ACCESS_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'odbc'] : [],
            'driver' => 'excel',
            'database' => $env['EXCEL_DATABASE'],
            'charset' => $env['EXCEL_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'odbc'] : [],
            'driver' => 'text',
            'database' => $env['TEXT_DATABASE'],
            'charset' => $env['TEXT_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
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
        /** @var Connection|ODBCConnection $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;

        /** @var callable $constructor */
        $constructor = [$className, 'new'];

        return $constructor([
            ...$strategy ? ['engine' => 'odbc'] : [],
            'driver' => 'sqlite',
            'database' => $env['SQLITE_DATABASE_MEMORY'],
            'charset' => $env['SQLITE_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
    }
}
