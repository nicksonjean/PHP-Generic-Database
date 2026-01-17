<?php

namespace GenericDatabase\Tests;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Connection;
use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

/**
 * @group ignore-test-cases
 */
class ConnectionFirebirdTest extends TestCase
{
    private array $firebirdEnv;

    private $connection;

    public static function setUpBeforeClass(): void
    {
        $path = dirname(__DIR__, 1);
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

        $this->connection = Chainable::nativeFirebird($this->firebirdEnv, false, true);
    }

    public function testConnectionConstants()
    {
        $this->assertSame(1, Connection::FETCH_LAZY);
        $this->assertSame(2, Connection::FETCH_ASSOC);
        $this->assertSame(3, Connection::FETCH_NUM);
        $this->assertSame(4, Connection::FETCH_BOTH);
        $this->assertSame(5, Connection::FETCH_OBJ);
        $this->assertSame(6, Connection::FETCH_BOUND);
        $this->assertSame(7, Connection::FETCH_COLUMN);
        $this->assertSame(8, Connection::FETCH_CLASS);
        $this->assertSame(9, Connection::FETCH_INTO);
    }

    public function testConnectionSingleton()
    {
        $connection1 = Connection::getInstance();
        $connection2 = Connection::getInstance();

        $this->assertInstanceOf(Connection::class, $connection1);
        $this->assertInstanceOf(Connection::class, $connection2);
        $this->assertSame($connection1, $connection2);

        $ini = Connection::new('./resources/dsn/ini/stg_firebird.ini');
        $this->assertInstanceOf(Connection::class, $ini);

        $json = Connection::new('./resources/dsn/json/stg_firebird.json');
        $this->assertInstanceOf(Connection::class, $json);

        $xml = Connection::new('./resources/dsn/xml/stg_firebird.xml');
        $this->assertInstanceOf(Connection::class, $xml);

        $yaml = Connection::new('./resources/dsn/yaml/stg_firebird.yaml');
        $this->assertInstanceOf(Connection::class, $yaml);
    }

    public function testConnect()
    {
        $this->connection->connect();
        $this->assertInstanceOf(Connection::class, $this->connection);
    }

    public function testCallWithByStaticArrayThroughCallStatic(): void
    {
        $connection = Connection::new($this->firebirdEnv);
        $this->assertEquals($_ENV['FBIRD_HOST'], $connection->getHost());
        $this->assertEquals($_ENV['FBIRD_PORT'], $connection->getPort());
        $this->assertEquals($_ENV['FBIRD_DATABASE'], $connection->getDatabase());
        $this->assertEquals($_ENV['FBIRD_USERNAME'], $connection->getUser());
        $this->assertEquals($_ENV['FBIRD_PASSWORD'], $connection->getPassword());
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

    public function testGetEngine()
    {
        $engine = $this->connection->getEngine();
        $this->assertEquals("firebird", $engine);
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
        $this->assertIsObject($data);
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
