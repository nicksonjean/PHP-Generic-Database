<?php

namespace GenericDatabase\Tests\Modules;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Modules\StaticArray;
use GenericDatabase\Connection;
use GenericDatabase\Engine\OCIConnection;
use GenericDatabase\Engine\PgSQLConnection;
use GenericDatabase\Engine\MySQLiConnection;
use GenericDatabase\Engine\SQLiteConnection;
use GenericDatabase\Engine\SQLSrvConnection;
use GenericDatabase\Engine\FirebirdConnection;
use GenericDatabase\Engine\PDOConnection;
use GenericDatabase\Engine\ODBCConnection;

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
            'FBIRD_HOST' => "localhost",
            'FBIRD_PORT' => 3050,
            'FBIRD_DATABASE' => "../../resources/database/firebird/DB.FDB",
            'FBIRD_USER' => "sysdba",
            'FBIRD_PASSWORD' => "masterkey",
            'FBIRD_CHARSET' => "utf8",
        ];

        $this->sqliteEnv = [
            'SQLITE_DATABASE' => "../../resources/database/sqlite/DB.SQLITE",
            'SQLITE_DATABASE_MEMORY' => "memory",
            'SQLITE_CHARSET' => "utf8",
        ];
    }
    public function testNativeMysqliAndStrategyMysqli()
    {
        $native = StaticArray::nativeMySQLi($this->mysqlEnv);
        $this->assertInstanceOf(MySQLiConnection::class, $native);

        $strategy = StaticArray::nativeMySQLi($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativePgsqlAndStrategyPgsql()
    {
        $native = StaticArray::nativePgSQL($this->pgsqlEnv);
        $this->assertInstanceOf(PgSQLConnection::class, $native);

        $strategy = StaticArray::nativePgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeSqlsrvAndStrategySqlsrv()
    {
        $native = StaticArray::nativeSQLSrv($this->sqlsrvEnv);
        $this->assertInstanceOf(SQLSrvConnection::class, $native);

        $strategy = StaticArray::nativeSQLSrv($this->sqlsrvEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeOciAndStrategyOci()
    {
        $native = StaticArray::nativeOci($this->ociEnv);
        $this->assertInstanceOf(OCIConnection::class, $native);

        $strategy = StaticArray::nativeOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeFirebirdAndStrategyFirebird()
    {
        $native = StaticArray::nativeFirebird($this->firebirdEnv);
        $this->assertInstanceOf(FirebirdConnection::class, $native);

        $strategy = StaticArray::nativeFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeSqliteAndStrategySqlite()
    {
        $native = StaticArray::nativeSQLite($this->sqliteEnv);
        $this->assertInstanceOf(SQLiteConnection::class, $native);

        $strategy = StaticArray::nativeSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeMemoryAndStrategyMemory()
    {
        $native = StaticArray::nativeMemory($this->sqliteEnv);
        $this->assertInstanceOf(SQLiteConnection::class, $native);

        $strategy = StaticArray::nativeMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoMysqliAndStrategyMysqli()
    {
        $pdo = StaticArray::pdoMySQL($this->mysqlEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = StaticArray::pdoMySQL($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoPgsqlAndStrategyPgsql()
    {
        $pdo = StaticArray::pdoPgSQL($this->pgsqlEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = StaticArray::pdoPgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoSqlsrvAndStrategySqlsrv()
    {
        $pdo = StaticArray::pdoSQLSrv($this->sqlsrvEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = StaticArray::pdoSQLSrv($this->sqlsrvEnv, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoOciAndStrategyOci()
    {
        $pdo = StaticArray::pdoOci($this->ociEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = StaticArray::pdoOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoFirebirdAndStrategyFirebird()
    {
        $pdo = StaticArray::pdoFirebird($this->firebirdEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = StaticArray::pdoFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoSqliteAndStrategySqlite()
    {
        $pdo = StaticArray::pdoSQLite($this->sqliteEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = StaticArray::pdoSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoMemoryAndStrategyMemory()
    {
        $pdo = StaticArray::pdoMemory($this->sqliteEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = StaticArray::pdoMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcMysqliAndStrategyMysqli()
    {
        $pdo = StaticArray::odbcMySQL($this->mysqlEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = StaticArray::odbcMySQL($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcPgsqlAndStrategyPgsql()
    {
        $pdo = StaticArray::odbcPgSQL($this->pgsqlEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = StaticArray::odbcPgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcSqlsrvAndStrategySqlsrv()
    {
        $pdo = StaticArray::odbcSQLSrv($this->sqlsrvEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = StaticArray::odbcSQLSrv($this->sqlsrvEnv, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcOciAndStrategyOci()
    {
        $pdo = StaticArray::odbcOci($this->ociEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = StaticArray::odbcOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcFirebirdAndStrategyFirebird()
    {
        $pdo = StaticArray::odbcFirebird($this->firebirdEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = StaticArray::odbcFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcSqliteAndStrategySqlite()
    {
        $pdo = StaticArray::odbcSQLite($this->sqliteEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = StaticArray::odbcSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcMemoryAndStrategyMemory()
    {
        $pdo = StaticArray::odbcMemory($this->sqliteEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = StaticArray::odbcMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }
}
