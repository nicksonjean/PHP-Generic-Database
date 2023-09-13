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

class StaticArgs
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
            $parameters[0] = 'mysqli';
        }
        $parameters = array_merge($parameters, [
            $env['MYSQL_HOST'],
            (int)$env['MYSQL_PORT'],
            $env['MYSQL_DATABASE'],
            $env['MYSQL_USER'],
            $env['MYSQL_PASSWORD'],
            'utf8',
            [
                MySQL::ATTR_PERSISTENT => $persistent,
                MySQL::ATTR_AUTOCOMMIT => true,
                MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
                MySQL::ATTR_SET_CHARSET_NAME => "utf8",
                MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
                MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
                MySQL::ATTR_OPT_READ_TIMEOUT => 30,
                MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
            ],
            true
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
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PGSQL_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters[0] = 'pgsql';
        }
        $parameters = array_merge($parameters, [
            $env['PGSQL_HOST'],
            (int)$env['PGSQL_PORT'],
            $env['PGSQL_DATABASE'],
            $env['PGSQL_USER'],
            $env['PGSQL_PASSWORD'],
            'utf8',
            [
                PgSQL::ATTR_PERSISTENT => $persistent,
                PgSQL::ATTR_CONNECT_ASYNC => true,
                PgSQL::ATTR_CONNECT_FORCE_NEW => true,
                PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
            ],
            true
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
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLSRV_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters[0] = 'sqlsrv';
        }
        $parameters = array_merge($parameters, [
            $env['SQLSRV_HOST'],
            (int)$env['SQLSRV_PORT'],
            $env['SQLSRV_DATABASE'],
            $env['SQLSRV_USER'],
            $env['SQLSRV_PASSWORD'],
            'utf8',
            [
                SQLSrv::ATTR_PERSISTENT => $persistent,
                SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
            ],
            true
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
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_OCI_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters[0] = 'oci';
        }
        $parameters = array_merge($parameters, [
            $env['OCI_HOST'],
            (int)$env['OCI_PORT'],
            $env['OCI_DATABASE'],
            $env['OCI_USER'],
            $env['OCI_PASSWORD'],
            'utf8',
            [
                OCI::ATTR_PERSISTENT => $persistent,
                OCI::ATTR_CONNECT_TIMEOUT => 28800,
            ],
            true
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
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_FBIRD_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters[0] = 'fbird';
        }
        $parameters = array_merge($parameters, [
            $env['FBIRD_HOST'],
            (int)$env['FBIRD_PORT'],
            $env['FBIRD_DATABASE'],
            $env['FBIRD_USER'],
            $env['FBIRD_PASSWORD'],
            'utf8',
            [
                FBird::ATTR_PERSISTENT => $persistent,
                FBird::ATTR_CONNECT_TIMEOUT => 28800,
            ],
            true
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
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLITE_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters[0] = 'sqlite';
        }
        $parameters = array_merge($parameters, [
            $env['SQLITE_DATABASE'],
            'utf8',
            [
                SQLite::ATTR_OPEN_READONLY => false,
                SQLite::ATTR_OPEN_READWRITE => true,
                SQLite::ATTR_OPEN_CREATE => true,
                SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                SQLite::ATTR_PERSISTENT => $persistent,
                SQLite::ATTR_AUTOCOMMIT => true
            ],
            true
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
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_SQLITE_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters[0] = 'sqlite';
        }
        $parameters = array_merge($parameters, [
            $env['SQLITE_DATABASE_MEMORY'],
            'utf8',
            [
                SQLite::ATTR_OPEN_READONLY => false,
                SQLite::ATTR_OPEN_READWRITE => true,
                SQLite::ATTR_OPEN_CREATE => true,
                SQLite::ATTR_CONNECT_TIMEOUT => 28800,
                SQLite::ATTR_PERSISTENT => $persistent,
                SQLite::ATTR_AUTOCOMMIT => true
            ],
            true
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
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters[0] = 'pdo';
        }
        $parameters = array_merge($parameters, [
            'mysql',
            $env['MYSQL_HOST'],
            (int)$env['MYSQL_PORT'],
            $env['MYSQL_DATABASE'],
            $env['MYSQL_USER'],
            $env['MYSQL_PASSWORD'],
            'utf8',
            [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            true
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
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters[0] = 'pdo';
        }
        $parameters = array_merge($parameters, [
            'pgsql',
            $env['PGSQL_HOST'],
            (int)$env['PGSQL_PORT'],
            $env['PGSQL_DATABASE'],
            $env['PGSQL_USER'],
            $env['PGSQL_PASSWORD'],
            'utf8',
            [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            true
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
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters[0] = 'pdo';
        }
        $parameters = array_merge($parameters, [
            'sqlsrv',
            $env['SQLSRV_HOST'],
            (int)$env['SQLSRV_PORT'],
            $env['SQLSRV_DATABASE'],
            $env['SQLSRV_USER'],
            $env['SQLSRV_PASSWORD'],
            'utf8',
            [
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            true
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
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters[0] = 'pdo';
        }
        $parameters = array_merge($parameters, [
            'oci',
            $env['OCI_HOST'],
            (int)$env['OCI_PORT'],
            $env['OCI_DATABASE'],
            $env['OCI_USER'],
            $env['OCI_PASSWORD'],
            'utf8',
            [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            true
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
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters[0] = 'pdo';
        }
        $parameters = array_merge($parameters, [
            'firebird',
            $env['FBIRD_HOST'],
            (int)$env['FBIRD_PORT'],
            $env['FBIRD_DATABASE'],
            $env['FBIRD_USER'],
            $env['FBIRD_PASSWORD'],
            'utf8',
            [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            true
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
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters[0] = 'pdo';
        }
        $parameters = array_merge($parameters, [
            'sqlite',
            $env['SQLITE_DATABASE'],
            'utf8',
            [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            true
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
        $className = $strategy ? Entity::CLASS_CONNECTION->value : Entity::CLASS_PDO_ENGINE->value;
        $parameters = [];
        if ($strategy) {
            $parameters[0] = 'pdo';
        }
        $parameters = array_merge($parameters, [
            'sqlite',
            $env['SQLITE_DATABASE_MEMORY'],
            'utf8',
            [
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ],
            true
        ]);
        /** @var callable $constructor */
        $constructor = [$className, 'new'];
        return $constructor(...$parameters);
    }
}
