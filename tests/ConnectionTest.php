<?php

namespace GenericDatabase\Tests;

use PDO;
use PDOStatement;
use stdClass;
use Dotenv\Dotenv;
use GenericDatabase\Connection;
use PHPUnit\Framework\TestCase;
use GenericDatabase\IConnection;

class ConnectionTest extends TestCase
{
    private array $mysqlEnv;
    private Connection $connection;
    private IConnection $strategy;

    public static function setUpBeforeClass(): void
    {
        $path = dirname(__DIR__, 1);
        require_once $path . '/vendor/autoload.php';
        Dotenv::createImmutable($path)->load();
    }

    protected function setUp(): void
    {
        $this->mysqlEnv = [
            'MYSQL_HOST' => $_ENV['MYSQL_HOST'],
            'MYSQL_PORT' => $_ENV['MYSQL_PORT'],
            'MYSQL_DATABASE' => $_ENV['MYSQL_DATABASE'],
            'MYSQL_USERNAME' => $_ENV['MYSQL_USERNAME'],
            'MYSQL_PASSWORD' => $_ENV['MYSQL_PASSWORD'],
            'MYSQL_CHARSET' => $_ENV['MYSQL_CHARSET']
        ];
        $this->strategy = $this->createMock(IConnection::class);
        $this->connection = new Connection();
        $this->connection->setStrategy($this->strategy);
    }

    public function testConnectionMethods(): void
    {
        $mockPDO = $this->createMock(PDO::class);
        $mockPDO->method('errorCode')->willReturn('00000');
        $mockPDO->method('errorInfo')->willReturn(['00000', null, null]);

        $this->strategy->expects($this->once())->method('setConnection')->with($mockPDO);
        $this->strategy->expects($this->exactly(3))->method('getConnection')->willReturn($mockPDO);

        $result = $this->connection->setConnection($mockPDO);
        $this->assertSame($mockPDO, $result);

        $this->strategy->expects($this->once())->method('lastInsertId')->with('test_table')->willReturn(123);
        $this->assertEquals(123, $this->connection->lastInsertId('test_table'));

        $metadata = (object) ['queryRows' => 10, 'affectedRows' => 5];
        $this->strategy->expects($this->once())->method('getAllMetadata')->willReturn($metadata);
        $this->assertEquals($metadata, $this->connection->getAllMetadata());

        $this->strategy->expects($this->once())->method('getQueryString')->willReturn('SELECT * FROM test');
        $this->assertEquals('SELECT * FROM test', $this->connection->getQueryString());

        $this->connection->errorCode();
        $this->connection->errorInfo();
    }

    public function testAttributeMethods(): void
    {
        $attrName = 'test_attr';
        $attrValue = 'test_value';

        $this->strategy->expects($this->once())
            ->method('getAttribute')
            ->with($attrName)
            ->willReturn($attrValue);

        $this->strategy->expects($this->once())
            ->method('setAttribute')
            ->with($attrName, $attrValue);

        $this->assertEquals($attrValue, $this->connection->getAttribute($attrName));
        $this->connection->setAttribute($attrName, $attrValue);
    }

    public function testTransactionMethods(): void
    {
        $this->strategy->expects($this->once())->method('beginTransaction')->willReturn(true);
        $this->assertTrue($this->connection->beginTransaction());

        $this->strategy->expects($this->once())->method('commit')->willReturn(true);
        $this->assertTrue($this->connection->commit());

        $this->strategy->expects($this->once())->method('rollback')->willReturn(true);
        $this->assertTrue($this->connection->rollback());

        $this->strategy->expects($this->once())->method('inTransaction')->willReturn(true);
        $this->assertTrue($this->connection->inTransaction());
    }
    public function testRowsAndColumnsMethods(): void
    {
        // Test query rows
        $this->strategy->expects($this->once())->method('getQueryRows')->willReturn(5);
        $this->assertEquals(5, $this->connection->getQueryRows());

        // Test affected rows
        $this->strategy->expects($this->once())->method('getAffectedRows')->willReturn(3);
        $this->assertEquals(3, $this->connection->getAffectedRows());

        // Test query columns
        $this->strategy->expects($this->once())->method('getQueryColumns')->willReturn(4);
        $this->assertEquals(4, $this->connection->getQueryColumns());
    }

    public function testQueryExecutionMethods(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);

        $this->strategy->expects($this->once())->method('query')->willReturn($mockStatement);
        $this->assertSame($mockStatement, $this->connection->query('SELECT * FROM test'));

        $this->strategy->expects($this->once())->method('prepare')->willReturn($mockStatement);
        $this->assertSame($mockStatement, $this->connection->prepare('SELECT * FROM test WHERE id = ?'));

        $this->strategy->expects($this->once())->method('exec')->willReturn(1);
        $this->assertEquals(1, $this->connection->exec('DELETE FROM test WHERE id = 1'));
    }

    public function testFetchMethods(): void
    {
        $this->strategy->expects($this->once())
            ->method('fetch')
            ->with(Connection::FETCH_ASSOC)
            ->willReturn(['id' => 1]);
        $this->assertEquals(['id' => 1], $this->connection->fetch(Connection::FETCH_ASSOC));

        $this->strategy->expects($this->once())
            ->method('fetchAll')
            ->with(Connection::FETCH_OBJ)
            ->willReturn([
                (object) ['id' => 1],
                (object) ['id' => 2]
            ]);
        $this->assertEquals(
            [(object) ['id' => 1], (object) ['id' => 2]],
            $this->connection->fetchAll(Connection::FETCH_OBJ)
        );
    }

    public function testConnectionStateMethods(): void
    {
        $this->strategy->expects($this->once())->method('connect')->willReturn($this->connection);
        $this->assertSame($this->connection, $this->connection->connect());

        $this->strategy->expects($this->once())->method('disconnect');
        $this->connection->disconnect();

        $this->strategy->expects($this->once())->method('ping')->willReturn(true);
        $this->assertTrue($this->connection->ping());

        $this->strategy->expects($this->once())->method('isConnected')->willReturn(true);
        $this->assertTrue($this->connection->isConnected());
    }

    public function testLoadFromFile(): void
    {
        $this->strategy->expects($this->once())
            ->method('loadFromFile')
            ->with('dump.sql', ';', null)
            ->willReturn(10);
        $this->assertEquals(10, $this->connection->loadFromFile('dump.sql'));
    }

    public function testQuoteMethod(): void
    {
        $this->strategy->expects($this->once())
            ->method('quote')
            ->with("O'Reilly")
            ->willReturn("'O\'Reilly'");
        $this->assertEquals("'O\'Reilly'", $this->connection->quote("O'Reilly"));
    }

    public function testQueryStringMethods(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = ?';

        $this->strategy->expects($this->once())
            ->method('setQueryString')
            ->with($queryString);

        $this->connection->setQueryString($queryString);
    }

    public function testQueryParametersMethods(): void
    {
        $params = ['id' => 1, 'name' => 'John'];

        $this->strategy->expects($this->once())
            ->method('setQueryParameters')
            ->with($params);
        $this->connection->setQueryParameters($params);

        $this->strategy->expects($this->once())
            ->method('getQueryParameters')
            ->willReturn($params);
        $this->assertEquals($params, $this->connection->getQueryParameters());
    }

    public function testQueryRowsAndColumnsMethods(): void
    {
        $rowCount = 5;
        $this->strategy->expects($this->once())
            ->method('setQueryRows')
            ->with($rowCount);
        $this->connection->setQueryRows($rowCount);

        $columnCount = 3;
        $this->strategy->expects($this->once())
            ->method('setQueryColumns')
            ->with($columnCount);
        $this->connection->setQueryColumns($columnCount);
    }

    public function testAffectedRowsMethods(): void
    {
        $affectedRows = 10;
        $this->strategy->expects($this->once())
            ->method('setAffectedRows')
            ->with($affectedRows);
        $this->connection->setAffectedRows($affectedRows);
    }

    public function testStatementMethods(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);

        $this->strategy->expects($this->once())
            ->method('setStatement')
            ->with($mockStatement);
        $this->connection->setStatement($mockStatement);

        $this->strategy->expects($this->once())
            ->method('getStatement')
            ->willReturn($mockStatement);
        $this->assertSame($mockStatement, $this->connection->getStatement());
    }
}
