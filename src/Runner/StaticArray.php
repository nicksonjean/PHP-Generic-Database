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
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_MYSQLI_ENGINE;
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
                MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return $constructor(...$parameters);
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
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PGSQL_ENGINE;
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
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return $constructor(...$parameters);
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
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_SQLSRV_ENGINE;
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
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return $constructor(...$parameters);
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
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_OCI_ENGINE;
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
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return $constructor(...$parameters);
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
        /** @var Connection|FBirdEngine $className */
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_FBIRD_ENGINE;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'fbird';
        }
        $parameters = array_merge($parameters, [
            'host' => $env['FBIRD_HOST'],
            'port' => (int)$env['FBIRD_PORT'],
            'database' => $env['FBIRD_DATABASE'],
            'user' => $env['FBIRD_USER'],
            'password' => $env['FBIRD_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                FBird::ATTR_PERSISTENT => $persistent,
                FBird::ATTR_CONNECT_TIMEOUT => 28800,
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return $constructor(...$parameters);
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
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_SQLITE_ENGINE;
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
                SQLite::ATTR_AUTOCOMMIT => true
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return $constructor(...$parameters);
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
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_SQLITE_ENGINE;
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
                SQLite::ATTR_AUTOCOMMIT => true
            ],
            'exception' => true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return $constructor(...$parameters);
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
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PDO_ENGINE;
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
        return $constructor(...$parameters);
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
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PDO_ENGINE;
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
        return $constructor(...$parameters);
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
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PDO_ENGINE;
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
        return $constructor(...$parameters);
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
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PDO_ENGINE;
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
        return $constructor(...$parameters);
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
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PDO_ENGINE;
        $parameters = [];
        if ($strategy) {
            $parameters['engine'] = 'pdo';
        }
        $parameters = array_merge($parameters, [
            'driver' => 'firebird',
            'host' => $env['FBIRD_HOST'],
            'port' => (int)$env['FBIRD_PORT'],
            'database' => $env['FBIRD_DATABASE'],
            'user' => $env['FBIRD_USER'],
            'password' => $env['FBIRD_PASSWORD'],
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
        return $constructor(...$parameters);
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
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PDO_ENGINE;
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
        return $constructor(...$parameters);
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
        $className = $strategy ? Entity::CLASS_CONNECTION : Entity::CLASS_PDO_ENGINE;
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
        return $constructor(...$parameters);
    }
}
