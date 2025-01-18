<?php

namespace GenericDatabase\Tests\Engine;

use PHPUnit\Framework\TestCase;
use GenericDatabase\Engine\SQLiteConnection;
use GenericDatabase\Engine\SQLite\Connection\SQLite;
use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

class SqliteEngineTest extends TestCase
{
    private array $sqliteEnv;

    private $connection;

    public static function setUpBeforeClass(): void
    {
        $path = dirname(__DIR__, 2);
        require_once $path . '/vendor/autoload.php';
        Dotenv::createImmutable($path)->load();
    }

    protected function setUp(): void
    {
        $this->sqliteEnv = [
            'SQLITE_DATABASE' => "./resources/database/sqlite/data/DB.SQLITE",
            'SQLITE_DATABASE_MEMORY' => $_ENV['SQLITE_DATABASE_MEMORY'],
            'SQLITE_CHARSET' => $_ENV['SQLITE_CHARSET']
        ];

        $this->connection = Chainable::nativeSQLite($this->sqliteEnv, false, false);
    }

    public function testConnectionConstants()
    {
        $this->assertSame(1, SQLite::FETCH_LAZY);
        $this->assertSame(2, SQLite::FETCH_ASSOC);
        $this->assertSame(3, SQLite::FETCH_NUM);
        $this->assertSame(4, SQLite::FETCH_BOTH);
        $this->assertSame(5, SQLite::FETCH_OBJ);
        $this->assertSame(6, SQLite::FETCH_BOUND);
        $this->assertSame(7, SQLite::FETCH_COLUMN);
        $this->assertSame(8, SQLite::FETCH_CLASS);
        $this->assertSame(9, SQLite::FETCH_INTO);
    }

    public function testConnection()
    {
        $this->assertInstanceOf(SQLiteConnection::class, $this->connection);
    }

    public function testConnectionSingleton()
    {
        $connection1 = SQLiteConnection::getInstance();
        $connection2 = SQLiteConnection::getInstance();

        $this->assertInstanceOf(SQLiteConnection::class, $connection1);
        $this->assertInstanceOf(SQLiteConnection::class, $connection2);
        $this->assertSame($connection1, $connection2);
    }

    public function testConnect()
    {
        $this->connection->connect();
        $this->assertInstanceOf(SQLiteConnection::class, $this->connection);
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
        $this->assertInstanceOf(SQLiteConnection::class, $this->connection);
    }

    public function testQuery()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado WHERE id = 5');
        $this->assertInstanceOf(SQLiteConnection::class, $this->connection);
    }

    public function testFetch()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(SQLiteConnection::class, $this->connection);
        $data = $this->connection->fetch();
        $this->assertIsObject($data);
    }

    public function testFetchAll()
    {
        $this->connection->query('SELECT id AS Codigo FROM estado');
        $this->assertInstanceOf(SQLiteConnection::class, $this->connection);
        $data = $this->connection->fetchAll();
        $this->assertIsArray($data);
    }

    public function testExec()
    {
        $this->connection->query("INSERT INTO estado (nome, sigla) VALUES ('TESTE', 'TE')");
        $this->connection->query("DELETE FROM estado WHERE nome = 'TESTE' AND sigla = 'TE'");
        $this->assertInstanceOf(SQLiteConnection::class, $this->connection);
    }

    public function testDisconnect()
    {
        $this->connection->disconnect();
        $this->assertInstanceOf(SQLiteConnection::class, $this->connection);
    }
}
