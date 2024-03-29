<?php

namespace GenericDatabase\Tests\Engine;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Engine\MySQLiEngine;
use GenericDatabase\Engine\MySQLi\MySQL;
use GenericDatabase\Modules\Chainable;

class MysqliEngineTest extends TestCase
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
        $this->assertInstanceOf(MySQLiEngine::class, $this->connection);
    }

    public function testConnectionSingleton()
    {
        $connection1 = MySQLiEngine::getInstance();
        $connection2 = MySQLiEngine::getInstance();

        $this->assertInstanceOf(MySQLiEngine::class, $connection1);
        $this->assertInstanceOf(MySQLiEngine::class, $connection2);
        $this->assertSame($connection1, $connection2);
    }

    public function testConnect()
    {
        $this->connection->connect();
        $this->assertInstanceOf(MySQLiEngine::class, $this->connection);
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
        $this->assertInstanceOf(MySQLiEngine::class, $this->connection);
    }

    public function testQuery()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado WHERE id = 5');
        $this->assertInstanceOf(MySQLiEngine::class, $this->connection);
    }

    public function testFetch()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(MySQLiEngine::class, $this->connection);
        $data = $this->connection->fetch();
        $this->assertIsObject($data);
    }

    public function testFetchAll()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(MySQLiEngine::class, $this->connection);
        $data = $this->connection->fetchAll();
        $this->assertIsArray($data);
    }

    public function testExec()
    {
        $stmt1 = $this->connection->query("INSERT INTO estado (nome, sigla) VALUES ('TESTE', 'TE')");
        $this->connection->exec($stmt1);
        $stmt2 = $this->connection->query("DELETE FROM estado WHERE nome = 'TESTE' AND sigla = 'TE'");
        $this->connection->exec($stmt2);
        $this->assertInstanceOf(MySQLiEngine::class, $this->connection);
    }

    public function testDisconnect()
    {
        $this->connection->disconnect();
        $this->assertInstanceOf(MySQLiEngine::class, $this->connection);
    }
}
