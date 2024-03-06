<?php

namespace GenericDatabase\Tests;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;

class ConnectionMysqliTest extends TestCase
{
    private array $mysqlEnv;

    private $connection;

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

        $this->connection = Chainable::nativeMySQLi($this->mysqlEnv, false, true);
    }

    public function testConnectionConstants()
    {
        $this->assertSame(8, Connection::FETCH_NUM);
        $this->assertSame(9, Connection::FETCH_OBJ);
        $this->assertSame(10, Connection::FETCH_BOTH);
        $this->assertSame(11, Connection::FETCH_INTO);
        $this->assertSame(12, Connection::FETCH_CLASS);
        $this->assertSame(13, Connection::FETCH_ASSOC);
        $this->assertSame(14, Connection::FETCH_COLUMN);
    }

    public function testConnectionSingleton()
    {
        $connection1 = Connection::getInstance();
        $connection2 = Connection::getInstance();

        $this->assertInstanceOf(Connection::class, $connection1);
        $this->assertInstanceOf(Connection::class, $connection2);
        $this->assertSame($connection1, $connection2);
    }

    public function testConnect()
    {
        $this->connection->connect();
        $this->assertInstanceOf(Connection::class, $this->connection);
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

    public function testGetEngine()
    {
        $engine = $this->connection->getEngine();
        $this->assertEquals("mysqli", $engine);
    }

    public function testPrepare()
    {
        $this->connection->prepare(
            'SELECT id AS Codigo FROM estado WHERE id = :id',
            [':id' => 10]
        );
        $this->assertInstanceOf(Connection::class, $this->connection);
    }

    public function testQuery()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado WHERE id = 5');
        $this->assertInstanceOf(Connection::class, $this->connection);
    }

    public function testFetch()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(Connection::class, $this->connection);
        $data = $this->connection->fetch();
        $this->assertIsArray($data);
    }

    public function testFetchAll()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(Connection::class, $this->connection);
        $data = $this->connection->fetchAll();
        $this->assertIsArray($data);
    }

    public function testExec()
    {
        $stmt1 = $this->connection->query("INSERT INTO estado (nome, sigla) VALUES ('TESTE', 'TE')");
        $this->connection->exec($stmt1);
        $stmt2 = $this->connection->query("DELETE FROM estado WHERE nome = 'TESTE' AND sigla = 'TE'");
        $this->connection->exec($stmt2);
        $this->assertInstanceOf(Connection::class, $this->connection);
    }

    public function testDisconnect()
    {
        $this->connection->disconnect();
        $this->assertInstanceOf(Connection::class, $this->connection);
    }
}
