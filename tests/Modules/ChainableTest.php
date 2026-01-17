<?php

namespace GenericDatabase\Tests\Modules;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Modules\Chainable;
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

class ChainableTest extends TestCase
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
        $native = Chainable::nativeMySQLi($this->mysqlEnv);
        $this->assertInstanceOf(MySQLiConnection::class, $native);

        $strategy = Chainable::nativeMySQLi($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativePgsqlAndStrategyPgsql()
    {
        $native = Chainable::nativePgSQL($this->pgsqlEnv);
        $this->assertInstanceOf(PgSQLConnection::class, $native);

        $strategy = Chainable::nativePgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeSqlsrvAndStrategySqlsrv()
    {
        $native = Chainable::nativeSQLSrv($this->sqlsrvEnv);
        $this->assertInstanceOf(SQLSrvConnection::class, $native);

        $strategy = Chainable::nativeSQLSrv($this->sqlsrvEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeOciAndStrategyOci()
    {
        $native = Chainable::nativeOci($this->ociEnv);
        $this->assertInstanceOf(OCIConnection::class, $native);

        $strategy = Chainable::nativeOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeFirebirdAndStrategyFirebird()
    {
        $native = Chainable::nativeFirebird($this->firebirdEnv);
        $this->assertInstanceOf(FirebirdConnection::class, $native);

        $strategy = Chainable::nativeFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeSqliteAndStrategySqlite()
    {
        $native = Chainable::nativeSQLite($this->sqliteEnv);
        $this->assertInstanceOf(SQLiteConnection::class, $native);

        $strategy = Chainable::nativeSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testNativeMemoryAndStrategyMemory()
    {
        $native = Chainable::nativeMemory($this->sqliteEnv);
        $this->assertInstanceOf(SQLiteConnection::class, $native);

        $strategy = Chainable::nativeMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoMysqliAndStrategyMysqli()
    {
        $pdo = Chainable::pdoMySQL($this->mysqlEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = Chainable::pdoMySQL($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoPgsqlAndStrategyPgsql()
    {
        $pdo = Chainable::pdoPgSQL($this->pgsqlEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = Chainable::pdoPgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoSqlsrvAndStrategySqlsrv()
    {
        $pdo = Chainable::pdoSQLSrv($this->sqlsrvEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = Chainable::pdoSQLSrv($this->sqlsrvEnv, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoOciAndStrategyOci()
    {
        $pdo = Chainable::pdoOci($this->ociEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = Chainable::pdoOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoFirebirdAndStrategyFirebird()
    {
        $pdo = Chainable::pdoFirebird($this->firebirdEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = Chainable::pdoFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoSqliteAndStrategySqlite()
    {
        $pdo = Chainable::pdoSQLite($this->sqliteEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = Chainable::pdoSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testPdoMemoryAndStrategyMemory()
    {
        $pdo = Chainable::pdoMemory($this->sqliteEnv);
        $this->assertInstanceOf(PDOConnection::class, $pdo);

        $strategy = Chainable::pdoMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcMysqliAndStrategyMysqli()
    {
        $pdo = Chainable::odbcMySQL($this->mysqlEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = Chainable::odbcMySQL($this->mysqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcPgsqlAndStrategyPgsql()
    {
        $pdo = Chainable::odbcPgSQL($this->pgsqlEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = Chainable::odbcPgSQL($this->pgsqlEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcSqlsrvAndStrategySqlsrv()
    {
        $pdo = Chainable::odbcSQLSrv($this->sqlsrvEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = Chainable::odbcSQLSrv($this->sqlsrvEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcOciAndStrategyOci()
    {
        $pdo = Chainable::odbcOci($this->ociEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = Chainable::odbcOci($this->ociEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcFirebirdAndStrategyFirebird()
    {
        $pdo = Chainable::odbcFirebird($this->firebirdEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = Chainable::odbcFirebird($this->firebirdEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcSqliteAndStrategySqlite()
    {
        $pdo = Chainable::odbcSQLite($this->sqliteEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = Chainable::odbcSQLite($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }

    public function testOdbcMemoryAndStrategyMemory()
    {
        $pdo = Chainable::odbcMemory($this->sqliteEnv);
        $this->assertInstanceOf(ODBCConnection::class, $pdo);

        $strategy = Chainable::odbcMemory($this->sqliteEnv, false, true);
        $this->assertInstanceOf(Connection::class, $strategy);
    }
}
