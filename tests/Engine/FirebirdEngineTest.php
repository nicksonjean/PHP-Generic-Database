<?php

namespace GenericDatabase\Tests\Engine;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Engine\FirebirdConnection;
use GenericDatabase\Engine\Firebird\Connection\Firebird;
use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

/**
 * @group ignore-test-cases
 */
class FirebirdEngineTest extends TestCase
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

        $this->connection = Chainable::nativeFirebird($this->firebirdEnv, false, false);
    }

    public function testConnectionConstants()
    {
        $this->assertSame(1, Firebird::FETCH_LAZY);
        $this->assertSame(2, Firebird::FETCH_ASSOC);
        $this->assertSame(3, Firebird::FETCH_NUM);
        $this->assertSame(4, Firebird::FETCH_BOTH);
        $this->assertSame(5, Firebird::FETCH_OBJ);
        $this->assertSame(6, Firebird::FETCH_BOUND);
        $this->assertSame(7, Firebird::FETCH_COLUMN);
        $this->assertSame(8, Firebird::FETCH_CLASS);
        $this->assertSame(9, Firebird::FETCH_INTO);
    }

    public function testConnection()
    {
        $this->assertInstanceOf(FirebirdConnection::class, $this->connection);
    }

    public function testConnectionSingleton()
    {
        $connection1 = FirebirdConnection::getInstance();
        $connection2 = FirebirdConnection::getInstance();

        $this->assertInstanceOf(FirebirdConnection::class, $connection1);
        $this->assertInstanceOf(FirebirdConnection::class, $connection2);
        $this->assertSame($connection1, $connection2);
    }

    public function testConnect()
    {
        $this->connection->connect();
        $this->assertInstanceOf(FirebirdConnection::class, $this->connection);
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
        $this->assertInstanceOf(FirebirdConnection::class, $this->connection);
    }

    public function testQuery()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado WHERE id = 5');
        $this->assertInstanceOf(FirebirdConnection::class, $this->connection);
    }

    public function testFetch()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(FirebirdConnection::class, $this->connection);
        $data = $this->connection->fetch();
        $this->assertIsObject($data);
    }

    public function testFetchAll()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(FirebirdConnection::class, $this->connection);
        $data = $this->connection->fetchAll();
        $this->assertIsArray($data);
    }

    public function testExec()
    {
        $this->connection->query("INSERT INTO estado (nome, sigla) VALUES ('TESTE', 'TE')");
        $this->connection->query("DELETE FROM estado WHERE nome = 'TESTE' AND sigla = 'TE'");
        $this->assertInstanceOf(FirebirdConnection::class, $this->connection);
    }

    public function testDisconnect()
    {
        $this->connection->disconnect();
        $this->assertInstanceOf(FirebirdConnection::class, $this->connection);
    }
}
