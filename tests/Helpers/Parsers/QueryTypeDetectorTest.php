<?php

namespace GenericDatabase\Tests\Helpers\Parsers;

use GenericDatabase\Helpers\Parsers\SQL\Query\TypeDetector;
use GenericDatabase\Helpers\Parsers\SQL\Query\Info;
use PHPUnit\Framework\TestCase;

final class QueryTypeDetectorTest extends TestCase
{
    // =============================================
    // Basic Query Type Detection Tests
    // =============================================

    public function testDetectSimpleSelect(): void
    {
        $this->assertEquals(
            TypeDetector::TYPE_SELECT,
            TypeDetector::detect('SELECT * FROM users')
        );
    }

    public function testDetectSimpleInsert(): void
    {
        $this->assertEquals(
            TypeDetector::TYPE_INSERT,
            TypeDetector::detect("INSERT INTO users (name) VALUES ('test')")
        );
    }

    public function testDetectSimpleUpdate(): void
    {
        $this->assertEquals(
            TypeDetector::TYPE_UPDATE,
            TypeDetector::detect("UPDATE users SET name = 'new' WHERE id = 1")
        );
    }

    public function testDetectSimpleDelete(): void
    {
        $this->assertEquals(
            TypeDetector::TYPE_DELETE,
            TypeDetector::detect('DELETE FROM users WHERE id = 1')
        );
    }

    public function testDetectEmptyQuery(): void
    {
        $this->assertEquals(
            TypeDetector::TYPE_UNKNOWN,
            TypeDetector::detect('')
        );
    }

    public function testDetectWhitespaceOnlyQuery(): void
    {
        $this->assertEquals(
            TypeDetector::TYPE_UNKNOWN,
            TypeDetector::detect('   ')
        );
    }

    // =============================================
    // CTE (WITH) Query Tests
    // =============================================

    public function testDetectCTEWithSelect(): void
    {
        $query = 'WITH cte AS (SELECT * FROM users) SELECT * FROM cte';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectCTEWithInsert(): void
    {
        $query = 'WITH cte AS (SELECT * FROM users) INSERT INTO archive SELECT * FROM cte';
        $this->assertEquals(TypeDetector::TYPE_INSERT, TypeDetector::detect($query));
    }

    public function testDetectCTEWithDelete(): void
    {
        $query = 'WITH cte AS (SELECT id FROM banned) DELETE FROM users WHERE id IN (SELECT id FROM cte)';
        $this->assertEquals(TypeDetector::TYPE_DELETE, TypeDetector::detect($query));
    }

    public function testDetectCTEWithUpdate(): void
    {
        $query = 'WITH cte AS (SELECT id, new_status FROM status_updates) UPDATE users SET status = (SELECT new_status FROM cte WHERE cte.id = users.id)';
        $this->assertEquals(TypeDetector::TYPE_UPDATE, TypeDetector::detect($query));
    }

    public function testDetectRecursiveCTE(): void
    {
        $query = 'WITH RECURSIVE cte AS (SELECT 1 AS n UNION ALL SELECT n + 1 FROM cte WHERE n < 10) SELECT * FROM cte';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectMultipleCTEs(): void
    {
        $query = 'WITH cte1 AS (SELECT * FROM t1), cte2 AS (SELECT * FROM t2) SELECT * FROM cte1 JOIN cte2';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    // =============================================
    // SQL Comment Tests
    // =============================================

    public function testDetectWithMultiLineCommentBefore(): void
    {
        $query = '/* This is a comment */ SELECT * FROM users';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectWithSingleLineCommentBefore(): void
    {
        $query = "-- This is a comment\nSELECT * FROM users";
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectWithInlineComment(): void
    {
        $query = 'SELECT /* inline comment */ * FROM users';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectWithMySQLHashComment(): void
    {
        $query = "# MySQL comment\nSELECT * FROM users";
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectWithMultipleComments(): void
    {
        $query = "/* comment 1 */ -- comment 2\n/* comment 3 */ SELECT * FROM users";
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    // =============================================
    // Compound Query (INSERT...SELECT) Tests
    // =============================================

    public function testDetectInsertSelect(): void
    {
        $query = 'INSERT INTO archive SELECT * FROM users';
        $this->assertEquals(TypeDetector::TYPE_INSERT, TypeDetector::detect($query));
    }

    public function testDetectInsertSelectWithColumns(): void
    {
        $query = 'INSERT INTO t1 (a, b) SELECT x, y FROM t2 WHERE z > 0';
        $this->assertEquals(TypeDetector::TYPE_INSERT, TypeDetector::detect($query));
    }

    // =============================================
    // Subquery Tests
    // =============================================

    public function testHasSubqueryInWhere(): void
    {
        $query = 'SELECT * FROM users WHERE id IN (SELECT user_id FROM orders)';
        $this->assertTrue(TypeDetector::hasSubquery($query));
    }

    public function testHasSubqueryInFrom(): void
    {
        $query = 'SELECT * FROM (SELECT * FROM users) AS sub';
        $this->assertTrue(TypeDetector::hasSubquery($query));
    }

    public function testHasSubqueryWithExists(): void
    {
        $query = 'DELETE FROM users WHERE EXISTS (SELECT 1 FROM banned WHERE banned.id = users.id)';
        $this->assertTrue(TypeDetector::hasSubquery($query));
    }

    public function testHasNoSubquery(): void
    {
        $query = 'SELECT * FROM users WHERE id = 1';
        $this->assertFalse(TypeDetector::hasSubquery($query));
    }

    public function testDetectSelectWithSubquery(): void
    {
        $query = 'SELECT * FROM users WHERE id IN (SELECT user_id FROM orders)';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectDeleteWithSubquery(): void
    {
        $query = 'DELETE FROM users WHERE EXISTS (SELECT 1 FROM banned WHERE banned.id = users.id)';
        $this->assertEquals(TypeDetector::TYPE_DELETE, TypeDetector::detect($query));
    }

    public function testDetectUpdateWithSubquery(): void
    {
        $query = "UPDATE users SET status = (SELECT code FROM statuses WHERE name = 'active') WHERE id = 1";
        $this->assertEquals(TypeDetector::TYPE_UPDATE, TypeDetector::detect($query));
    }

    // =============================================
    // UNION/INTERSECT/EXCEPT Tests
    // =============================================

    public function testDetectUnion(): void
    {
        $query = 'SELECT a FROM t1 UNION SELECT b FROM t2';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectUnionAll(): void
    {
        $query = 'SELECT a FROM t1 UNION ALL SELECT b FROM t2';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectIntersect(): void
    {
        $query = 'SELECT a FROM t1 INTERSECT SELECT b FROM t2';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectExcept(): void
    {
        $query = 'SELECT a FROM t1 EXCEPT SELECT b FROM t2';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectMultipleSetOperations(): void
    {
        $query = 'SELECT a FROM t1 UNION ALL SELECT b FROM t2 INTERSECT SELECT c FROM t3';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectParenthesizedSetOperations(): void
    {
        $query = '(SELECT a FROM t1) EXCEPT (SELECT b FROM t2)';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    // =============================================
    // Complex Nested Query Tests
    // =============================================

    public function testDetectNestedSubqueryInSelect(): void
    {
        $query = 'SELECT * FROM t1 WHERE a > (SELECT AVG(b) FROM t2 WHERE t2.id = t1.id)';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectDeeplyNestedSubqueries(): void
    {
        $query = 'SELECT * FROM t1 WHERE id IN (SELECT id FROM t2 WHERE status IN (SELECT status FROM t3))';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
        $this->assertTrue(TypeDetector::hasSubquery($query));
    }

    // =============================================
    // Helper Method Tests
    // =============================================

    public function testIsSelectQuery(): void
    {
        $this->assertTrue(TypeDetector::isSelectQuery('SELECT * FROM users'));
        $this->assertFalse(TypeDetector::isSelectQuery('INSERT INTO users (name) VALUES ("test")'));
        $this->assertFalse(TypeDetector::isSelectQuery('UPDATE users SET name = "new"'));
        $this->assertFalse(TypeDetector::isSelectQuery('DELETE FROM users'));
    }

    public function testIsDmlQuery(): void
    {
        $this->assertFalse(TypeDetector::isDmlQuery('SELECT * FROM users'));
        $this->assertTrue(TypeDetector::isDmlQuery('INSERT INTO users (name) VALUES ("test")'));
        $this->assertTrue(TypeDetector::isDmlQuery('UPDATE users SET name = "new"'));
        $this->assertTrue(TypeDetector::isDmlQuery('DELETE FROM users'));
    }

    // =============================================
    // Query Analysis Tests
    // =============================================

    public function testAnalyzeSimpleSelect(): void
    {
        $info = TypeDetector::analyze('SELECT * FROM users');

        $this->assertInstanceOf(Info::class, $info);
        $this->assertEquals(TypeDetector::TYPE_SELECT, $info->primaryType);
        $this->assertFalse($info->isCompound);
        $this->assertFalse($info->hasSubquery);
        $this->assertContains('SELECT', $info->operations);
        $this->assertContains('users', $info->tables);
    }

    public function testAnalyzeInsertSelect(): void
    {
        $info = TypeDetector::analyze('INSERT INTO archive SELECT * FROM users WHERE status = "inactive"');

        $this->assertEquals(TypeDetector::TYPE_INSERT, $info->primaryType);
        $this->assertTrue($info->isCompound);
        $this->assertContains('INSERT', $info->operations);
        $this->assertContains('SELECT', $info->operations);
        $this->assertContains('archive', $info->tables);
        $this->assertContains('users', $info->tables);
    }

    public function testAnalyzeUnionQuery(): void
    {
        $info = TypeDetector::analyze('SELECT id FROM t1 UNION SELECT id FROM t2');

        $this->assertEquals(TypeDetector::TYPE_SELECT, $info->primaryType);
        $this->assertTrue($info->isCompound);
        $this->assertContains('UNION', $info->operations);
    }

    public function testAnalyzeWithSubquery(): void
    {
        $info = TypeDetector::analyze('SELECT * FROM users WHERE id IN (SELECT user_id FROM orders)');

        $this->assertEquals(TypeDetector::TYPE_SELECT, $info->primaryType);
        $this->assertTrue($info->hasSubquery);
        $this->assertContains('users', $info->tables);
        $this->assertContains('orders', $info->tables);
    }

    public function testAnalyzeCTEQuery(): void
    {
        $info = TypeDetector::analyze('WITH active_users AS (SELECT * FROM users WHERE status = "active") SELECT * FROM active_users');

        $this->assertEquals(TypeDetector::TYPE_SELECT, $info->primaryType);
        $this->assertContains('WITH', $info->operations);
        $this->assertContains('SELECT', $info->operations);
    }

    public function testAnalyzeJoinQuery(): void
    {
        $info = TypeDetector::analyze('SELECT u.name, o.total FROM users u JOIN orders o ON u.id = o.user_id');

        $this->assertEquals(TypeDetector::TYPE_SELECT, $info->primaryType);
        $this->assertContains('users', $info->tables);
        $this->assertContains('orders', $info->tables);
    }

    // =============================================
    // QueryInfo DTO Tests
    // =============================================

    public function testQueryInfoIsSelect(): void
    {
        $info = new Info(TypeDetector::TYPE_SELECT, false, false, ['SELECT'], ['users']);
        $this->assertTrue($info->isSelect());
        $this->assertFalse($info->isDml());
    }

    public function testQueryInfoIsDml(): void
    {
        $info = new Info(TypeDetector::TYPE_INSERT, false, false, ['INSERT'], ['users']);
        $this->assertFalse($info->isSelect());
        $this->assertTrue($info->isDml());
    }

    public function testQueryInfoIsInsert(): void
    {
        $info = new Info(TypeDetector::TYPE_INSERT, false, false, ['INSERT'], ['users']);
        $this->assertTrue($info->isInsert());
    }

    public function testQueryInfoIsUpdate(): void
    {
        $info = new Info(TypeDetector::TYPE_UPDATE, false, false, ['UPDATE'], ['users']);
        $this->assertTrue($info->isUpdate());
    }

    public function testQueryInfoIsDelete(): void
    {
        $info = new Info(TypeDetector::TYPE_DELETE, false, false, ['DELETE'], ['users']);
        $this->assertTrue($info->isDelete());
    }

    // =============================================
    // Edge Case Tests
    // =============================================

    public function testDetectCaseInsensitive(): void
    {
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect('select * from users'));
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect('SELECT * FROM users'));
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect('Select * From users'));
    }

    public function testDetectWithLeadingWhitespace(): void
    {
        $query = '   SELECT * FROM users';
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectWithLeadingNewlines(): void
    {
        $query = "\n\n\nSELECT * FROM users";
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testDetectWithTabs(): void
    {
        $query = "\t\tSELECT * FROM users";
        $this->assertEquals(TypeDetector::TYPE_SELECT, TypeDetector::detect($query));
    }

    public function testGetOperationsOrder(): void
    {
        $operations = TypeDetector::getOperations('SELECT a FROM t1 UNION SELECT b FROM t2');
        $this->assertEquals(['SELECT', 'UNION', 'SELECT'], $operations);
    }

    public function testGetOperationsWithCTE(): void
    {
        $operations = TypeDetector::getOperations('WITH cte AS (SELECT * FROM t1) SELECT * FROM cte');
        $this->assertContains('WITH', $operations);
        $this->assertContains('SELECT', $operations);
    }
}
