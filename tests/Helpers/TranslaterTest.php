<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\Translater;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

final class TranslaterTest extends TestCase
{
    private static string $userQuery = "SELECT * FROM users WHERE name = 'John'";
    public function testEscapeDefaultDialect()
    {
        $input = self::$userQuery;
        $expected = self::$userQuery;

        $actual = Translater::escape($input);

        $this->assertEquals($expected, $actual);
    }

    public function testEscapeDquoteDialect()
    {
        $input = 'SELECT * FROM users WHERE name = "John"';
        $expected = 'SELECT "*" FROM "users" WHERE "name" = "John"';

        $actual = Translater::escape($input, Translater::SQL_DIALECT_DQUOTE);

        $this->assertEquals($expected, $actual);
    }

    public function testEscapeSquoteDialect()
    {
        $input = self::$userQuery;
        $expected = "SELECT '*' FROM 'users' WHERE 'name' = 'John'";

        $actual = Translater::escape($input, Translater::SQL_DIALECT_SQUOTE);

        $this->assertEquals($expected, $actual);
    }

    public function testEscapeBtickDialect()
    {
        $input = self::$userQuery;
        $expected = "SELECT `*` FROM `users` WHERE `name` = 'John'";

        $actual = Translater::escape($input, Translater::SQL_DIALECT_BTICK);

        $this->assertEquals($expected, $actual);
    }

    public function testEscapeNoneDialect()
    {
        $input = self::$userQuery;
        $expected = self::$userQuery;

        $actual = Translater::escape($input, Translater::SQL_DIALECT_NONE);

        $this->assertEquals($expected, $actual);
    }

    public function testEscapeForbiddenWords()
    {
        $input = "SELECT * FROM users WHERE name = 'SELECT'";
        $expected = "SELECT * FROM users WHERE name = 'SELECT'";

        $actual = Translater::escape($input);

        $this->assertEquals($expected, $actual);
    }

    public function testBindingQuestionMark()
    {
        $input = "SELECT * FROM table WHERE id = :id";
        $expected = "SELECT * FROM table WHERE id = ?";
        $actual = Translater::binding($input);
        $this->assertEquals($expected, $actual);
    }

    public function testBindingDollarSign()
    {
        $input = "SELECT * FROM table WHERE id = :id";
        $expected = "SELECT * FROM table WHERE id = $1";
        $actual = Translater::binding($input, Translater::BIND_DOLLAR_SIGN);
        $this->assertEquals($expected, $actual);
    }

    public function testArguments()
    {
        $input = "SELECT * FROM users WHERE id = :id AND name = :name";
        $values = ['id' => 1, 'name' => 'John'];
        $arguments = Translater::arguments($input, $values);
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

        $reflectionClass = new ReflectionClass(Translater::class);
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

        $reflectionClass = new ReflectionClass(Translater::class);
        $method = $reflectionClass->getMethod('replaceParameters');
        $method->setAccessible(true); //NOSONAR

        $result = $method->invokeArgs(null, [$input, $quote, $forbiddenWords]);

        $this->assertEquals($input, $result);
    }

    public function testArgumentsWithNullValues()
    {
        $input = "SELECT * FROM users";

        $arguments = Translater::arguments($input, null);

        $this->assertEmpty($arguments);
    }
}
