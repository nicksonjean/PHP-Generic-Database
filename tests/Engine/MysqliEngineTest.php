<?php

namespace GenericDatabase\Tests\Engine;

use mysqli;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use GenericDatabase\Modules\Chainable;
use GenericDatabase\Engine\MySQLiConnection;
use GenericDatabase\Engine\MySQLi\Connection\MySQL;

class MysqliEngineTest extends TestCase
{
    private array $mysqlEnv;
    private MySQLiConnection $connection;

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

        $this->connection = Chainable::nativeMySQLi($this->mysqlEnv, false, false);
    }


    public function testConnectionConstants()
    {
        $this->assertSame(1, MySQL::FETCH_LAZY);
        $this->assertSame(2, MySQL::FETCH_ASSOC);
        $this->assertSame(3, MySQL::FETCH_NUM);
        $this->assertSame(4, MySQL::FETCH_BOTH);
        $this->assertSame(5, MySQL::FETCH_OBJ);
        $this->assertSame(6, MySQL::FETCH_BOUND);
        $this->assertSame(7, MySQL::FETCH_COLUMN);
        $this->assertSame(8, MySQL::FETCH_CLASS);
        $this->assertSame(9, MySQL::FETCH_INTO);
    }

    public function testConnection()
    {
        $this->assertInstanceOf(MySQLiConnection::class, $this->connection);
    }

    public function testConnectionSingleton()
    {
        $connection1 = MySQLiConnection::getInstance();
        $connection2 = MySQLiConnection::getInstance();

        $this->assertInstanceOf(MySQLiConnection::class, $connection1);
        $this->assertInstanceOf(MySQLiConnection::class, $connection2);
        $this->assertSame($connection1, $connection2);
    }

    public function testConnect()
    {
        $this->connection->connect();
        $this->assertInstanceOf(MySQLiConnection::class, $this->connection);
    }

    public function testPing()
    {
        $connected = $this->connection->ping();
        $this->assertTrue($connected);
    }

    public function testIsConnected()
    {
        $connected = $this->connection->isConnected();
        $this->assertTrue($connected);
    }

    public function testQuoteString()
    {
        $quotedString = $this->connection->quote("O'Hare's Pub");
        $this->assertEquals("O\'Hare\'s Pub", $quotedString);
    }

    public function testGetCharset()
    {
        $charset = $this->connection->getCharset();
        $this->assertEquals("utf8", $charset);
    }

    public function testPrepare()
    {
        $this->connection->prepare(
            'SELECT id AS Codigo FROM estado WHERE id = :id',
            [':id' => 10]
        );
        $this->assertInstanceOf(MySQLiConnection::class, $this->connection);
    }

    public function testQuery()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado WHERE id = 5');
        $this->assertInstanceOf(MySQLiConnection::class, $this->connection);
    }

    public function testFetch()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(MySQLiConnection::class, $this->connection);
        $data = $this->connection->fetch();
        $this->assertIsObject($data);
    }

    public function testFetchAll()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(MySQLiConnection::class, $this->connection);
        $data = $this->connection->fetchAll();
        $this->assertIsArray($data);
    }

    public function testExec()
    {
        $this->connection->query("INSERT INTO estado (nome, sigla) VALUES ('TESTE', 'TE')");
        $this->connection->query("DELETE FROM estado WHERE nome = 'TESTE' AND sigla = 'TE'");
        $this->assertInstanceOf(MySQLiConnection::class, $this->connection);
    }

    public function testDisconnect()
    {
        $this->connection->disconnect();
        $this->assertInstanceOf(MySQLiConnection::class, $this->connection);
    }
}
