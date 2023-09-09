<?php

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

    public function test_translate_sql_query_no_parameters()
    {
        $input = "SELECT * FROM users";
        $expected = "SELECT * FROM users";

        $actual = Translater::escape($input);

        $this->assertEquals($expected, $actual);
    }

    public function test_translate_sql_query_named_parameters()
    {
        $input = self::$twoParamInputQuery;
        $expected = "SELECT * FROM users WHERE id = ? AND name = ?";

        $actual = Translater::binding($input);

        $this->assertEquals($expected, $actual);
    }

    public function test_translate_sql_query_unnamed_parameters()
    {
        $input = self::$twoParamInputQuery;
        $expected = "SELECT * FROM users WHERE id = ? AND name = ?";

        $actual = Translater::binding($input, Translater::BIND_QUESTION_MARK);

        $this->assertEquals($expected, $actual);
    }

    public function test_translate_sql_query_single_quotes()
    {
        $input = self::$oneParamInputQuery;
        $expected = "SELECT '*' FROM 'users' WHERE 'name' = ?";

        $actual = Translater::binding(
            Translater::escape($input, Translater::SQL_DIALECT_SQUOTE),
            Translater::BIND_QUESTION_MARK
        );

        $this->assertEquals($expected, $actual);
    }

    public function test_translate_sql_query_double_quotes()
    {
        $input = self::$oneParamInputQuery;
        $expected = 'SELECT "*" FROM "users" WHERE "name" = ?';

        $actual = Translater::binding(
            Translater::escape($input, Translater::SQL_DIALECT_DQUOTE),
            Translater::BIND_QUESTION_MARK
        );

        $this->assertEquals($expected, $actual);
    }

    public function test_translate_sql_query_backticks()
    {
        $input = self::$oneParamInputQuery;
        $expected = "SELECT `*` FROM `users` WHERE `name` = ?";

        $actual = Translater::binding(
            Translater::escape($input, Translater::SQL_DIALECT_BTICK),
            Translater::BIND_QUESTION_MARK
        );

        $this->assertEquals($expected, $actual);
    }

    public function test_translate_sql_query_parentheses()
    {
        $input = "SELECT * FROM users WHERE (name = 'John' AND age > 30)";
        $expected = "SELECT * FROM users WHERE (name = 'John' AND age > 30)";

        $actual = Translater::escape($input);

        $this->assertEquals($expected, $actual);
    }

    public function test_translate_sql_query_commas()
    {
        $input = "SELECT name, age, email FROM users";
        $expected = "SELECT name, age, email FROM users";

        $actual = Translater::escape($input);

        $this->assertEquals($expected, $actual);
    }

    public function test_translate_sql_query_forbidden_words()
    {
        $input = "SELECT * FROM users WHERE name = 'DELETE'";
        $expected = "SELECT * FROM users WHERE name = 'DELETE'";

        $actual = Translater::escape($input);

        $this->assertEquals($expected, $actual);
    }
}
