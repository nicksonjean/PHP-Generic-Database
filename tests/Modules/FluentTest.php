<?php

namespace GenericDatabase\Tests\Modules;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Modules\Fluent;
use GenericDatabase\Connection;
use GenericDatabase\Engine\OCIEngine;
use GenericDatabase\Engine\PgSQLEngine;
use GenericDatabase\Engine\MySQLiEngine;
use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Engine\SQLSrvEngine;
use GenericDatabase\Engine\FirebirdEngine;
use GenericDatabase\Engine\PDOEngine;

class FluentTest extends TestCase
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
        $native = Fluent::nativeMySQLi($this->mysqlEnv);
        $this->assertInstanceOf(MySQLiEngine::class, $native);

        $strategy = Fluent::nativeMySQLi($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativePgsqlAndStrategyPgsql()
    {
        $native = Fluent::nativePgSQL($this->pgsqlEnv);
        $this->assertInstanceOf(PgSQLEngine::class, $native);

        $strategy = Fluent::nativePgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeSqlsrvAndStrategySqlsrv()
    {
        $native = Fluent::nativeSQLSrv($this->sqlsrvEnv);
        $this->assertInstanceOf(SQLSrvEngine::class, $native);

        $strategy = Fluent::nativeSQLSrv($this->sqlsrvEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeOciAndStrategyOci()
    {
        $native = Fluent::nativeOci($this->ociEnv);
        $this->assertInstanceOf(OCIEngine::class, $native);

        $strategy = Fluent::nativeOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeFirebirdAndStrategyFirebird()
    {
        $native = Fluent::nativeFirebird($this->firebirdEnv);
        $this->assertInstanceOf(FirebirdEngine::class, $native);

        $strategy = Fluent::nativeFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeSqliteAndStrategySqlite()
    {
        $native = Fluent::nativeSQLite($this->sqliteEnv);
        $this->assertInstanceOf(SQLiteEngine::class, $native);

        $strategy = Fluent::nativeSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeMemoryAndStrategyMemory()
    {
        $native = Fluent::nativeMemory($this->sqliteEnv);
        $this->assertInstanceOf(SqliteEngine::class, $native);

        $strategy = Fluent::nativeMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoMysqliAndStrategyMysqli()
    {
        $pdo = Fluent::pdoMySQL($this->mysqlEnv);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = Fluent::pdoMySQL($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoPgsqlAndStrategyPgsql()
    {
        $pdo = Fluent::pdoPgSQL($this->pgsqlEnv);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = Fluent::pdoPgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoSqlsrvAndStrategySqlsrv()
    {
        $pdo = Fluent::pdoSQLSrv($this->sqlsrvEnv);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = Fluent::pdoSQLSrv($this->sqlsrvEnv, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoOciAndStrategyOci()
    {
        $pdo = Fluent::pdoOci($this->ociEnv);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = Fluent::pdoOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoFirebirdAndStrategyFirebird()
    {
        $pdo = Fluent::pdoFirebird($this->firebirdEnv);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = Fluent::pdoFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoSqliteAndStrategySqlite()
    {
        $pdo = Fluent::pdoSQLite($this->sqliteEnv);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = Fluent::pdoSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoMemoryAndStrategyMemory()
    {
        $pdo = Fluent::pdoMemory($this->sqliteEnv);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = Fluent::pdoMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }
}
