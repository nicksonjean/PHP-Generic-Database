<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\Compare;
use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;
use MySQLi;
use PDO;
use SQLite3;
use stdClass;

final class CompareTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $path = dirname(__DIR__, 2);
        require_once $path . '/vendor/autoload.php';
        Dotenv::createImmutable($path)->load();
    }

    public function testNativeMysqliConnection()
    {
        $connection = new MySQLi(
            $_ENV['MYSQL_HOST'],
            $_ENV['MYSQL_USERNAME'],
            $_ENV['MYSQL_PASSWORD'],
            $_ENV['MYSQL_DATABASE']
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('mysqli', $connectionType);
    }

    public function testNativePgsqlConnection()
    {
        $connectionString = vsprintf(
            "host=%s port=%s dbname=%s user=%s password=%s",
            [
                $_ENV['PGSQL_HOST'],
                $_ENV['PGSQL_PORT'],
                $_ENV['PGSQL_DATABASE'],
                $_ENV['PGSQL_USERNAME'],
                $_ENV['PGSQL_PASSWORD']
            ]
        );
        $connection = pg_connect($connectionString);
        $connectionType = Compare::connection($connection);

        $this->assertEquals('pgsql', $connectionType);
    }

    public function testNativeSqlsrvConnection()
    {
        $serverName = vsprintf('%s', [$_ENV['SQLSRV_HOST']]);
        $connectionInfo = [
            "Database" => $_ENV['SQLSRV_DATABASE'],
            "UID" => $_ENV['SQLSRV_USERNAME'],
            "PWD" => $_ENV['SQLSRV_PASSWORD']
        ];
        $connection = sqlsrv_connect($serverName, $connectionInfo);

        $connectionType = Compare::connection($connection);

        $this->assertEquals('sqlsrv', $connectionType);
    }

    public function testNativeOciConnection()
    {
        $connection = oci_connect(
            $_ENV['OCI_USERNAME'],
            $_ENV['OCI_PASSWORD'],
            sprintf(
                "%s/%s",
                $_ENV['OCI_HOST'],
                $_ENV['OCI_DATABASE']
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
                $_ENV['MYSQL_HOST'],
                $_ENV['MYSQL_DATABASE']
            ),
            $_ENV['MYSQL_USERNAME'],
            $_ENV['MYSQL_PASSWORD']
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('PDO mysql', $connectionType);
    }

    public function testPdoPgsqlConnection()
    {
        $connection = new PDO(
            sprintf(
                "pgsql:host=%s;dbname=%s",
                $_ENV['PGSQL_HOST'],
                $_ENV['PGSQL_DATABASE']
            ),
            $_ENV['PGSQL_USERNAME'],
            $_ENV['PGSQL_PASSWORD']
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('PDO pgsql', $connectionType);
    }

    public function testPdoSqlsrvConnection()
    {
        $connection = new PDO(
            sprintf(
                "sqlsrv:server=%s;database=%s",
                $_ENV['SQLSRV_HOST'],
                $_ENV['SQLSRV_DATABASE']
            ),
            $_ENV['SQLSRV_USERNAME'],
            $_ENV['SQLSRV_PASSWORD']
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('PDO sqlsrv', $connectionType);
    }

    public function testPdoOciConnection()
    {
        $connection = new PDO(
            vsprintf(
                "oci:dbname=%s:%s/%s",
                [
                    $_ENV['OCI_HOST'],
                    $_ENV['OCI_PORT'],
                    $_ENV['OCI_DATABASE']
                ]
            ),
            $_ENV['OCI_USERNAME'],
            $_ENV['OCI_PASSWORD']
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
