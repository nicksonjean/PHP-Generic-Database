<?php

namespace GenericDatabase\Tests;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;

class ConnectionPgsqlTest extends TestCase
{
    private array $pgsqlEnv;

    private $connection;

    protected function setUp(): void
    {
        $this->pgsqlEnv = [
            'PGSQL_HOST' => "localhost",
            'PGSQL_PORT' => 5432,
            'PGSQL_DATABASE' => "postgres",
            'PGSQL_USER' => "postgres",
            'PGSQL_PASSWORD' => "masterkey",
            'PGSQL_CHARSET' => "utf8",
        ];

        $this->connection = Chainable::nativePgSQL($this->pgsqlEnv, false, true);
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

        $ini = Connection::new('./resources/dsn/ini/stg_pgsql.ini');
        $this->assertInstanceOf(Connection::class, $ini);

        $json = Connection::new('./resources/dsn/json/stg_pgsql.json');
        $this->assertInstanceOf(Connection::class, $json);

        $xml = Connection::new('./resources/dsn/xml/stg_pgsql.xml');
        $this->assertInstanceOf(Connection::class, $xml);

        $yaml = Connection::new('./resources/dsn/yaml/stg_pgsql.yaml');
        $this->assertInstanceOf(Connection::class, $yaml);
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
        $this->assertEquals("O''Hare''s Pub", $quotedString);
    }

    public function testGetEngine()
    {
        $engine = $this->connection->getEngine();
        $this->assertEquals("pgsql", $engine);
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
        $this->connection->query("INSERT INTO estado (nome, sigla) VALUES ('TESTE', 'TE')");
        $this->connection->query("DELETE FROM estado WHERE nome = 'TESTE' AND sigla = 'TE'");
        $this->assertInstanceOf(Connection::class, $this->connection);
    }

    public function testDisconnect()
    {
        $this->connection->disconnect();
        $this->assertInstanceOf(Connection::class, $this->connection);
    }
}
