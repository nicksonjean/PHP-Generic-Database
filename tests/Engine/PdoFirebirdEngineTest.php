<?php

namespace GenericDatabase\Tests\Engine;

use PHPUnit\Framework\TestCase;
use PDO;
use GenericDatabase\Engine\PDOConnection;
use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

class PdoFirebirdEngineTest extends TestCase
{
    private array $firebirdEnv;

    private $connection;

    public static function setUpBeforeClass(): void
    {
        $path = dirname(__DIR__, 2);
        require_once $path . '/vendor/autoload.php';
        Dotenv::createImmutable($path)->load();
    }

    protected function setUp(): void
    {
        $this->firebirdEnv = [
            'FBIRD_HOST' => $_ENV['FBIRD_HOST'],
            'FBIRD_PORT' => $_ENV['FBIRD_PORT'],
            'FBIRD_DATABASE' => $_ENV['FBIRD_DATABASE'],
            'FBIRD_USERNAME' => $_ENV['FBIRD_USERNAME'],
            'FBIRD_PASSWORD' => $_ENV['FBIRD_PASSWORD'],
            'FBIRD_CHARSET' => $_ENV['FBIRD_CHARSET']
        ];

        $this->connection = Chainable::pdoFirebird($this->firebirdEnv, false, false);
    }

    public function testConnectionConstants()
    {
        $this->assertSame(1, PDO::FETCH_LAZY);
        $this->assertSame(2, PDO::FETCH_ASSOC);
        $this->assertSame(3, PDO::FETCH_NUM);
        $this->assertSame(4, PDO::FETCH_BOTH);
        $this->assertSame(5, PDO::FETCH_OBJ);
        $this->assertSame(6, PDO::FETCH_BOUND);
        $this->assertSame(7, PDO::FETCH_COLUMN);
        $this->assertSame(8, PDO::FETCH_CLASS);
        $this->assertSame(9, PDO::FETCH_INTO);
    }

    public function testConnection()
    {
        $this->assertInstanceOf(PDOConnection::class, $this->connection);
    }

    public function testConnectionSingleton()
    {
        $connection1 = PDOConnection::getInstance();
        $connection2 = PDOConnection::getInstance();

        $this->assertInstanceOf(PDOConnection::class, $connection1);
        $this->assertInstanceOf(PDOConnection::class, $connection2);
        $this->assertSame($connection1, $connection2);
    }

    public function testConnect()
    {
        $this->connection->connect();
        $this->assertInstanceOf(PDOConnection::class, $this->connection);
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
        $this->assertInstanceOf(PDOConnection::class, $this->connection);
    }

    public function testQuery()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado WHERE id = 5');
        $this->assertInstanceOf(PDOConnection::class, $this->connection);
    }

    public function testFetch()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(PDOConnection::class, $this->connection);
        $data = $this->connection->fetch();
        $this->assertIsArray($data);
    }

    public function testFetchAll()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(PDOConnection::class, $this->connection);
        $data = $this->connection->fetchAll();
        $this->assertIsArray($data);
    }

    public function testExec()
    {
        $this->connection->query("INSERT INTO estado (nome, sigla) VALUES ('TESTE', 'TE')");
        $this->connection->query("DELETE FROM estado WHERE nome = 'TESTE' AND sigla = 'TE'");
        $this->assertInstanceOf(PDOConnection::class, $this->connection);
    }

    public function testDisconnect()
    {
        $this->connection->disconnect();
        $this->assertInstanceOf(PDOConnection::class, $this->connection);
    }
}
