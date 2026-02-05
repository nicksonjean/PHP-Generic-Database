<?php

declare(strict_types=1);

namespace GenericDatabase\Tests\Engine;

use PHPUnit\Framework\TestCase;
use GenericDatabase\QueryBuilder;
use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

/**
 * Tests for QueryBuilder UNION, UNION ALL, WHERE EXISTS and WHERE NOT EXISTS
 * as described in readme/DQL_EXISTS_UNION_SUBQUERY_IMPACT_ANALYSIS.md
 */
class UnionExistsQueryBuilderTest extends TestCase
{
    private $connection;

    public static function setUpBeforeClass(): void
    {
        $path = dirname(__DIR__, 2);
        require_once $path . '/vendor/autoload.php';
        Dotenv::createImmutable($path)->load();
    }

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('PDO SQLite extension is not loaded.');
        }
        $sqliteEnv = [
            'SQLITE_DATABASE' => './resources/database/sqlite/data/DB.SQLITE',
            'SQLITE_DATABASE_MEMORY' => $_ENV['SQLITE_DATABASE_MEMORY'] ?? ':memory:',
            'SQLITE_CHARSET' => $_ENV['SQLITE_CHARSET'] ?? 'utf8',
        ];
        $this->connection = Chainable::pdoSQLite($sqliteEnv, false, true)->connect();

        $this->connection->query("DROP TABLE IF EXISTS users");
        $this->connection->query("DROP TABLE IF EXISTS employees");
        $this->connection->query("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                name TEXT,
                city TEXT,
                salary REAL
            )
        ");
        $this->connection->query("
            CREATE TABLE employees (
                id INTEGER PRIMARY KEY,
                name TEXT,
                department TEXT,
                salary REAL
            )
        ");

        $this->connection->query("INSERT INTO users (name, city, salary) VALUES
            ('Alice', 'São Paulo', 5000),
            ('Bob', 'Rio de Janeiro', 6000),
            ('Charlie', 'São Paulo', 7000)");
        $this->connection->query("INSERT INTO employees (name, department, salary) VALUES
            ('David', 'IT', 5500),
            ('Eve', 'HR', 4500),
            ('Frank', 'IT', 6500)");
    }

    public function testUnionBuildsValidSql(): void
    {
        $sql = QueryBuilder::with($this->connection)
            ->select('name')
            ->from('users')
            ->union(
                QueryBuilder::with($this->connection)
                    ->select('name')
                    ->from('employees')
            )
            ->build();

        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('FROM', $sql);
        $this->assertStringContainsString('UNION', $sql);
        $this->assertMatchesRegularExpression('/UNION\s*\(/', $sql);
    }

    public function testUnionReturnsCombinedResults(): void
    {
        $results = QueryBuilder::with($this->connection)
            ->select('name')
            ->from('users')
            ->union(
                QueryBuilder::with($this->connection)
                    ->select('name')
                    ->from('employees')
            )
            ->fetchAll();

        $this->assertIsArray($results);
        $names = array_column($results, 'name');
        $this->assertContains('Alice', $names);
        $this->assertContains('David', $names);
        $this->assertCount(6, $results);
    }

    public function testUnionAllBuildsValidSql(): void
    {
        $sql = QueryBuilder::with($this->connection)
            ->select('name')
            ->from('users')
            ->unionAll(
                QueryBuilder::with($this->connection)
                    ->select('name')
                    ->from('employees')
            )
            ->build();

        $this->assertStringContainsString('UNION ALL', $sql);
        $this->assertMatchesRegularExpression('/UNION\s+ALL\s*\(/', $sql);
    }

    public function testUnionAllReturnsAllRows(): void
    {
        $results = QueryBuilder::with($this->connection)
            ->select('name')
            ->from('users')
            ->unionAll(
                QueryBuilder::with($this->connection)
                    ->select('name')
                    ->from('employees')
            )
            ->fetchAll();

        $this->assertIsArray($results);
        $this->assertCount(6, $results);
    }

    public function testWhereExistsBuildsValidSql(): void
    {
        $sql = QueryBuilder::with($this->connection)
            ->select('name', 'city')
            ->from('users')
            ->whereExists(
                QueryBuilder::with($this->connection)
                    ->select('1')
                    ->from('employees')
                    ->where('employees.name', '=', 'users.name')
            )
            ->build();

        $this->assertStringContainsString('EXISTS', $sql);
        $this->assertMatchesRegularExpression('/WHERE\s+EXISTS\s*\(/', $sql);
    }

    public function testWhereExistsReturnsMatchingRows(): void
    {
        $results = QueryBuilder::with($this->connection)
            ->select('name', 'city')
            ->from('users')
            ->whereExists(
                QueryBuilder::with($this->connection)
                    ->select('1')
                    ->from('employees')
                    ->where('employees.name', '=', 'users.name')
            )
            ->fetchAll();

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertSame('Alice', $results[0]['name']);
    }

    public function testWhereNotExistsBuildsValidSql(): void
    {
        $sql = QueryBuilder::with($this->connection)
            ->select('name', 'city')
            ->from('users')
            ->whereNotExists(
                QueryBuilder::with($this->connection)
                    ->select('1')
                    ->from('employees')
                    ->where('employees.name', '=', 'users.name')
            )
            ->build();

        $this->assertStringContainsString('NOT EXISTS', $sql);
    }

    public function testWhereNotExistsReturnsNonMatchingRows(): void
    {
        $results = QueryBuilder::with($this->connection)
            ->select('name', 'city')
            ->from('users')
            ->whereNotExists(
                QueryBuilder::with($this->connection)
                    ->select('1')
                    ->from('employees')
                    ->where('employees.name', '=', 'users.name')
            )
            ->fetchAll();

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $names = array_column($results, 'name');
        $this->assertContains('Bob', $names);
        $this->assertContains('Charlie', $names);
    }

    public function testGetValuesMergesSubqueryPlaceholders(): void
    {
        $qb = QueryBuilder::with($this->connection)
            ->select('name')
            ->from('users')
            ->where('name', '=', 'Alice')
            ->whereExists(
                QueryBuilder::with($this->connection)
                    ->select('1')
                    ->from('employees')
                    ->where('department', '=', 'IT')
            );

        $values = $qb->getValues();
        $this->assertIsArray($values);
        $this->assertContains('Alice', $values);
        $this->assertContains('IT', $values);
    }
}
