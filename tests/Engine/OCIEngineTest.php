<?php

namespace GenericDatabase\Tests\Engine;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Engine\OCIConnection;
use GenericDatabase\Engine\OCI\Connection\OCI;
use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

class OCIEngineTest extends TestCase
{
    private array $ociEnv;

    private $connection;

    public static function setUpBeforeClass(): void
    {
        $path = dirname(__DIR__, 2);
        require_once $path . '/vendor/autoload.php';
        Dotenv::createImmutable($path)->load();
    }

    protected function setUp(): void
    {
        $this->ociEnv = [
            'OCI_HOST' => $_ENV['OCI_HOST'],
            'OCI_PORT' => $_ENV['OCI_PORT'],
            'OCI_DATABASE' => $_ENV['OCI_DATABASE'],
            'OCI_USERNAME' => $_ENV['OCI_USERNAME'],
            'OCI_PASSWORD' => $_ENV['OCI_PASSWORD'],
            'OCI_CHARSET' => $_ENV['OCI_CHARSET']
        ];

        $this->connection = Chainable::nativeOCI($this->ociEnv, false, false);
    }

    public function testConnectionConstants()
    {
        $this->assertSame(1, OCI::FETCH_LAZY);
        $this->assertSame(2, OCI::FETCH_ASSOC);
        $this->assertSame(3, OCI::FETCH_NUM);
        $this->assertSame(4, OCI::FETCH_BOTH);
        $this->assertSame(5, OCI::FETCH_OBJ);
        $this->assertSame(6, OCI::FETCH_BOUND);
        $this->assertSame(7, OCI::FETCH_COLUMN);
        $this->assertSame(8, OCI::FETCH_CLASS);
        $this->assertSame(9, OCI::FETCH_INTO);
    }

    public function testConnection()
    {
        $this->assertInstanceOf(OCIConnection::class, $this->connection);
    }

    public function testConnectionSingleton()
    {
        $connection1 = OCIConnection::getInstance();
        $connection2 = OCIConnection::getInstance();

        $this->assertInstanceOf(OCIConnection::class, $connection1);
        $this->assertInstanceOf(OCIConnection::class, $connection2);
        $this->assertSame($connection1, $connection2);
    }

    public function testConnect()
    {
        $this->connection->connect();
        $this->assertInstanceOf(OCIConnection::class, $this->connection);
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
        $this->assertInstanceOf(OCIConnection::class, $this->connection);
    }

    public function testQuery()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado WHERE id = 5');
        $this->assertInstanceOf(OCIConnection::class, $this->connection);
    }

    public function testFetch()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(OCIConnection::class, $this->connection);
        $data = $this->connection->fetch();
        $this->assertIsObject($data);
    }

    public function testFetchAll()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(OCIConnection::class, $this->connection);
        $data = $this->connection->fetchAll();
        $this->assertIsArray($data);
    }

    public function testExec()
    {
        $this->connection->query("INSERT INTO estado (nome, sigla) VALUES ('TESTE', 'TE')");
        $this->connection->query("DELETE FROM estado WHERE nome = 'TESTE' AND sigla = 'TE'");
        $this->assertInstanceOf(OCIConnection::class, $this->connection);
    }

    public function testDisconnect()
    {
        $this->connection->disconnect();
        $this->assertInstanceOf(OCIConnection::class, $this->connection);
    }
}
