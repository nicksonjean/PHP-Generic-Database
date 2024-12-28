<?php

namespace GenericDatabase\Tests\Engine;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Engine\PgSQLConnection;
use GenericDatabase\Engine\PgSQL\Connection\PgSQL;
use GenericDatabase\Modules\Chainable;

class PgSQLConnectionTest extends TestCase
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

        $this->connection = Chainable::nativePgSQL($this->pgsqlEnv, false, false);
    }

    public function testConnectionConstants()
    {
        $this->assertSame(1, PgSQL::FETCH_LAZY);
        $this->assertSame(2, PgSQL::FETCH_ASSOC);
        $this->assertSame(3, PgSQL::FETCH_NUM);
        $this->assertSame(4, PgSQL::FETCH_BOTH);
        $this->assertSame(5, PgSQL::FETCH_OBJ);
        $this->assertSame(6, PgSQL::FETCH_BOUND);
        $this->assertSame(7, PgSQL::FETCH_COLUMN);
        $this->assertSame(8, PgSQL::FETCH_CLASS);
        $this->assertSame(9, PgSQL::FETCH_INTO);
    }

    public function testConnection()
    {
        $this->assertInstanceOf(PgSQLConnection::class, $this->connection);
    }

    public function testConnectionSingleton()
    {
        $connection1 = PgSQLConnection::getInstance();
        $connection2 = PgSQLConnection::getInstance();

        $this->assertInstanceOf(PgSQLConnection::class, $connection1);
        $this->assertInstanceOf(PgSQLConnection::class, $connection2);
        $this->assertSame($connection1, $connection2);
    }

    public function testConnect()
    {
        $this->connection->connect();
        $this->assertInstanceOf(PgSQLConnection::class, $this->connection);
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
        $this->assertInstanceOf(PgSQLConnection::class, $this->connection);
    }

    public function testQuery()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado WHERE id = 5');
        $this->assertInstanceOf(PgSQLConnection::class, $this->connection);
    }

    public function testFetch()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(PgSQLConnection::class, $this->connection);
        $data = $this->connection->fetch();
        $this->assertIsObject($data);
    }

    public function testFetchAll()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(PgSQLConnection::class, $this->connection);
        $data = $this->connection->fetchAll();
        $this->assertIsArray($data);
    }

    public function testExec()
    {
        $this->connection->query("INSERT INTO estado (nome, sigla) VALUES ('TESTE', 'TE')");
        $this->connection->query("DELETE FROM estado WHERE nome = 'TESTE' AND sigla = 'TE'");
        $this->assertInstanceOf(PgSQLConnection::class, $this->connection);
    }

    public function testDisconnect()
    {
        $this->connection->disconnect();
        $this->assertInstanceOf(PgSQLConnection::class, $this->connection);
    }
}
