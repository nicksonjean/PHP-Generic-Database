<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\Compare;
use MySQLi;
use PDO;
use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;
use SQLite3;
use stdClass;

final class CompareTest extends TestCase
{
    private static array $env = [];

    public static function setUpBeforeClass(): void
    {
        define("PATH_ROOT", dirname(__DIR__, 2));
        require_once PATH_ROOT . '/vendor/autoload.php';
        self::$env = Dotenv::createImmutable(PATH_ROOT)->load();
    }

    public static function tearDownAfterClass(): void
    {
        self::$env = [];
    }

    public function testNativeConnection()
    {
        $connection = new MySQLi(
            self::$env['MYSQL_HOST'],
            self::$env['MYSQL_USER'],
            self::$env['MYSQL_PASSWORD'],
            self::$env['MYSQL_DATABASE']
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('mysqli', $connectionType);
    }

    public function testNativePgsqlConnection()
    {
        $connection = pg_connect(
            sprintf(
                "host=%s dbname=%s user=%s password=%s",
                self::$env['PGSQL_HOST'],
                self::$env['PGSQL_DATABASE'],
                self::$env['PGSQL_USER'],
                self::$env['PGSQL_PASSWORD']
            )
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('pgsql', $connectionType);
    }

    public function testNativeSqlsrvConnection()
    {
        $serverName = vsprintf('%s', [self::$env['SQLSRV_HOST']]);
        $connectionInfo = [
            "Database" => self::$env['SQLSRV_DATABASE'],
            "UID" => self::$env['SQLSRV_USER'],
            "PWD" => self::$env['SQLSRV_PASSWORD']
        ];
        $connection = sqlsrv_connect($serverName, $connectionInfo);

        $connectionType = Compare::connection($connection);

        $this->assertEquals('sqlsrv', $connectionType);
    }

    public function testNativeOciConnection()
    {
        $connection = oci_connect(
            self::$env['OCI_USER'],
            self::$env['OCI_PASSWORD'],
            sprintf(
                "%s/%s",
                self::$env['OCI_HOST'],
                self::$env['OCI_DATABASE']
            )
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('oci', $connectionType);
    }

    public function testNativeSqlite3MemoryConnection()
    {
        $sqlite3Connection = new SQLite3(":memory:");

        $connectionType = Compare::connection($sqlite3Connection);

        $this->assertEquals('sqlite', $connectionType);
    }

    public function testPdoMysqlConnection()
    {
        $connection = new PDO(
            sprintf(
                "mysql:host=%s;dbname=%s",
                self::$env['MYSQL_HOST'],
                self::$env['MYSQL_DATABASE']
            ),
            self::$env['MYSQL_USER'],
            self::$env['MYSQL_PASSWORD']
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('PDO mysql', $connectionType);
    }

    public function testPdoPgsqlConnection()
    {
        $connection = new PDO(
            sprintf(
                "pgsql:host=%s;dbname=%s",
                self::$env['PGSQL_HOST'],
                self::$env['PGSQL_DATABASE']
            ),
            self::$env['PGSQL_USER'],
            self::$env['PGSQL_PASSWORD']
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('PDO pgsql', $connectionType);
    }

    public function testPdoSqlsrvConnection()
    {
        $connection = new PDO(
            sprintf(
                "sqlsrv:server=%s;database=%s",
                self::$env['SQLSRV_HOST'],
                self::$env['SQLSRV_DATABASE']
            ),
            self::$env['SQLSRV_USER'],
            self::$env['SQLSRV_PASSWORD']
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('PDO sqlsrv', $connectionType);
    }

    public function testPdoOciConnection()
    {
        $connection = new PDO(
            sprintf(
                "oci:host=%s;dbname=%s",
                self::$env['OCI_HOST'],
                self::$env['OCI_DATABASE']
            ),
            self::$env['OCI_USER'],
            self::$env['OCI_PASSWORD']
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('PDO oci', $connectionType);
    }

    public function testPdoSqliteMemoryConnection()
    {
        $connection = new PDO('sqlite::memory:');

        $connectionType = Compare::connection($connection);

        $this->assertEquals('PDO sqlite', $connectionType);
    }

    public function testInvalidConnection()
    {
        $invalidConnection = new stdClass();

        $type = Compare::connection($invalidConnection);

        $this->assertEquals('Unidentified or invalid connection type.', $type);
    }
}
