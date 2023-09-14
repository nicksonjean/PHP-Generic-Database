<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\Validations;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \GenericDatabase\Helpers\Validations
 */
final class ValidationsTest extends TestCase
{
    public function testIsNumberReturnsTrue()
    {
        $value = '123';

        $result = Validations::isNumber($value);

        $this->assertTrue($result);
    }

    public function testIsNumberReturnsFalse()
    {
        $value = 'abc';

        $result = Validations::isNumber($value);

        $this->assertFalse($result);
    }

    public function testIsBooleanReturnsTrue()
    {
        $value = true;

        $result = Validations::isBoolean($value);

        $this->assertTrue($result);
    }

    public function testIsBooleanReturnsFalseForEmptyString()
    {
        $value = '';

        $result = Validations::isBoolean($value);

        $this->assertFalse($result);
    }

    public function testIsBooleanReturnsFalseForNull()
    {
        $value = null;

        $result = Validations::isBoolean($value);

        $this->assertNull($result);
    }

    public function testIsBooleanReturnsFalseForStringWithTabs()
    {
        $value = "\t\t\t";

        $result = Validations::isBoolean($value);

        $this->assertFalse($result);
    }

    public function testRandomStringGeneratedWithCorrectLength()
    {
        $length = 10;

        $result = Validations::randomString($length);

        $this->assertEquals($length, strlen($result));
    }

    public function testIsSelectReturnsTrue()
    {
        $stmt = 'SELECT * FROM table';

        $result = Validations::isSelect($stmt);

        $this->assertTrue($result);
    }
}
