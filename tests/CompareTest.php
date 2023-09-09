<?php

use GenericDatabase\Helpers\Compare;
use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 1));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

final class CompareTest extends TestCase
{
    public function testNative_MySQLi_Connection()
    {
        $connection = new MySQLi(
            $_ENV['MYSQL_HOST'],
            $_ENV['MYSQL_USER'],
            $_ENV['MYSQL_PASSWORD'],
            $_ENV['MYSQL_DATABASE']
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('mysqli', $connectionType);
    }

    public function testNative_PgSQL_Connection()
    {
        $connection = pg_connect(
            sprintf(
                "host=%s dbname=%s user=%s password=%s",
                $_ENV['PGSQL_HOST'],
                $_ENV['PGSQL_DATABASE'],
                $_ENV['PGSQL_USER'],
                $_ENV['PGSQL_PASSWORD']
            )
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('pgsql', $connectionType);
    }

    public function testNative_SQLSrv_Connection()
    {
        $serverName = vsprintf('%s', [$_ENV['SQLSRV_HOST']]);
        $connectionInfo = [
            "Database" => $_ENV['SQLSRV_DATABASE'],
            "UID" => $_ENV['SQLSRV_USER'],
            "PWD" => $_ENV['SQLSRV_PASSWORD']
        ];
        $connection = sqlsrv_connect($serverName, $connectionInfo);

        $connectionType = Compare::connection($connection);

        $this->assertEquals('sqlsrv', $connectionType);
    }

    public function testNative_OCI_Connection()
    {
        $connection = oci_connect(
            $_ENV['OCI_USER'],
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

    public function testNative_SQLite3_Memory_Connection()
    {
        $sqlite3Connection = new SQLite3(":memory:");

        $connectionType = Compare::connection($sqlite3Connection);

        $this->assertEquals('sqlite', $connectionType);
    }

    public function testPDO_MySQL_Connection()
    {
        $connection = new PDO(
            sprintf(
                "mysql:host=%s;dbname=%s",
                $_ENV['MYSQL_HOST'],
                $_ENV['MYSQL_DATABASE']
            ),
            $_ENV['MYSQL_USER'],
            $_ENV['MYSQL_PASSWORD']
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('PDO mysql', $connectionType);
    }

    public function testPDO_PgSQL_Connection()
    {
        $connection = new PDO(
            sprintf(
                "pgsql:host=%s;dbname=%s",
                $_ENV['PGSQL_HOST'],
                $_ENV['PGSQL_DATABASE']
            ),
            $_ENV['PGSQL_USER'],
            $_ENV['PGSQL_PASSWORD']
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('PDO pgsql', $connectionType);
    }

    public function testPDO_SQLSrv_Connection()
    {
        $connection = new PDO(
            sprintf(
                "sqlsrv:server=%s;database=%s",
                $_ENV['SQLSRV_HOST'],
                $_ENV['SQLSRV_DATABASE']
            ),
            $_ENV['SQLSRV_USER'],
            $_ENV['SQLSRV_PASSWORD']
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('PDO sqlsrv', $connectionType);
    }

    public function testPDO_OCI_Connection()
    {
        $connection = new PDO(
            sprintf(
                "oci:host=%s;dbname=%s",
                $_ENV['OCI_HOST'],
                $_ENV['OCI_DATABASE']
            ),
            $_ENV['OCI_USER'],
            $_ENV['OCI_PASSWORD']
        );

        $connectionType = Compare::connection($connection);

        $this->assertEquals('PDO oci', $connectionType);
    }

    public function testPDO_SQLite_Memory_Connection()
    {
        $connection = new PDO('sqlite::memory:');

        $connectionType = Compare::connection($connection);

        $this->assertEquals('PDO sqlite', $connectionType);
    }

    public function testInvalid_Connection()
    {
        $invalidConnection = new stdClass;

        $type = Compare::connection($invalidConnection);

        $this->assertEquals('Unidentified or invalid connection type.', $type);
    }
}
