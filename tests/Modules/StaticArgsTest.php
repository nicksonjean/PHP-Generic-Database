<?php

namespace GenericDatabase\Tests\Modules;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Modules\StaticArgs;
use GenericDatabase\Connection;
use GenericDatabase\Engine\OCIConnection;
use GenericDatabase\Engine\PgSQLConnection;
use GenericDatabase\Engine\MySQLiConnection;
use GenericDatabase\Engine\SQLiteConnection;
use GenericDatabase\Engine\SQLSrvConnection;
use GenericDatabase\Engine\FirebirdConnection;
use GenericDatabase\Engine\PDOConnection;
use GenericDatabase\Engine\ODBCConnection;
use Dotenv\Dotenv;

class StaticArgsTest extends TestCase
{
    private array $mysqlEnv;
    private array $pgsqlEnv;
    private array $sqlsrvEnv;
    private array $ociEnv;
    private array $firebirdEnv;
    private array $sqliteEnv;

    public static function setUpBeforeClass(): void
    {
        $path = dirname(__DIR__, 2);
        require_once $path . '/vendor/autoload.php';
        Dotenv::createImmutable($path)->load();
    }

    protected function setUp(): void
    {
        $this->mysqlEnv = [
            'MYSQL_HOST' => $_ENV['MYSQL_HOST'],
            'MYSQL_PORT' => $_ENV['MYSQL_PORT'],
            'MYSQL_DATABASE' => $_ENV['MYSQL_DATABASE'],
            'MYSQL_USERNAME' => $_ENV['MYSQL_USERNAME'],
            'MYSQL_PASSWORD' => $_ENV['MYSQL_PASSWORD'],
            'MYSQL_CHARSET' => $_ENV['MYSQL_CHARSET']
        ];

        $this->pgsqlEnv = [
            'PGSQL_HOST' => $_ENV['PGSQL_HOST'],
            'PGSQL_PORT' => $_ENV['PGSQL_PORT'],
            'PGSQL_DATABASE' => $_ENV['PGSQL_DATABASE'],
            'PGSQL_USERNAME' => $_ENV['PGSQL_USERNAME'],
            'PGSQL_PASSWORD' => $_ENV['PGSQL_PASSWORD'],
            'PGSQL_CHARSET' => $_ENV['PGSQL_CHARSET']
        ];

        $this->sqlsrvEnv = [
            'SQLSRV_HOST' => $_ENV['SQLSRV_HOST'],
            'SQLSRV_PORT' => $_ENV['SQLSRV_PORT'],
            'SQLSRV_DATABASE' => $_ENV['SQLSRV_DATABASE'],
            'SQLSRV_USERNAME' => $_ENV['SQLSRV_USERNAME'],
            'SQLSRV_PASSWORD' => $_ENV['SQLSRV_PASSWORD'],
            'SQLSRV_CHARSET' => $_ENV['SQLSRV_CHARSET']
        ];

        $this->ociEnv = [
            'OCI_HOST' => $_ENV['OCI_HOST'],
            'OCI_PORT' => $_ENV['OCI_PORT'],
            'OCI_DATABASE' => $_ENV['OCI_DATABASE'],
            'OCI_USERNAME' => $_ENV['OCI_USERNAME'],
            'OCI_PASSWORD' => $_ENV['OCI_PASSWORD'],
            'OCI_CHARSET' => $_ENV['OCI_CHARSET']
        ];

        $this->firebirdEnv = [
            'FBIRD_HOST' => $_ENV['FBIRD_HOST'],
            'FBIRD_PORT' => $_ENV['FBIRD_PORT'],
            'FBIRD_DATABASE' => $_ENV['FBIRD_DATABASE'],
            'FBIRD_USERNAME' => $_ENV['FBIRD_USERNAME'],
            'FBIRD_PASSWORD' => $_ENV['FBIRD_PASSWORD'],
            'FBIRD_CHARSET' => $_ENV['FBIRD_CHARSET']
        ];

        $this->sqliteEnv = [
            'SQLITE_DATABASE' => $_ENV['SQLITE_DATABASE'],
            'SQLITE_DATABASE_MEMORY' => $_ENV['SQLITE_DATABASE_MEMORY'],
            'SQLITE_CHARSET' => $_ENV['SQLITE_CHARSET']
        ];
    }
    public function testNativeMysqliAndStrategyMysqli()
    {
        $native = StaticArgs::nativeMySQLi($this->mysqlEnv);
        $this->assertInstanceOf(MySQLiConnection::class, $native);

        $strategy = StaticArgs::nativeMySQLi($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativePgsqlAndStrategyPgsql()
    {
        $native = StaticArgs::nativePgSQL($this->pgsqlEnv);
        $this->assertInstanceOf(PgSQLConnection::class, $native);

        $strategy = StaticArgs::nativePgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeSqlsrvAndStrategySqlsrv()
    {
        $native = StaticArgs::nativeSQLSrv($this->sqlsrvEnv);
        $this->assertInstanceOf(SQLSrvConnection::class, $native);

        $strategy = StaticArgs::nativeSQLSrv($this->sqlsrvEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeOciAndStrategyOci()
    {
        $native = StaticArgs::nativeOci($this->ociEnv);
        $this->assertInstanceOf(OCIConnection::class, $native);

        $strategy = StaticArgs::nativeOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeFirebirdAndStrategyFirebird()
    {
        $native = StaticArgs::nativeFirebird($this->firebirdEnv);
        $this->assertInstanceOf(FirebirdConnection::class, $native);

        $strategy = StaticArgs::nativeFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeSqliteAndStrategySqlite()
    {
        $native = StaticArgs::nativeSQLite($this->sqliteEnv);
        $this->assertInstanceOf(SQLiteConnection::class, $native);

        $strategy = StaticArgs::nativeSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeMemoryAndStrategyMemory()
    {
        $native = StaticArgs::nativeMemory($this->sqliteEnv);
        $this->assertInstanceOf(SQLiteConnection::class, $native);

        $strategy = StaticArgs::nativeMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoMysqliAndStrategyMysqli()
    {
        $pdo = StaticArgs::pdoMySQL($this->mysqlEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = StaticArgs::pdoMySQL($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoPgsqlAndStrategyPgsql()
    {
        $pdo = StaticArgs::pdoPgSQL($this->pgsqlEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = StaticArgs::pdoPgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoSqlsrvAndStrategySqlsrv()
    {
        $pdo = StaticArgs::pdoSQLSrv($this->sqlsrvEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = StaticArgs::pdoSQLSrv($this->sqlsrvEnv, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoOciAndStrategyOci()
    {
        $pdo = StaticArgs::pdoOci($this->ociEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = StaticArgs::pdoOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoFirebirdAndStrategyFirebird()
    {
        $pdo = StaticArgs::pdoFirebird($this->firebirdEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = StaticArgs::pdoFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoSqliteAndStrategySqlite()
    {
        $pdo = StaticArgs::pdoSQLite($this->sqliteEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = StaticArgs::pdoSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoMemoryAndStrategyMemory()
    {
        $pdo = StaticArgs::pdoMemory($this->sqliteEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = StaticArgs::pdoMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcMysqliAndStrategyMysqli()
    {
        $pdo = StaticArgs::odbcMySQL($this->mysqlEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = StaticArgs::odbcMySQL($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcPgsqlAndStrategyPgsql()
    {
        $pdo = StaticArgs::odbcPgSQL($this->pgsqlEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = StaticArgs::odbcPgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcSqlsrvAndStrategySqlsrv()
    {
        $pdo = StaticArgs::odbcSQLSrv($this->sqlsrvEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = StaticArgs::odbcSQLSrv($this->sqlsrvEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcOciAndStrategyOci()
    {
        $pdo = StaticArgs::odbcOci($this->ociEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = StaticArgs::odbcOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcFirebirdAndStrategyFirebird()
    {
        $pdo = StaticArgs::odbcFirebird($this->firebirdEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = StaticArgs::odbcFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcSqliteAndStrategySqlite()
    {
        $pdo = StaticArgs::odbcSQLite($this->sqliteEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = StaticArgs::odbcSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcMemoryAndStrategyMemory()
    {
        $pdo = StaticArgs::odbcMemory($this->sqliteEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = StaticArgs::odbcMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }
}
