<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\Parsers\SQL\Parse;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

final class SQLTest extends TestCase
{
    private static string $userQuery = "SELECT * FROM users WHERE name = 'John'";
    public function testEscapeDefaultDialect()
    {
        $input = self::$userQuery;
        $expected = self::$userQuery;

        $actual = Parse::escape($input);

        $this->assertEquals($expected, $actual);
    }

    public function testEscapeDquoteDialect()
    {
        $input = 'SELECT * FROM users WHERE name = "John"';
        // PostgreSQL/SQLite: identifiers use ", literals use ' (SQL standard)
        $expected = 'SELECT * FROM "users" WHERE "name" = \'John\'';

        $actual = Parse::escape($input, Parse::SQL_DIALECT_DOUBLE_QUOTE);

        $this->assertEquals($expected, $actual);
    }

    public function testEscapeSquoteDialect()
    {
        $input = self::$userQuery;
        $expected = "SELECT * FROM 'users' WHERE 'name' = 'John'";

        $actual = Parse::escape($input, Parse::SQL_DIALECT_SINGLE_QUOTE);

        $this->assertEquals($expected, $actual);
    }

    public function testEscapeBtickDialect()
    {
        $input = self::$userQuery;
        $expected = "SELECT * FROM `users` WHERE `name` = 'John'";

        $actual = Parse::escape($input, Parse::SQL_DIALECT_BACKTICK);

        $this->assertEquals($expected, $actual);
    }

    public function testEscapeNoneDialect()
    {
        $input = self::$userQuery;
        $expected = self::$userQuery;

        $actual = Parse::escape($input, Parse::SQL_DIALECT_NONE);

        $this->assertEquals($expected, $actual);
    }

    public function testEscapeForbiddenWords()
    {
        $input = "SELECT * FROM users WHERE name = 'SELECT'";
        $expected = "SELECT * FROM users WHERE name = 'SELECT'";

        $actual = Parse::escape($input);

        $this->assertEquals($expected, $actual);
    }

    public function testBindingQuestionMark()
    {
        $input = "SELECT * FROM table WHERE id = :id";
        $expected = "SELECT * FROM table WHERE id = ?";
        $actual = Parse::binding($input);
        $this->assertEquals($expected, $actual);
    }

    public function testBindingDollarSign()
    {
        $input = "SELECT * FROM table WHERE id = :id";
        $expected = "SELECT * FROM table WHERE id = $1";
        $actual = Parse::binding($input, Parse::BIND_DOLLAR_SIGN);
        $this->assertEquals($expected, $actual);
    }

    public function testArguments()
    {
        $input = "SELECT * FROM users WHERE id = :id AND name = :name";
        $values = ['id' => 1, 'name' => 'John'];
        $arguments = Parse::arguments($input, $values);
        $this->assertEquals([':id' => 1, ':name' => 'John'], $arguments);
    }

    /**
     * @throws ReflectionException
     */
    public function testReplaceParameters()
    {
        $input = "INSERT INTO estado (id) VALUES (:id)";
        $quote = '"';
        $forbiddenWords = ['INSERT', 'INTO', 'VALUES'];

        $reflectionClass = new ReflectionClass(Parse::class);
        $method = $reflectionClass->getMethod('replaceParameters');
        $method->setAccessible(true); //NOSONAR

        $result = $method->invokeArgs(null, [$input, $quote, $forbiddenWords]);

        $this->assertStringContainsString('id', $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testReplaceParametersWithNoMatches()
    {
        $input = "INSERT INTO estado (id) VALUES (:id)";
        $quote = '';
        $forbiddenWords = [''];

        $reflectionClass = new ReflectionClass(Parse::class);
        $method = $reflectionClass->getMethod('replaceParameters');
        $method->setAccessible(true); //NOSONAR

        $result = $method->invokeArgs(null, [$input, $quote, $forbiddenWords]);

        $this->assertEquals($input, $result);
    }

    public function testArgumentsWithNullValues()
    {
        $input = "SELECT * FROM users";

        $arguments = Parse::arguments($input, null);

        $this->assertEmpty($arguments);
    }

    public function testParseParametersFromRawQuery(): void
    {
        $input = "SELECT * FROM users WHERE id = :id AND name = :name";
        $parameters = Parse::parseParameters($input);

        $this->assertEquals([':id', ':name'], $parameters);
    }

    public function testParseParametersPositionalPlaceholders(): void
    {
        $input = "SELECT * FROM users WHERE id = ? AND name = ?";
        $parameters = Parse::parseParameters($input);

        $this->assertEquals([0, 1], $parameters);
    }

    public function testParseParametersExtractLimitOffset(): void
    {
        $input = "SELECT * FROM users ORDER BY id LIMIT 10";
        $parameters = Parse::parseParameters($input);

        $this->assertEquals([10], $parameters);
    }

    public function testParseParametersExtractLimitAndOffset(): void
    {
        $input = "SELECT * FROM users ORDER BY id LIMIT 10 OFFSET 0";
        $parameters = Parse::parseParameters($input);

        $this->assertEquals([10, 0], $parameters);
    }

    public function testParseParametersExtractHavingAndLimit(): void
    {
        $input = "SELECT e.id, COUNT(c.id) AS total FROM estado e JOIN cidade c ON c.estado_id = e.id "
            . "GROUP BY e.id HAVING COUNT(c.id) > 50 ORDER BY total DESC LIMIT 5";
        $parameters = Parse::parseParameters($input);

        $this->assertEquals([50, 5], $parameters);
    }

    public function testParseParametersExtractStringLiteral(): void
    {
        // Single-quoted strings only (double-quoted = identifiers in PostgreSQL/SQLite)
        $input = "SELECT * FROM estado WHERE nome LIKE '%Rio%'";
        $parameters = Parse::parseParameters($input);

        $this->assertEquals(['%Rio%'], $parameters);
    }

    public function testParseParametersExtractMixedLiterals(): void
    {
        $input = "SELECT * FROM estado WHERE nome LIKE '%Rio%' AND id > 10 LIMIT 5";
        $parameters = Parse::parseParameters($input);

        $this->assertEquals(['%Rio%', 10, 5], $parameters);
    }

    public function testEscapeCompoundStringLiteralNoBackticks(): void
    {
        $input = 'SELECT id, nome FROM estado WHERE nome = "Rio de Janeiro"';
        $result = Parse::escape($input, Parse::SQL_DIALECT_BACKTICK);

        $this->assertStringNotContainsString('`de`', $result);
        $this->assertStringContainsString('Rio de Janeiro', $result);
    }

    public function testEscapeCompoundStringLiteralPgSQL(): void
    {
        $input = 'SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE nome = "Rio de Janeiro"';
        $result = Parse::escape($input, Parse::SQL_DIALECT_DOUBLE_QUOTE);

        $this->assertStringContainsString("'Rio de Janeiro'", $result);
        $this->assertStringNotContainsString('"Rio de Janeiro"', $result);
    }

    public function testParseParametersExcludesDoubleQuotedIdentifiers(): void
    {
        // Escaped PostgreSQL-style query: double-quoted = identifiers, single-quoted = literals
        $input = 'SELECT "id" AS "Codigo", "nome" AS "Estado" FROM "estado" WHERE "nome" = \'Rio de Janeiro\'';
        $parameters = Parse::parseParameters($input);

        $this->assertEquals(['Rio de Janeiro'], $parameters);
    }

    public function testParseParametersExtractDoubleQuotedForBacktickDialect(): void
    {
        // MySQL: double-quoted = string literals, backticks = identifiers
        $input = 'SELECT `id`, `nome` FROM `estado` WHERE `nome` = "Rio de Janeiro"';
        $parameters = Parse::parseParameters($input, Parse::SQL_DIALECT_BACKTICK);

        $this->assertEquals(['Rio de Janeiro'], $parameters);
    }
}
