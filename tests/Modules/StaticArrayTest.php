<?php

namespace GenericDatabase\Tests\Modules;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Modules\StaticArray;
use GenericDatabase\Connection;
use GenericDatabase\Engine\OCIEngine;
use GenericDatabase\Engine\PgSQLEngine;
use GenericDatabase\Engine\MySQLiEngine;
use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Engine\SQLSrvEngine;
use GenericDatabase\Engine\FirebirdEngine;
use GenericDatabase\Engine\PDOEngine;

class StaticArrayTest extends TestCase
{
    private array $mysqlEnv;
    private array $pgsqlEnv;
    private array $sqlsrvEnv;
    private array $ociEnv;
    private array $firebirdEnv;
    private array $sqliteEnv;

    protected function setUp(): void
    {
        $this->mysqlEnv = [
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'demodev',
            'MYSQL_USER' => 'root',
            'MYSQL_PASSWORD' => 'masterkey',
            'MYSQL_CHARSET' => 'utf8',
        ];

        $this->pgsqlEnv = [
            'PGSQL_HOST' => "localhost",
            'PGSQL_PORT' => 5432,
            'PGSQL_DATABASE' => "postgres",
            'PGSQL_USER' => "postgres",
            'PGSQL_PASSWORD' => "masterkey",
            'PGSQL_CHARSET' => "utf8",
        ];

        $this->sqlsrvEnv = [
            'SQLSRV_HOST' => "localhost",
            'SQLSRV_PORT' => 1433,
            'SQLSRV_DATABASE' => "demodev",
            'SQLSRV_USER' => "sa",
            'SQLSRV_PASSWORD' => "masterkey",
            'SQLSRV_CHARSET' => "utf8",
        ];

        $this->ociEnv = [
            'OCI_HOST' => "localhost",
            'OCI_PORT' => 1521,
            'OCI_DATABASE' => "xe",
            'OCI_USER' => "hr",
            'OCI_PASSWORD' => "masterkey",
            'OCI_CHARSET' => "utf8",
        ];

        $this->firebirdEnv = [
            'FIREBIRD_HOST' => "localhost",
            'FIREBIRD_PORT' => 3050,
            'FIREBIRD_DATABASE' => "../../resources/database/firebird/DB.FDB",
            'FIREBIRD_USER' => "sysdba",
            'FIREBIRD_PASSWORD' => "masterkey",
            'FIREBIRD_CHARSET' => "utf8",
        ];

        $this->sqliteEnv = [
            'SQLITE_DATABASE' => "../../resources/database/sqlite/DB.SQLITE",
            'SQLITE_DATABASE_MEMORY' => "memory",
            'SQLITE_CHARSET' => "utf8",
        ];
    }
    public function testNativeMysqliAndStrategyMysqli()
    {
        $native = StaticArray::nativeMySQLi($this->mysqlEnv, false, false);
        $this->assertInstanceOf(MySQLiEngine::class, $native);

        $strategy = StaticArray::nativeMySQLi($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativePgsqlAndStrategyPgsql()
    {
        $native = StaticArray::nativePgSQL($this->pgsqlEnv, false, false);
        $this->assertInstanceOf(PgSQLEngine::class, $native);

        $strategy = StaticArray::nativePgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeSqlsrvAndStrategySqlsrv()
    {
        $native = StaticArray::nativeSQLSrv($this->sqlsrvEnv, false, false);
        $this->assertInstanceOf(SQLSrvEngine::class, $native);

        $strategy = StaticArray::nativeSQLSrv($this->sqlsrvEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeOciAndStrategyOci()
    {
        $native = StaticArray::nativeOci($this->ociEnv, false, false);
        $this->assertInstanceOf(OCIEngine::class, $native);

        $strategy = StaticArray::nativeOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeFirebirdAndStrategyFirebird()
    {
        $native = StaticArray::nativeFirebird($this->firebirdEnv, false, false);
        $this->assertInstanceOf(FirebirdEngine::class, $native);

        $strategy = StaticArray::nativeFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeSqliteAndStrategySqlite()
    {
        $native = StaticArray::nativeSQLite($this->sqliteEnv, false, false);
        $this->assertInstanceOf(SQLiteEngine::class, $native);

        $strategy = StaticArray::nativeSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeMemoryAndStrategyMemory()
    {
        $native = StaticArray::nativeMemory($this->sqliteEnv, false, false);
        $this->assertInstanceOf(SqliteEngine::class, $native);

        $strategy = StaticArray::nativeMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoMysqliAndStrategyMysqli()
    {
        $pdo = StaticArray::pdoMySQL($this->mysqlEnv, false, false);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = StaticArray::pdoMySQL($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoPgsqlAndStrategyPgsql()
    {
        $pdo = StaticArray::pdoPgSQL($this->pgsqlEnv, false, false);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = StaticArray::pdoPgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoSqlsrvAndStrategySqlsrv()
    {
        $pdo = StaticArray::pdoSQLSrv($this->sqlsrvEnv, false);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = StaticArray::pdoSQLSrv($this->sqlsrvEnv, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoOciAndStrategyOci()
    {
        $pdo = StaticArray::pdoOci($this->ociEnv, false, false);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = StaticArray::pdoOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoFirebirdAndStrategyFirebird()
    {
        $pdo = StaticArray::pdoFirebird($this->firebirdEnv, false, false);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = StaticArray::pdoFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoSqliteAndStrategySqlite()
    {
        $pdo = StaticArray::pdoSQLite($this->sqliteEnv, false, false);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = StaticArray::pdoSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoMemoryAndStrategyMemory()
    {
        $pdo = StaticArray::pdoMemory($this->sqliteEnv, false, false);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = StaticArray::pdoMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }
}
