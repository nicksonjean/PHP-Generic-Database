<?php

declare(strict_types=1);

namespace GenericDatabase\Modules;

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

class Chainable
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('mysqli');
        } else {
            $instance = new MySQLiEngine();
        }
        $instance->setHost($env['MYSQL_HOST'])
            ->setPort((int)$env['MYSQL_PORT'])
            ->setDatabase($env['MYSQL_DATABASE'])
            ->setUser($env['MYSQL_USER'])
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
                MySQL::ATTR_DEFAULT_FETCH_MODE => MySQL::FETCH_OBJ
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pgsql');
        } else {
            $instance = new PgSQLEngine();
        }
        $instance->setHost($env['PGSQL_HOST'])
            ->setPort((int)$env['PGSQL_PORT'])
            ->setDatabase($env['PGSQL_DATABASE'])
            ->setUser($env['PGSQL_USER'])
            ->setPassword($env['PGSQL_PASSWORD'])
            ->setCharset($env['PGSQL_CHARSET'])
            ->setOptions([
                PgSQL::ATTR_PERSISTENT => $persistent,
                PgSQL::ATTR_CONNECT_ASYNC => true,
                PgSQL::ATTR_CONNECT_FORCE_NEW => true,
                PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
                PgSQL::ATTR_DEFAULT_FETCH_MODE => PgSQL::FETCH_OBJ
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('sqlsrv');
        } else {
            $instance = new SQLSrvEngine();
        }
        $instance->setHost($env['SQLSRV_HOST'])
            ->setPort((int)$env['SQLSRV_PORT'])
            ->setDatabase($env['SQLSRV_DATABASE'])
            ->setUser($env['SQLSRV_USER'])
            ->setPassword($env['SQLSRV_PASSWORD'])
            ->setCharset($env['SQLSRV_CHARSET'])
            ->setOptions([
                SQLSrv::ATTR_PERSISTENT => $persistent,
                SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
                SQLSrv::ATTR_DEFAULT_FETCH_MODE => SQLSrv::FETCH_OBJ
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('oci');
        } else {
            $instance = new OCIEngine();
        }
        $instance->setHost($env['OCI_HOST'])
            ->setPort((int)$env['OCI_PORT'])
            ->setDatabase($env['OCI_DATABASE'])
            ->setUser($env['OCI_USER'])
            ->setPassword($env['OCI_PASSWORD'])
            ->setCharset($env['OCI_CHARSET'])
            ->setOptions([
                OCI::ATTR_PERSISTENT => $persistent,
                OCI::ATTR_CONNECT_TIMEOUT => 28800,
                OCI::ATTR_DEFAULT_FETCH_MODE => OCI::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('firebird');
        } else {
            $instance = new FirebirdEngine();
        }
        $instance->setHost($env['FIREBIRD_HOST'])
            ->setPort((int)$env['FIREBIRD_PORT'])
            ->setDatabase($env['FIREBIRD_DATABASE'])
            ->setUser($env['FIREBIRD_USER'])
            ->setPassword($env['FIREBIRD_PASSWORD'])
            ->setCharset($env['FIREBIRD_CHARSET'])
            ->setOptions([
                Firebird::ATTR_PERSISTENT => $persistent,
                Firebird::ATTR_CONNECT_TIMEOUT => 28800,
                Firebird::ATTR_DEFAULT_FETCH_MODE => Firebird::FETCH_OBJ
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('sqlite');
        } else {
            $instance = new SQLiteEngine();
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
                SQLite::ATTR_DEFAULT_FETCH_MODE => SQLite::FETCH_OBJ
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('sqlite');
        } else {
            $instance = new SQLiteEngine();
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
                SQLite::ATTR_DEFAULT_FETCH_MODE => SQLite::FETCH_OBJ
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pdo');
        } else {
            $instance = new PDOEngine();
        }
        $instance->setDriver('mysql')
            ->setHost($env['MYSQL_HOST'])
            ->setPort((int)$env['MYSQL_PORT'])
            ->setDatabase($env['MYSQL_DATABASE'])
            ->setUser($env['MYSQL_USER'])
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pdo');
        } else {
            $instance = new PDOEngine();
        }
        $instance->setDriver('pgsql')
            ->setHost($env['PGSQL_HOST'])
            ->setPort((int)$env['PGSQL_PORT'])
            ->setDatabase($env['PGSQL_DATABASE'])
            ->setUser($env['PGSQL_USER'])
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
     * @param array $env
     * @param bool $strategy
     * @return Connection|PDOEngine
     */
    public static function pdoSQLSrv(
        array $env,
        bool $strategy = false
    ): Connection|PDOEngine {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pdo');
        } else {
            $instance = new PDOEngine();
        }
        $instance->setDriver('sqlsrv')
            ->setHost($env['SQLSRV_HOST'])
            ->setPort((int)$env['SQLSRV_PORT'])
            ->setDatabase($env['SQLSRV_DATABASE'])
            ->setUser($env['SQLSRV_USER'])
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pdo');
        } else {
            $instance = new PDOEngine();
        }
        $instance->setDriver('oci')
            ->setHost($env['OCI_HOST'])
            ->setPort((int)$env['OCI_PORT'])
            ->setDatabase($env['OCI_DATABASE'])
            ->setUser($env['OCI_USER'])
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pdo');
        } else {
            $instance = new PDOEngine();
        }
        $instance->setDriver('firebird')
            ->setHost($env['FIREBIRD_HOST'])
            ->setPort((int)$env['FIREBIRD_PORT'])
            ->setDatabase($env['FIREBIRD_DATABASE'])
            ->setUser($env['FIREBIRD_USER'])
            ->setPassword($env['FIREBIRD_PASSWORD'])
            ->setCharset($env['FIREBIRD_CHARSET'])
            ->setOptions([
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ])
            ->setException(true);

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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pdo');
        } else {
            $instance = new PDOEngine();
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('pdo');
        } else {
            $instance = new PDOEngine();
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCEngine();
        }
        $instance->setDriver('mysql')
            ->setHost($env['MYSQL_HOST'])
            ->setPort((int)$env['MYSQL_PORT'])
            ->setDatabase($env['MYSQL_DATABASE'])
            ->setUser($env['MYSQL_USER'])
            ->setPassword($env['MYSQL_PASSWORD'])
            ->setCharset($env['MYSQL_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ->setException(true);
        return $instance;
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCEngine();
        }
        $instance->setDriver('pgsql')
            ->setHost($env['PGSQL_HOST'])
            ->setPort((int)$env['PGSQL_PORT'])
            ->setDatabase($env['PGSQL_DATABASE'])
            ->setUser($env['PGSQL_USER'])
            ->setPassword($env['PGSQL_PASSWORD'])
            ->setCharset($env['PGSQL_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
    }

    /**
     * @param array $env
     * @param bool $strategy
     * @return Connection|ODBCEngine
     */
    public static function odbcSQLSrv(
        array $env,
        bool $persistent = false,
        bool $strategy = false
    ): Connection|ODBCEngine {
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCEngine();
        }
        $instance->setDriver('sqlsrv')
            ->setHost($env['SQLSRV_HOST'])
            ->setPort((int)$env['SQLSRV_PORT'])
            ->setDatabase($env['SQLSRV_DATABASE'])
            ->setUser($env['SQLSRV_USER'])
            ->setPassword($env['SQLSRV_PASSWORD'])
            ->setCharset($env['SQLSRV_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCEngine();
        }
        $instance->setDriver('oci')
            ->setHost($env['OCI_HOST'])
            ->setPort((int)$env['OCI_PORT'])
            ->setDatabase($env['OCI_DATABASE'])
            ->setUser($env['OCI_USER'])
            ->setPassword($env['OCI_PASSWORD'])
            ->setCharset($env['OCI_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCEngine();
        }
        $instance->setDriver('firebird')
            ->setHost($env['FIREBIRD_HOST'])
            ->setPort((int)$env['FIREBIRD_PORT'])
            ->setDatabase($env['FIREBIRD_DATABASE'])
            ->setUser($env['FIREBIRD_USER'])
            ->setPassword($env['FIREBIRD_PASSWORD'])
            ->setCharset($env['FIREBIRD_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCEngine();
        }
        $instance->setDriver('sqlite')
            ->setDatabase($env['SQLITE_DATABASE'])
            ->setCharset($env['SQLITE_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCEngine();
        }
        $instance->setDriver('access')
            ->setDatabase($env['ACCESS_DATABASE'])
            ->setUser($env['ACCESS_USER'])
            ->setPassword($env['ACCESS_PASSWORD'])
            ->setCharset($env['ACCESS_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCEngine();
        }
        $instance->setDriver('excel')
            ->setDatabase($env['EXCEL_DATABASE'])
            ->setCharset($env['EXCEL_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCEngine();
        }
        $instance->setDriver('text')
            ->setDatabase($env['TEXT_DATABASE'])
            ->setCharset($env['TEXT_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_CONNECT_TIMEOUT => 28800,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
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
        if ($strategy) {
            $instance = new Connection();
            $instance->setEngine('odbc');
        } else {
            $instance = new ODBCEngine();
        }
        $instance->setDriver('sqlite')
            ->setDatabase($env['SQLITE_DATABASE_MEMORY'])
            ->setCharset($env['SQLITE_CHARSET'])
            ->setOptions([
                ODBC::ATTR_PERSISTENT => $persistent,
                ODBC::ATTR_DEFAULT_FETCH_MODE => ODBC::FETCH_OBJ
            ])
            ->setException(true);

        return $instance;
    }
}
