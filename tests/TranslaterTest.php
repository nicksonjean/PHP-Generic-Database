<?php

namespace GenericDatabase\Tests;

use GenericDatabase\Helpers\Translater;
use PHPUnit\Framework\TestCase;

final class TranslaterTest extends TestCase
{
    private static $oneParamInputQuery;
    private static $twoParamInputQuery;

    public static function setUpBeforeClass(): void
    {
        self::$oneParamInputQuery = "SELECT * FROM users WHERE name = :name";
        self::$twoParamInputQuery = "SELECT * FROM users WHERE id = :id AND name = :name";
    }

    public function testTranslateSqlQueryNoParameters()
    {
        $input = "SELECT * FROM users";
        $expected = "SELECT * FROM users";

        $actual = Translater::escape($input);

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateSqlQueryNamedParameters()
    {
        $input = self::$twoParamInputQuery;
        $expected = "SELECT * FROM users WHERE id = ? AND name = ?";

        $actual = Translater::binding($input);

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateSqlQueryUnnamedParameters()
    {
        $input = self::$twoParamInputQuery;
        $expected = "SELECT * FROM users WHERE id = ? AND name = ?";

        $actual = Translater::binding($input, Translater::BIND_QUESTION_MARK);

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateSqlQuerySingleQuotes()
    {
        $input = self::$oneParamInputQuery;
        $expected = "SELECT '*' FROM 'users' WHERE 'name' = ?";

        $actual = Translater::binding(
            Translater::escape($input, Translater::SQL_DIALECT_SQUOTE),
            Translater::BIND_QUESTION_MARK
        );

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateSqlQueryDoubleQuotes()
    {
        $input = self::$oneParamInputQuery;
        $expected = 'SELECT "*" FROM "users" WHERE "name" = ?';

        $actual = Translater::binding(
            Translater::escape($input, Translater::SQL_DIALECT_DQUOTE),
            Translater::BIND_QUESTION_MARK
        );

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateSqlQueryBackticks()
    {
        $input = self::$oneParamInputQuery;
        $expected = "SELECT `*` FROM `users` WHERE `name` = ?";

        $actual = Translater::binding(
            Translater::escape($input, Translater::SQL_DIALECT_BTICK),
            Translater::BIND_QUESTION_MARK
        );

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateSqlQueryParentheses()
    {
        $input = "SELECT * FROM users WHERE (name = 'John' AND age > 30)";
        $expected = "SELECT * FROM users WHERE (name = 'John' AND age > 30)";

        $actual = Translater::escape($input);

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateSqlQueryCommas()
    {
        $input = "SELECT name, age, email FROM users";
        $expected = "SELECT name, age, email FROM users";

        $actual = Translater::escape($input);

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateSqlQueryForbiddenWords()
    {
        $input = "SELECT * FROM users WHERE name = 'DELETE'";
        $expected = "SELECT * FROM users WHERE name = 'DELETE'";

        $actual = Translater::escape($input);

        $this->assertEquals($expected, $actual);
    }
}
