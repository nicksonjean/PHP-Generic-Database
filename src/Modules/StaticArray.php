<?php

declare(strict_types=1);

namespace GenericDatabase\Modules;

use GenericDatabase\Core\Entity;
use GenericDatabase\Connection;
use GenericDatabase\Engine\MySQLiEngine;
use GenericDatabase\Engine\MySQLi\MySQL;
use GenericDatabase\Engine\PgSQLEngine;
use GenericDatabase\Engine\PgSQL\PgSQL;
use GenericDatabase\Engine\SQLSrvEngine;
use GenericDatabase\Engine\SQLSrv\SQLSrv;
use GenericDatabase\Engine\OCIEngine;
use GenericDatabase\Engine\OCI\OCI;
use GenericDatabase\Engine\FirebirdEngine;
use GenericDatabase\Engine\Firebird\Firebird;
use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Engine\SQLite\SQLite;
use GenericDatabase\Engine\ODBCEngine;
use GenericDatabase\Engine\ODBC\ODBC;
use GenericDatabase\Engine\PDOEngine;
use PDO;

class StaticArray
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
        /** @var Connection|MySQLiEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_MYSQLI_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'mysqli';
        }
        $parameters = array_merge($parameters, [
            'host' => $env['MYSQL_HOST'],
            'port' => (int)$env['MYSQL_PORT'],
            'database' => $env['MYSQL_DATABASE'],
            'user' => $env['MYSQL_USER'],
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
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
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
        /** @var Connection|PgSQLEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PGSQL_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'pgsql';
        }
        $parameters = array_merge($parameters, [
            'host' => $env['PGSQL_HOST'],
            'port' => (int)$env['PGSQL_PORT'],
            'database' => $env['PGSQL_DATABASE'],
            'user' => $env['PGSQL_USER'],
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
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
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
        /** @var Connection|SQLSrvEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLSRV_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'sqlsrv';
        }
        $parameters = array_merge($parameters, [
            'host' => $env['SQLSRV_HOST'],
            'port' => (int)$env['SQLSRV_PORT'],
            'database' => $env['SQLSRV_DATABASE'],
            'user' => $env['SQLSRV_USER'],
            'password' => $env['SQLSRV_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                SQLSrv::ATTR_PERSISTENT => $persistent,
                SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
                SQLSrv::ATTR_DEFAULT_FETCH_MODE => SQLSrv::FETCH_OBJ
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
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
        /** @var Connection|OCIEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_OCI_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'oci';
        }
        $parameters = array_merge($parameters, [
            'host' => $env['OCI_HOST'],
            'port' => (int)$env['OCI_PORT'],
            'database' => $env['OCI_DATABASE'],
            'user' => $env['OCI_USER'],
            'password' => $env['OCI_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                OCI::ATTR_PERSISTENT => $persistent,
                OCI::ATTR_CONNECT_TIMEOUT => 28800,
                OCI::ATTR_DEFAULT_FETCH_MODE => OCI::FETCH_OBJ
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|FirebirdEngine
     */
    public static function nativeFirebird(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|FirebirdEngine {
        /** @var Connection|FirebirdEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_FIREBIRD_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'firebird';
        }
        $parameters = array_merge($parameters, [
            'host' => $env['FIREBIRD_HOST'],
            'port' => (int)$env['FIREBIRD_PORT'],
            'database' => $env['FIREBIRD_DATABASE'],
            'user' => $env['FIREBIRD_USER'],
            'password' => $env['FIREBIRD_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                Firebird::ATTR_PERSISTENT => $persistent,
                Firebird::ATTR_CONNECT_TIMEOUT => 28800,
                Firebird::ATTR_DEFAULT_FETCH_MODE => Firebird::FETCH_OBJ
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
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
        /** @var Connection|SQLiteEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLITE_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'sqlite';
        }
        $parameters = array_merge($parameters, [
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
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
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
        /** @var Connection|SQLiteEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLITE_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'sqlite';
        }
        $parameters = array_merge($parameters, [
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
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
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
        /** @var Connection|PDOEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'pdo';
        }
        $parameters = array_merge($parameters, [
            'driver' => 'mysql',
            'host' => $env['MYSQL_HOST'],
            'port' => (int)$env['MYSQL_PORT'],
            'database' => $env['MYSQL_DATABASE'],
            'user' => $env['MYSQL_USER'],
            'password' => $env['MYSQL_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
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
        /** @var Connection|PDOEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'pdo';
        }
        $parameters = array_merge($parameters, [
            'driver' => 'pgsql',
            'host' => $env['PGSQL_HOST'],
            'port' => (int)$env['PGSQL_PORT'],
            'database' => $env['PGSQL_DATABASE'],
            'user' => $env['PGSQL_USER'],
            'password' => $env['PGSQL_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
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
        /** @var Connection|PDOEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'pdo';
        }
        $parameters = array_merge($parameters, [
            'driver' => 'sqlsrv',
            'host' => $env['SQLSRV_HOST'],
            'port' => (int)$env['SQLSRV_PORT'],
            'database' => $env['SQLSRV_DATABASE'],
            'user' => $env['SQLSRV_USER'],
            'password' => $env['SQLSRV_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
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
        /** @var Connection|PDOEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'pdo';
        }
        $parameters = array_merge($parameters, [
            'driver' => 'oci',
            'host' => $env['OCI_HOST'],
            'port' => (int)$env['OCI_PORT'],
            'database' => $env['OCI_DATABASE'],
            'user' => $env['OCI_USER'],
            'password' => $env['OCI_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
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
        /** @var Connection|PDOEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'pdo';
        }
        $parameters = array_merge($parameters, [
            'driver' => 'firebird',
            'host' => $env['FIREBIRD_HOST'],
            'port' => (int)$env['FIREBIRD_PORT'],
            'database' => $env['FIREBIRD_DATABASE'],
            'user' => $env['FIREBIRD_USER'],
            'password' => $env['FIREBIRD_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
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
        /** @var Connection|PDOEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'pdo';
        }
        $parameters = array_merge($parameters, [
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
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
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
        /** @var Connection|PDOEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'pdo';
        }
        $parameters = array_merge($parameters, [
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
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCEngine
     */
    public static function odbcMySQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCEngine {
        /** @var Connection|ODBCEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'odbc';
        }
        $parameters = array_merge($parameters, [
            'driver' => 'mysql',
            'host' => $env['MYSQL_HOST'],
            'port' => (int)$env['MYSQL_PORT'],
            'database' => $env['MYSQL_DATABASE'],
            'user' => $env['MYSQL_USER'],
            'password' => $env['MYSQL_PASSWORD'],
            'charset' => $env['MYSQL_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCEngine
     */
    public static function odbcPgSQL(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCEngine {
        /** @var Connection|ODBCEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'odbc';
        }
        $parameters = array_merge($parameters, [
            'driver' => 'pgsql',
            'host' => $env['PGSQL_HOST'],
            'port' => (int)$env['PGSQL_PORT'],
            'database' => $env['PGSQL_DATABASE'],
            'user' => $env['PGSQL_USER'],
            'password' => $env['PGSQL_PASSWORD'],
            'charset' => $env['PGSQL_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCEngine
     */
    public static function odbcSQLSrv(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCEngine {
        /** @var Connection|ODBCEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'odbc';
        }
        $parameters = array_merge($parameters, [
            'driver' => 'sqlsrv',
            'host' => $env['SQLSRV_HOST'],
            'port' => (int)$env['SQLSRV_PORT'],
            'database' => $env['SQLSRV_DATABASE'],
            'user' => $env['SQLSRV_USER'],
            'password' => $env['SQLSRV_PASSWORD'],
            'charset' => $env['SQLSRV_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCEngine
     */
    public static function odbcOCI(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCEngine {
        /** @var Connection|ODBCEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'odbc';
        }
        $parameters = array_merge($parameters, [
            'driver' => 'oci',
            'host' => $env['OCI_HOST'],
            'port' => (int)$env['OCI_PORT'],
            'database' => $env['OCI_DATABASE'],
            'user' => $env['OCI_USER'],
            'password' => $env['OCI_PASSWORD'],
            'charset' => $env['OCI_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCEngine
     */
    public static function odbcFirebird(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCEngine {
        /** @var Connection|ODBCEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'odbc';
        }
        $parameters = array_merge($parameters, [
            'driver' => 'firebird',
            'host' => $env['FIREBIRD_HOST'],
            'port' => (int)$env['FIREBIRD_PORT'],
            'database' => $env['FIREBIRD_DATABASE'],
            'user' => $env['FIREBIRD_USER'],
            'password' => $env['FIREBIRD_PASSWORD'],
            'charset' => $env['FIREBIRD_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCEngine
     */
    public static function odbcSQLite(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCEngine {
        /** @var Connection|ODBCEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'odbc';
        }
        $parameters = array_merge($parameters, [
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
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCEngine
     */
    public static function odbcAccess(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCEngine {
        /** @var Connection|ODBCEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'odbc';
        }
        $parameters = array_merge($parameters, [
            'driver' => 'access',
            'database' => $env['ACCESS_DATABASE'],
            'user' => $env['ACCESS_USER'],
            'password' => $env['ACCESS_PASSWORD'],
            'charset' => $env['ACCESS_CHARSET'],
            'options' => [
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCEngine
     */
    public static function odbcExcel(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCEngine {
        /** @var Connection|ODBCEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'odbc';
        }
        $parameters = array_merge($parameters, [
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
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCEngine
     */
    public static function odbcText(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCEngine {
        /** @var Connection|ODBCEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'odbc';
        }
        $parameters = array_merge($parameters, [
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
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
    }

    /**
     * @param array $env
     * @param bool $persistent
     * @param bool $strategy
     * @return Connection|ODBCEngine
     */
    public static function odbcMemory(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCEngine {
        /** @var Connection|ODBCEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_ODBC_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'odbc';
        }
        $parameters = array_merge($parameters, [
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
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return call_user_func_array($constructor, [...$parameters]);
    }
}
