<?php

namespace GenericDatabase\Tests\Modules;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Modules\Chainable;
use GenericDatabase\Connection;
use GenericDatabase\Engine\OCIEngine;
use GenericDatabase\Engine\PgSQLEngine;
use GenericDatabase\Engine\MySQLiEngine;
use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Engine\SQLSrvEngine;
use GenericDatabase\Engine\FirebirdEngine;
use GenericDatabase\Engine\PDOEngine;

class ChainableTest extends TestCase
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
        $native = Chainable::nativeMySQLi($this->mysqlEnv);
        $this->assertInstanceOf(MySQLiEngine::class, $native);

        $strategy = Chainable::nativeMySQLi($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativePgsqlAndStrategyPgsql()
    {
        $native = Chainable::nativePgSQL($this->pgsqlEnv);
        $this->assertInstanceOf(PgSQLEngine::class, $native);

        $strategy = Chainable::nativePgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeSqlsrvAndStrategySqlsrv()
    {
        $native = Chainable::nativeSQLSrv($this->sqlsrvEnv);
        $this->assertInstanceOf(SQLSrvEngine::class, $native);

        $strategy = Chainable::nativeSQLSrv($this->sqlsrvEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeOciAndStrategyOci()
    {
        $native = Chainable::nativeOci($this->ociEnv);
        $this->assertInstanceOf(OCIEngine::class, $native);

        $strategy = Chainable::nativeOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeFirebirdAndStrategyFirebird()
    {
        $native = Chainable::nativeFirebird($this->firebirdEnv);
        $this->assertInstanceOf(FirebirdEngine::class, $native);

        $strategy = Chainable::nativeFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeSqliteAndStrategySqlite()
    {
        $native = Chainable::nativeSQLite($this->sqliteEnv);
        $this->assertInstanceOf(SQLiteEngine::class, $native);

        $strategy = Chainable::nativeSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeMemoryAndStrategyMemory()
    {
        $native = Chainable::nativeMemory($this->sqliteEnv);
        $this->assertInstanceOf(SqliteEngine::class, $native);

        $strategy = Chainable::nativeMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoMysqliAndStrategyMysqli()
    {
        $pdo = Chainable::pdoMySQL($this->mysqlEnv);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = Chainable::pdoMySQL($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoPgsqlAndStrategyPgsql()
    {
        $pdo = Chainable::pdoPgSQL($this->pgsqlEnv);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = Chainable::pdoPgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoSqlsrvAndStrategySqlsrv()
    {
        $pdo = Chainable::pdoSQLSrv($this->sqlsrvEnv);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = Chainable::pdoSQLSrv($this->sqlsrvEnv, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoOciAndStrategyOci()
    {
        $pdo = Chainable::pdoOci($this->ociEnv);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = Chainable::pdoOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoFirebirdAndStrategyFirebird()
    {
        $pdo = Chainable::pdoFirebird($this->firebirdEnv);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = Chainable::pdoFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoSqliteAndStrategySqlite()
    {
        $pdo = Chainable::pdoSQLite($this->sqliteEnv);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = Chainable::pdoSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoMemoryAndStrategyMemory()
    {
        $pdo = Chainable::pdoMemory($this->sqliteEnv);
        $this->assertInstanceOf(PDOEngine::class, $pdo);

        $strategy = Chainable::pdoMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }
}
