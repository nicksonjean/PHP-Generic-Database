<?php

namespace GenericDatabase\Tests\Engine;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Engine\OCIEngine;
use GenericDatabase\Modules\Chainable;

class OCIEngineTest extends TestCase
{
    private array $ociEnv;

    private $connection;

    protected function setUp(): void
    {
        $this->ociEnv = [
            'OCI_HOST' => "localhost",
            'OCI_PORT' => 1521,
            'OCI_DATABASE' => "xe",
            'OCI_USER' => "hr",
            'OCI_PASSWORD' => "masterkey",
            'OCI_CHARSET' => "utf8",
        ];

        $this->connection = Chainable::nativeOCI($this->ociEnv, false, false);
    }

    public function testConnectionConstants()
    {
        $this->assertSame(8, OCIEngine::FETCH_NUM);
        $this->assertSame(9, OCIEngine::FETCH_OBJ);
        $this->assertSame(10, OCIEngine::FETCH_BOTH);
        $this->assertSame(11, OCIEngine::FETCH_INTO);
        $this->assertSame(12, OCIEngine::FETCH_CLASS);
        $this->assertSame(13, OCIEngine::FETCH_ASSOC);
        $this->assertSame(14, OCIEngine::FETCH_COLUMN);
    }

    public function testConnection()
    {
        $this->assertInstanceOf(OCIEngine::class, $this->connection);
    }

    public function testConnectionSingleton()
    {
        $connection1 = OCIEngine::getInstance();
        $connection2 = OCIEngine::getInstance();

        $this->assertInstanceOf(OCIEngine::class, $connection1);
        $this->assertInstanceOf(OCIEngine::class, $connection2);
        $this->assertSame($connection1, $connection2);
    }

    public function testConnect()
    {
        $this->connection->connect();
        $this->assertInstanceOf(OCIEngine::class, $this->connection);
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
        $this->assertEquals("'O''Hare''s Pub'", $quotedString);
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
        $this->assertInstanceOf(OCIEngine::class, $this->connection);
    }

    public function testQuery()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado WHERE id = 5');
        $this->assertInstanceOf(OCIEngine::class, $this->connection);
    }

    public function testFetch()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(OCIEngine::class, $this->connection);
        $data = $this->connection->fetch();
        $this->assertIsArray($data);
    }

    public function testFetchAll()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(OCIEngine::class, $this->connection);
        $data = $this->connection->fetchAll();
        $this->assertIsArray($data);
    }

    public function testExec()
    {
        $this->connection->query("INSERT INTO estado (nome, sigla) VALUES ('TESTE', 'TE')");
        $this->connection->query("DELETE FROM estado WHERE nome = 'TESTE' AND sigla = 'TE'");
        $this->assertInstanceOf(OCIEngine::class, $this->connection);
    }

    public function testDisconnect()
    {
        $this->connection->disconnect();
        $this->assertInstanceOf(OCIEngine::class, $this->connection);
    }
}
